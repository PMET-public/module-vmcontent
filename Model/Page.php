<?php


namespace MagentoEse\VMContent\Model;



use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Store\Api\StoreRepositoryInterface;
use MagentoEse\VMContent\Model\ReplaceIds;
use Magento\Framework\Api\SearchCriteriaBuilder;


class Page
{
    /**
     * @var \Magento\Framework\File\Csv
     */
    private $csvReader;


    private $fixtureManager;

    /** @var PageInterfaceFactory  */
    private $pageInterfaceFactory;

    /** @var ReplaceIds  */
    private $replaceIds;

    /** @var StoreRepositoryInterface  */
    private $storeRepository;

    /** @var PageRepositoryInterface  */
    private $pageRepository;

    /** @var SearchCriteriaBuilder  */
    private $searchCriteria;

    /**
     * Page constructor.
     * @param SampleDataContext $sampleDataContext
     * @param PageInterfaceFactory $pageInterfaceFactory
     * @param \MagentoEse\VMContent\Model\ReplaceIds $replaceIds
     * @param StoreRepositoryInterface $storeRepository
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteria
     */
    public function __construct( SampleDataContext $sampleDataContext,PageInterfaceFactory $pageInterfaceFactory,
                                 ReplaceIds $replaceIds, StoreRepositoryInterface $storeRepository,
                                 PageRepositoryInterface $pageRepository, SearchCriteriaBuilder $searchCriteria)
    {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->pageInterfaceFactory = $pageInterfaceFactory;
        $this->replaceIds = $replaceIds;
        $this->storeRepository = $storeRepository;
        $this->pageRepository = $pageRepository;
        $this->searchCriteria = $searchCriteria;

    }

    public function install(array $fixtures){
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                throw new \Exception('File not found: ' . $fileName);
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;
                $row['content'] = $this->replaceIds->replaceAll($row['content']);

                //check if page exists

                $search = $this->searchCriteria->addFilter(PageInterface::IDENTIFIER,$row['identifier'],'eq')
                    ->addFilter(PageInterface::TITLE, $row['title'],'eq')->create();

                $pages = $this->pageRepository->getList($search)->getTotalCount();
                if($pages==0){
                    $this->pageInterfaceFactory->create()
                        //->load($row['identifier'], 'identifier')
                        ->addData($row)
                        //->setStores([\Magento\Store\Model\Store::DEFAULT_STORE_ID])
                        ->setStores($this->getStoreIds($row['stores']))
                        ->save();
                }



            }
        }
    }

    public function getStoreIds($storeCodes){
        $storeList = explode(",",$storeCodes);
        $returnArray = [];
        foreach($storeList as $storeCode){
            $stores =$this->storeRepository->getList();
            foreach($stores as $store){
                if($store->getCode()==$storeCode){
                    $returnArray[]= $store->getId();
                    break;
                }
            }
        }
        return $returnArray;
    }
}