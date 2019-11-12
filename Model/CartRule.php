<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\VMContent\Model;

use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\SalesRule\Model\RuleFactory as RuleFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use MagentoEse\VMContent\Model\ReplaceIds;
use Magento\Reward\Model\ResourceModel\Reward;
/**
 * Class Rule
 */
class CartRule
{
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvReader;

    /**
     * @var \Magento\Framework\Setup\SampleData\FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var \Magento\CatalogRuleSampleData\Model\Rule
     */
    protected $catalogRule;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /** @var \MagentoEse\VMContent\Model\ReplaceIds  */
    protected $replaceIds;

    /** @var Reward  */
    protected $reward;


    /**
     * CartRule constructor.
     * @param SampleDataContext $sampleDataContext
     * @param RuleFactory $ruleFactory
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param \Magento\CatalogRuleSampleData\Model\Rule $catalogRule
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \MagentoEse\VMContent\Model\ReplaceIds $replaceIds
     * @param Reward $reward
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        RuleFactory $ruleFactory,
        RuleCollectionFactory $ruleCollectionFactory,
        \Magento\CatalogRuleSampleData\Model\Rule $catalogRule,
        \Magento\Eav\Model\Config $eavConfig,
        ReplaceIds $replaceIds, Reward $reward
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->ruleFactory = $ruleFactory;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->catalogRule = $catalogRule;
        $this->eavConfig = $eavConfig;
        $this->replaceIds = $replaceIds;
        $this->reward = $reward;

    }

    /**
     * {@inheritdoc}
     */
    public function install(array $fixtures)
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', 'sku');
        if ($attribute->getIsUsedForPromoRules() == 0) {
            $attribute->setIsUsedForPromoRules('1')->save();
        }
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
                /** @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection $ruleCollection */
                $ruleCollection = $this->ruleCollectionFactory->create();
                $ruleCollection->addFilter('name', $row['name']);
                if ($ruleCollection->count() > 0) {
                    continue;
                }
                $row['customer_group_ids'] = $this->catalogRule->getGroupIds();
                $row['website_ids'] = $this->catalogRule->getWebsiteIds();
                $row['conditions_serialized'] = $this->catalogRule->convertSerializedData($this->replaceIds->replaceAll($row['conditions_serialized']));
                $row['actions_serialized'] = $this->catalogRule->convertSerializedData($this->replaceIds->replaceAll($row['actions_serialized']));
                /** @var \Magento\SalesRule\Model\Rule $rule */
                $rule = $this->ruleFactory->create();
                $rule->loadPost($row);
                $rule->save();
                $foo = $rule->getRowId();
                if($row['rewards']!=""){
                   $this->reward->saveRewardSalesrule($rule->getRowId(),$row['rewards']);
                }
            }
        }
    }
}
