<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magentoese\VMContent\Model\Page;

class AddHomePages implements DataPatchInterface
{

    /** @var Page  */
    private $page;

    /**
     * AddHomePages constructor.
     * @param Page $page
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    public function apply(){
        $this->page->install(['MagentoEse_VMContent::fixtures/luma_pages.csv']);
    }



    public static function getDependencies()
    {
        return [AddB2CSegments::class,AddDynamicBlocks::class,SetFeaturedProducts::class];
    }

    public function getAliases()
    {
        return [];
    }
}