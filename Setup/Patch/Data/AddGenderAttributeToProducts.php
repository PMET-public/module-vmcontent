<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;

use MagentoEse\VMContent\Model\ProductAttributes;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddGenderAttributeToProducts implements DataPatchInterface
{

    /** @var ProductAttributes  */
    private $productAttributes;


    /**
     * AddRunningAttributeToProducts constructor.
     * @param ProductAttributes $productAttributes
     */
    public function __construct(ProductAttributes $productAttributes)
    {
        $this->productAttributes = $productAttributes;
    }


    public function apply(){
        $this->productAttributes->addAttributeToSet('gender','Top','Product Details',100);
        $this->productAttributes->addAttributeToSet('gender','Bottom','Product Details',100);
        $this->productAttributes->install(['MagentoEse_VMContent::fixtures/product_attributes_gender.csv']);
    }



    public static function getDependencies()
    {
        return [AddCustomBrand::class,AddRunnerCustomerAttribute::class];
    }

    public function getAliases()
    {
        return [];
    }
}