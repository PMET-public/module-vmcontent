<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;

use MagentoEse\VMContent\Model\ProductImport;
use MagentoEse\VMContent\Model\ReplaceIds;
use MagentoEse\VMContent\Model\Category;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\VisualMerchandiser\Model\Rules\Factory;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\VisualMerchandiser\Model\Rules\RuleInterfaceFactory as RuleInterfaceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use MagentoEse\VMContent\Model\SetSession;

class SetFeaturedAndSaleProducts implements DataPatchInterface
{

    /** @var ReplaceIds  */
    private $replaceIds;

    /** @var EavSetupFactory  */
    private $eavSetup;

    /** @var Category  */
    private $category;

    /** @var Factory   */
    private $rules;

    /** @var RuleInterfaceFactory  */
    private $ruleInterfaceFactory;

    /** @var ResourceConfig  */
    private $resourceConfig;


    /** @var ScopeConfig  */
    private $scopeConfig;

    /** @var SetSession  */
    private $setSession;

    /** @var ProductImport  */
    private $productImport;
    public function __construct(ReplaceIds $replaceIds, EavSetupFactory $eavSetup,Category $category,
                                Factory $rules, RuleInterfaceFactory $ruleInterfaceFactory, ResourceConfig $resourceConfig,
                                ScopeConfig $scopeConfig, SetSession $setSession, ProductImport $productImport)
    {
        $this->replaceIds = $replaceIds;
        $this->eavSetup = $eavSetup;
        $this->category= $category;
        $this->rules = $rules;
        $this->ruleInterfaceFactory = $ruleInterfaceFactory;
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig =  $scopeConfig;
        $this->setSession = $setSession;
        $this->productImport = $productImport;
    }




    public function apply(){
        $this->featuredProduct();
    }



    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function featuredProduct()
    {
        ///add featured product attribute
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetup->create();

        /**
         * Add attributes to the eav/attribute
         */
        $attributeCode = "featured_product";
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            $attributeCode,
            [
                'group' => 'Product Details',
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Featured Product',
                'input' => 'boolean',
                'class' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_STORE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => true,
                'is_used_for_promo_rules' => true
            ]
        );
        //make attribute available to visual merch, along with the Sale attribute which we'll use to drive the sale category
        $currentValue = $this->scopeConfig->getValue('visualmerchandiser/options/smart_attributes', ScopeConfig::SCOPE_TYPE_DEFAULT);
        $this->resourceConfig->saveConfig('visualmerchandiser/options/smart_attributes', $currentValue . ",sale," . $attributeCode, ScopeConfig::SCOPE_TYPE_DEFAULT, 0);


        $this->productImport->install(['MagentoEse_VMContent::fixtures/featured_sale.csv']);

        $this->category->install(['MagentoEse_VMContent::fixtures/featured_category.csv']);
    }
}