<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\VMContent\Model\Store as StoreCreate;
use MagentoEse\VMContent\Model\Page;
use Magento\Sitemap\Model\SitemapFactory;
use MagentoEse\VMContent\Model\SetSession;

class AddCustomBrand implements DataPatchInterface
{
    /** @var StoreCreate  */
    protected $storeCreate;

    /** @var Page  */
    protected $page;


    public function __construct(StoreCreate $storeCreate, Page $page, SetSession $session)
    {
        $this->storeCreate = $storeCreate;
        $this->page  = $page;
    }

    public function apply()
    {
        //Create Store
        $storeId = $this->storeCreate->setup('Custom','custom_b2c_store','Custom B2C Store',
            'custom_b2c_us','Custom US English',true,15);
        //Assign theme to store
        $this->storeCreate->addThemeToStore('frontend/Custom/blank',$storeId);
        //load new page
        $this->page->install(['MagentoEse_VMContent::fixtures/custom_homepage.csv']);
        //add homepage to site
        $this->storeCreate->setHomepage('custom-home',$storeId,'stores');

        //create and generate sitemap
        $this->storeCreate->createSitemap(4,'custom.xml','/pub/');

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