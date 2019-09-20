<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use MagentoEse\VMContent\Model\Banner;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddDynamicBlocks implements DataPatchInterface
{

    /** @var Banner  */
    private $banner;


    /**
     * AddDynamicBlocks constructor.
     * @param Banner $banner
     */
    public function __construct(Banner $banner)
    {
        $this->banner = $banner;
    }

    public function apply(){
        echo "installing " , get_class($this) , "\n";
        $this->banner->install(['MagentoEse_VMContent::fixtures/dynamic_blocks.csv']);
    }

    public static function getDependencies()
    {
        return [AddB2CSegments::class];
    }

    public function getAliases()
    {
        return [];
    }
}