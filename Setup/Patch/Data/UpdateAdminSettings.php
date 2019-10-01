<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

class UpdateAdminSettings implements DataPatchInterface
{

    /** @var ResourceConfig  */
    private $resourceConfig;

    /** @var ScopeConfig  */
    private $scopeConfig;

    /**
     * UpdateAdminSettings constructor.
     * @param ResourceConfig $resourceConfig
     * @param ScopeConfig $scopeConfig
     */
    public function __construct(ResourceConfig $resourceConfig, ScopeConfig $scopeConfig)
    {
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
    }

    public function apply()
    {
        //set customer to redirect to account dashboard after login
        $this->resourceConfig->saveConfig('customer/startup/redirect_dashboard', 1, ScopeConfig::SCOPE_TYPE_DEFAULT, 0);

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