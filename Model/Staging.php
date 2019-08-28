<?php


namespace MagentoEse\VMContent\Model;

use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as CatalogRuleCollection;
use Magento\CatalogRuleStaging\Api\CatalogRuleStagingInterface;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\CmsStaging\Api\BlockStagingInterface;
use Magento\CmsStaging\Api\PageStagingInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManagerFactory;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Cms\Api\Data\PageInterface;


class Staging
{

    /** @var ProductStagingInterface  */
    private $productStaging;

    /** @var BlockStagingInterface   */
    private $blockStaging;

    /** @var PageStagingInterface  */
    private $pageStaging;

    /** @var \Magento\Framework\File\Csv */
    private $csvReader;

    private $fixtureManager;

    /** @var UpdateInterfaceFactory  */
    private $updateInterfaceFactory;

    /** @var UpdateRepositoryInterface  */
    private $updateRepositoryInterface;

    /** @var VersionManagerFactory  */
    private $versionManagerFactory;

    /** @var PageRepositoryInterface  */
    private $pageRepository;

    /** @var SearchCriteriaBuilder  */
    private $searchCriteria;

    /** @var ReplaceIds  */
    private $replaceIds;

    /** @var CatalogRuleStagingInterface  */
    private $catalogRuleStaging;

    /** @var CatalogRuleCollection  */
    private $catalogRuleCollection;


    public function __construct(ProductStagingInterface $productStaging, BlockStagingInterface $blockStaging,
                                PageStagingInterface $pageStaging, SampleDataContext $sampleDataContext,
                                UpdateInterfaceFactory $updateInterfaceFactory, UpdateRepositoryInterface $updateRepositoryInterface,
                                VersionManagerFactory $versionManagerFactory, PageRepositoryInterface $pageRepository,
                                SearchCriteriaBuilder $searchCriteriaBuilder, ReplaceIds $replaceIds,
                                CatalogRuleCollection $catalogRuleCollection, CatalogRuleStagingInterface $catalogRuleStaging)
    {
        $this->productStaging = $productStaging;
        $this->blockStaging = $blockStaging;
        $this->pageStaging = $pageStaging;
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->updateInterfaceFactory = $updateInterfaceFactory;
        $this->updateRepositoryInterface = $updateRepositoryInterface;
        $this->versionManagerFactory = $versionManagerFactory;
        $this->pageRepository = $pageRepository;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->replaceIds = $replaceIds;
        $this->catalogRuleCollection= $catalogRuleCollection;
        $this->catalogRuleStaging = $catalogRuleStaging;
    }

    public function addScheduledUpdates($updateType,$fixtures){
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                throw new \Exception('Campaign File not found: ' . $fileName);
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;
                $newDates = $this->adjustDates($row['start_date'],$row['end_date']);


                $campaign = $this->addCampaign($row['name'],$newDates['startDate'],$newDates['endDate']);
                //include file of elements to update
                $contentFiles = explode(",",$row['content_files']);
                foreach($contentFiles as $contentFile) {
                    $contentFileName = $this->fixtureManager->getFixture('MagentoEse_VMContent::fixtures/'.$contentFile);

                    echo($contentFileName."\n");
                    if (!file_exists($contentFileName)) {
                        throw new \Exception('Content File not found: ' . $contentFileName);
                    }

                    $contentRows = $this->csvReader->getData($contentFileName);
                    $contentHeader = array_shift($contentRows);

                    foreach ($contentRows as $contentRow) {
                        $contentData = [];
                        foreach ($contentRow as $key => $value) {
                            $contentData[$contentHeader[$key]] = $value;
                        }
                        $contentRow = $contentData;
                        switch ($contentData['type']) {
                            case "page":
                                $this->addPageToCampaign($contentRow, $campaign->getId());
                                break;
                            case "block":
                                echo "block";
                                break;
                            case "product":
                                echo "product";
                                break;
                            case "catalogrule":
                                $this->addCatalogRuleToCampaign($contentRow,$campaign->getId());
                                break;
                            case "cartrule":
                                echo "cartrule";
                                break;
                            case "category":
                                echo "category";
                                break;
                        }
                    }
                }

            }
        }
    }

    /**
     * @param $campaignName
     * @param $startDate
     * @param $endDate
     * @return UpdateInterface
     */
    public function addCampaign($campaignName, $startDate, $endDate){
        /** @var UpdateInterface $schedule */
        $schedule = $this->updateInterfaceFactory->create();
        $schedule->setName($campaignName);
        // date format 'Y-m-d H:i:s' UCT time
        $schedule->setStartTime($startDate);
        if($endDate!=''){
            $schedule->setEndTime($endDate);
        }
        //Save the schedule
        $update = $this->updateRepositoryInterface->save($schedule);
        $version = $this->versionManagerFactory->create();
        $version->setCurrentVersionId($update->getId());
        return $update;
    }

    public function addPageToCampaign($pageData, $stagingId){
        //get page
        $search = $this->searchCriteria->addFilter(PageInterface::TITLE,$pageData['identifier'],'eq')->create();
        $pages = $this->pageRepository->getList($search)->getItems();
        //update
        foreach($pages as $page){
            $page->setContent($this->replaceIds->replaceAll($pageData['content']));
            $this->pageStaging->schedule($page,$stagingId);
        }
    }

    public function addCatalogRuleToCampaign($ruleData, $stagingId){
        //no updates to the rule are being considered for MVP, just adding the rule to the campaign and setting status to active
        $ruleCollection =  $this->catalogRuleCollection->create();
        $rule = $ruleCollection->addFilter('name',$ruleData['identifier'],'eq')->getFirstItem();
        $rule->setIsActive(1);
        $this->catalogRuleStaging->schedule($rule,$stagingId);

    }

    public function addProductToCampaign($product, $stagingId){

    }

    public function addBlockToCampaign($block, $stagingId){

    }


    public function adjustDates($startDateIn,$endDateIn)
    {
        /*Dates need to be validated as you cannot schedule something to start or end in the past
                        1) If the current date is between the start and end date, adjust the start date to current date/time
                        2) If the end date has passed, bump them both out to next year
                        */
        $currentDateTime = new \DateTime();
        $currentDateTime = $currentDateTime->getTimestamp();
        $startDate = strtotime($startDateIn);
        $endDate = strtotime($endDateIn);

        //bring past years up to current year
        if(date('Y',$startDate) < date('Y',$currentDateTime)){
            $startDate = strtotime(date(date('Y',$currentDateTime).'-m-d H:i:s',$startDate));
        }
        if(date('Y',$endDate) < date('Y',$currentDateTime)){
            $endDate = strtotime(date(date('Y',$currentDateTime).'-m-d H:i:s',$endDate));
        }

        //if the campaign has already ended, bump it to next year
        if($endDate < $currentDateTime){
            $thisYear = date('Y',$currentDateTime);
            $startDate = strtotime('+1 year', strtotime(date($thisYear.'-m-d H:i:s',$startDate)));
            $endDate = strtotime('+1 year', strtotime(date($thisYear.'-m-d H:i:s',$endDate)));
        }

        // if campaign dates are already in flight, bring start date to current date
        if ($startDate < $currentDateTime && $endDate > $currentDateTime) {
            $startDate = strtotime('+1 minute', $currentDateTime);
        }
        //echo "updated start Date=".date('Y-m-d H:i:s',$startDate)."\n";
        //echo "updated end Date=".date('Y-m-d H:i:s',$endDate)."\n";
        return ['startDate'=>date('Y-m-d H:i:s',$startDate),'endDate'=>date('Y-m-d H:i:s',$endDate)];
    }


}