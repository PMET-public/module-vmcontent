<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\VMContent\Model;

use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes as TargetRuleActionAttributes;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TargetRule\Model as RuleModel;
use MagentoEse\VMContent\Model\ReplaceIds;

/**
 * Class Setup
 * Installation of related products rules
 */
class TargetRule
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
     * @var \Magento\TargetRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryManagementInterface
     */
    protected $categoryReadService;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;


    /** @var ReplaceIds  */
    protected $replaceIds;

    /**
     * TargetRule constructor.
     * @param SampleDataContext $sampleDataContext
     * @param RuleModel\RuleFactory $ruleFactory
     * @param \Magento\Catalog\Api\CategoryManagementInterface $categoryReadService
     * @param \MagentoEse\VMContent\Model\ReplaceIds $replaceIds
     * @param Json|null $serializer
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        \Magento\TargetRule\Model\RuleFactory $ruleFactory,
        \Magento\Catalog\Api\CategoryManagementInterface $categoryReadService,
        ReplaceIds $replaceIds,
        Json $serializer = null
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->ruleFactory = $ruleFactory;
        $this->categoryReadService = $categoryReadService;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->replaceIds = $replaceIds;
    }

    /**
     * @param array $categoryPath
     * @param string $ruleType
     * @return array|null
     */
    protected function getConditionFromCategory($categoryPath, $ruleType = 'Rule')
    {
        $categoryId = null;
        $tree = $this->categoryReadService->getTree();
        foreach ($categoryPath as $categoryName) {
            $categoryId = null;
            foreach ($tree->getChildrenData() as $child) {
                if ($child->getName() == $categoryName) {
                    $tree = $child;
                    /** @var \Magento\Catalog\Api\Data\CategoryTreeInterface $child */
                    $categoryId = $child->getId();
                    break;
                }
            }
        }
        if (!$categoryId) {
            return null;
        }

        $types = [
            'Rule' => 'Magento\TargetRule\Model\Rule\Condition\Product\Attributes',
            'Actions' => 'Magento\TargetRule\Model\Actions\Condition\Product\Attributes',
        ];
        if (empty($types[$ruleType])) {
            return null;
        }
        return [
            'type' => $types[$ruleType],
            'attribute' => 'category_ids',
            'operator' => '==',
            'value' => $categoryId,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function install(array $fixtures,$isActive)
    {
        foreach ($fixtures as $linkTypeId => $fileName) {
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
                $rule = $this->ruleFactory->create();
                if ($rule->getResourceCollection()->addFilter('name', $row['name'])->getSize() > 0) {
                    continue;
                }

                $rule->setName($row['name'])
                    ->setApplyTo($linkTypeId)
                    ->setIsActive(1)
                    ->setSortOrder(0)
                    ->setPositionsLimit(empty($row['limit']) ? 0 : $row['limit'])
                    ->setIsActive($isActive)
                    ->setConditionsSerialized($this->replaceIds->replaceAll($row['conditions_serialized']))
                    ->setActionsSerialized($this->replaceIds->replaceAll($row['actions_serialized']));
                $rule->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function installRefSampleData(array $fixtures,$isActive)
    {
        foreach ($fixtures as $linkTypeId => $fileName) {
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
                $rule = $this->ruleFactory->create();
                if ($rule->getResourceCollection()->addFilter('name', $row['name'])->getSize() > 0) {
                    continue;
                }

                $sourceCategory = $this->getConditionFromCategory(
                    array_filter(explode("\n", $row['source_category'])),
                    'Rule'
                );
                $targetCategory = $this->getConditionFromCategory(
                    array_filter(explode("\n", $row['target_category'])),
                    'Actions'
                );
                if (!$sourceCategory || !$targetCategory) {
                    continue;
                }
                $targetCategory['value_type'] = TargetRuleActionAttributes::VALUE_TYPE_CONSTANT;

                $ruleConditions = $this->createConditions($sourceCategory, $targetCategory);

                $rule->setName($row['name'])
                    ->setApplyTo($linkTypeId)
                    ->setIsActive(1)
                    ->setSortOrder(0)
                    ->setPositionsLimit(empty($row['limit']) ? 0 : $row['limit'])
                    ->setIsActive($isActive);
                $rule->loadPost($ruleConditions);
                $rule->save();
            }
        }
    }

    /**
     * @param string $sourceCategory
     * @param string $targetCategory
     * @return array
     */
    protected function createConditions($sourceCategory, $targetCategory)
    {
        $combineCondition = [
            'aggregator' => 'all',
            'value' => '1',
            'new_child' => '',
        ];
        $ruleConditions = [
            'conditions' => [
                1 => $combineCondition + ['type' => 'Magento\TargetRule\Model\Rule\Condition\Combine'],
                '1--1' => $sourceCategory,
            ],
            'actions' => [
                1 => $combineCondition + ['type' => 'Magento\TargetRule\Model\Actions\Condition\Combine'],
                '1--1' => $targetCategory,
            ],
        ];
        if (!empty($row['conditions'])) {
            $index = 2;
            foreach (array_filter(explode("\n", $row['conditions'])) as $condition) {
                $ruleConditions['actions']['1--' . $index] = $this->serializer->unserialize($condition);
                $index++;
            }
        }
        return $ruleConditions;
    }

    public function deleteRules(array $fixtures){
        foreach ($fixtures as $linkTypeId => $fileName) {
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
                $rule = $this->ruleFactory->create();

                //get adjusted name
                $adjustedName = $this->getAdjustedName($linkTypeId, $row['name']);
                //delete bad rules
                /** @var RuleModel $collection */
                $ruleToDelete  = $rule->getResourceCollection()->addFilter('name', $adjustedName)->getFirstItem();
                if($ruleToDelete->getSize()!=0){
                    $ruleToDelete->delete();
                }


            }
        }
    }

    /**
     * @param $linkTypeId
     * @param $adjustedName
     */
    private function getAdjustedName($linkTypeId, $adjustedName)
    {
        if ($linkTypeId == 2) {
            $adjustedName = str_replace("Related", "Up-sells", $adjustedName);
            $adjustedName = str_replace("Crosssell", "Up-sells", $adjustedName);
        } elseif ($linkTypeId == 1) {
            $adjustedName = str_replace("Crosssell", "Related Products", $adjustedName);
            $adjustedName = str_replace("Upsells", "Related Products", $adjustedName);
        } elseif ($linkTypeId == 3) {
            $adjustedName = str_replace("Upsells", "Cross-sells", $adjustedName);
            $adjustedName = str_replace("Related", "Cross-sells", $adjustedName);
        }

        return $adjustedName;
    }
}
