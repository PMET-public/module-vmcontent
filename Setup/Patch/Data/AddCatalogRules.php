<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\VMContent\Model\CatalogRule;


class AddCatalogRules implements DataPatchInterface
{

    /** @var CatalogRule  */
    private $catalogRule;

    /**
     * AddCatalogRules constructor.
     * @param CatalogRule $catalogRule
     */
    public function __construct(CatalogRule $catalogRule)
    {
        $this->catalogRule = $catalogRule;
    }

    public function apply()
    {
        $this->catalogRule->install(['MagentoEse_VMContent::fixtures/catalog_rules.csv']);
    }

    public static function getDependencies()
    {
        return [AddCustomBrand::class];
    }

    public function getAliases()
    {
        return [];
    }
}