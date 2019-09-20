<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use MagentoEse\VMContent\Model\Store;

class AddFavicons implements DataPatchInterface
{

    private $storeSettings = [["storeview"=>"default","icon"=>"lumafavicon.png"],["storeview"=>"luma_de","icon"=>"lumafavicon.png"],["storeview"=>"venia_us","icon"=>"veniafavicon.png"]];


    /** @var ResourceConfig  */
    private $resourceConfig;

    /** @var Store  */
    private $store;

    public function __construct(ResourceConfig $resourceConfig,Store $store)
    {
        $this->resourceConfig = $resourceConfig;
        $this->store = $store;
    }

    public function apply()
    {
        echo "installing " , get_class($this) , "\n";
        foreach($this->storeSettings as $setting){
            $this->resourceConfig->saveConfig("design/head/shortcut_icon", "stores/".$setting['icon'], "stores",$this->store->getExistingStoreId($setting['storeview']));
        }

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