<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\VMContent\Model;

use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\CustomerSegment\Model\SegmentFactory;
use Magento\CustomerSegment\Model\Segment as SegmentModel;
use MagentoEse\VMContent\Model\ReplaceIds;

/**
 * Class Segment
 */
class Segment
{
    /**
     * @var \Magento\Framework\File\Csv
    private
    protected $csvReader;

    /**
     * @var \Magento\Framework\Setup\SampleData\FixtureManager
     */
    private $fixtureManager;

    /**
     * @var SegmentFactory
     */
    private $segment;

    /** @var \MagentoEse\VMContent\Model\ReplaceIds  */
    private $replaceIds;


    /**
     * Segment constructor.
     * @param SampleDataContext $sampleDataContext
     * @param SegmentFactory $segment
     * @param ReplaceIds $replaceIds
     */

    public function __construct(
        SampleDataContext $sampleDataContext, SegmentFactory $segment, ReplaceIds $replaceIds
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->segment = $segment;
        $this->replaceIds = $replaceIds;
      }

    /**
     * {@inheritdoc}
     */
    public function install(array $fixtures)
    {
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                throw new \Exception('File not found: '.$fileName);
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;

                /** @var SegmentModel $segment */
                $segment = $this->segment->create();
                $ruleCount = $segment->getCollection()->addFilter('name',$row['name'],'eq');

                if(!$ruleCount->getSize()) {
                    $segment->addData(['website_ids' => [1]]);
                    $segment->setName($row['name']);
                    $conditions = $this->replaceIds->replaceCategories($row['conditions_serialized']);
                    $conditions = $this->replaceIds->replaceCustomerAttributes($conditions);
                    $conditions = $this->replaceIds->replaceProductAttributes($conditions);
                    $conditions = $this->replaceIds->replaceAttributeSets($conditions);
                    $conditions = $this->replaceIds->replaceCustomerGroups($conditions);
                    $segment->setConditionsSerialized($conditions);
                    //$segment->setConditionSql($row['sql']);
                    $segment->setIsActive($row['is_active']);
                    $segment->addData(['apply_to' => $data['apply_to']]);
                    $segment->save();
                    $segment->matchCustomers();
                }
            }
        }

    }
}
