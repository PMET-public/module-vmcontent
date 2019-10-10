<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\VMContent\Model\TargetRule;
use Magento\TargetRule\Model\Rule as RuleModel;

class ResetTargetRules implements DataPatchInterface
{

    /** @var TargetRule  */
    protected $targetRule;

    public function __construct(TargetRule $targetRule)
    {
        $this->targetRule = $targetRule;
    }

    public function apply()
    {
        //add new rules
        $this->targetRule->install(
            [
                RuleModel::CROSS_SELLS => 'MagentoEse_VMContent::fixtures/crossell.csv',
                RuleModel::UP_SELLS => 'MagentoEse_VMContent::fixtures/upsell.csv',
                RuleModel::RELATED_PRODUCTS => 'MagentoEse_VMContent::fixtures/related.csv'
            ],1
        );

        //delete old rules
        $this->targetRule->deleteRules(
            [
                RuleModel::RELATED_PRODUCTS => 'Magento_TargetRuleSampleData::fixtures/crossell.csv',
                RuleModel::UP_SELLS => 'Magento_TargetRuleSampleData::fixtures/related.csv',
                RuleModel::CROSS_SELLS => 'Magento_TargetRuleSampleData::fixtures/upsell.csv'
            ]
        );
        //add old rules back in correctly but set status to inactive
        $this->targetRule->installRefSampleData(
            [
                RuleModel::CROSS_SELLS => 'Magento_TargetRuleSampleData::fixtures/crossell.csv',
                RuleModel::RELATED_PRODUCTS => 'Magento_TargetRuleSampleData::fixtures/related.csv',
                RuleModel::UP_SELLS => 'Magento_TargetRuleSampleData::fixtures/upsell.csv'
            ],0
        );
        //add new rules
        $this->targetRule->install(
            [
                RuleModel::CROSS_SELLS => 'Magento_VMContent::fixtures/crossell.csv'
            ],1
        );
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}