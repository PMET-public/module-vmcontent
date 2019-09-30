<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\VMContent\Model\CartRule;

class AddCartRules implements DataPatchInterface
{


    /** @var CartRule  */
    protected $rule;

    /**
     * AddCartRules constructor.
     * @param CartRule $rule
     */
    public function __construct(CartRule $rule)
    {
        $this->rule = $rule;
    }


    public function apply()
    {
        $this->rule->install(['MagentoEse_VMContent::fixtures/cart_rules.csv']);
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