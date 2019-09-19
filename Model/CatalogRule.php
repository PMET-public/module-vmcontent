<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\VMContent\Model;

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\Rule;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\CatalogRule\Model\Rule\JobFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;
use MagentoEse\VMContent\Model\ReplaceIds;
use Magento\CatalogRule\Api\Data\RuleInterface;


/**
 *  * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CatalogRule
{
    /**
     * @var \Magento\Framework\Setup\SampleData\FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvReader;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $groupFactory;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $websiteFactory;

    /**
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var Json
     */
    private $serializer;

    /** @var ReplaceIds  */
    protected $replaceIds;

    /** @var CatalogRuleRepositoryInterface  */
    protected $catalogRuleRepository;

    /**
     * CatalogRule constructor.
     * @param SampleDataContext $sampleDataContext
     * @param RuleFactory $ruleFactory
     * @param JobFactory $jobFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Customer\Model\GroupFactory $groupFactory
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param Json|null $serializer
     * @param \MagentoEse\VMContent\Model\ReplaceIds $replaceIds
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        RuleFactory $ruleFactory,
        JobFactory $jobFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        ReplaceIds $replaceIds, CatalogRuleRepositoryInterface $catalogRuleRepository,
        Json $serializer = null

    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->ruleFactory = $ruleFactory;
        $this->jobFactory = $jobFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->groupFactory = $groupFactory;
        $this->websiteFactory = $websiteFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->replaceIds = $replaceIds;
        $this->catalogRuleRepository = $catalogRuleRepository;
    }

    /**
     * @param array $fixtures
     * @throws \Exception
     */
    public function install(array $fixtures)
    {
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;
                /** @var Rule $ruleCheck */
                $ruleCheck = $this->ruleFactory->create();
                $ruleCount = $ruleCheck->getCollection()->addFilter(RuleInterface::NAME,$row['name'],'eq');

                if(!$ruleCount->getSize()) {
                    $row['customer_group_ids'] = $this->getGroupIds();
                    $row['website_ids'] = $this->getWebsiteIds();
                    $row['conditions_serialized'] = $this->convertSerializedData($this->replaceIds->replaceAll($row['conditions_serialized']));
                    $row['actions_serialized'] = $this->convertSerializedData($this->replaceIds->replaceAll($row['actions_serialized']));
                    $ruleModel = $this->ruleFactory->create();
                    $ruleModel->loadPost($row);
                    $ruleModel->save();
                }
            }
        }
        $ruleJob = $this->jobFactory->create();
        $ruleJob->applyAll();
    }



    /**
     * @param array $data
     * @return mixed
     */
    public function convertSerializedData($data)
    {
        $regexp = '/\%(.*?)\%/';
        preg_match_all($regexp, $data, $matches);
        $replacement = null;
        foreach ($matches[1] as $matchedId => $matchedItem) {
            $extractedData = array_filter(explode(",", $matchedItem));
            foreach ($extractedData as $extractedItem) {
                $separatedData = array_filter(explode('=', $extractedItem));
                if ($separatedData[0] == 'url_key') {
                    if (!$replacement) {
                        $replacement = $this->getCategoryReplacement($separatedData[1]);
                    } else {
                        $replacement .= ',' . $this->getCategoryReplacement($separatedData[1]);
                    }
                }
            }
            if (!empty($replacement)) {
                $data = preg_replace(
                    '/' . $matches[0][$matchedId] . '/',
                    $this->serializer->serialize($replacement),
                    $data
                );
            }
        }
        return $data;
    }

    /**
     * @param string $urlKey
     * @return mixed|null
     */
    protected function getCategoryReplacement($urlKey)
    {
        $categoryCollection = $this->categoryCollectionFactory->create();
        $category = $categoryCollection->addAttributeToFilter('url_key', $urlKey)->getFirstItem();
        $categoryId = null;
        if (!empty($category)) {
            $categoryId = $category->getId();
        }
        return $categoryId;
    }

    /**
     * @return array
     */
    public function getGroupIds()
    {
        $groupsIds = [];
        $collection = $this->groupFactory->create()->getCollection();
        foreach ($collection as $group) {
            $groupsIds[] = $group->getId();
        }
        return $groupsIds;
    }

    /**
     * @return array
     */
    public function getWebsiteIds()
    {
        $websiteIds = [];
        $collection = $this->websiteFactory->create()->getCollection();
        foreach ($collection as $website) {
            $websiteIds[] = $website->getId();
        }
        return $websiteIds;
    }
}
