<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\VMContent\Model\Bookmark;

class AddBookmarks implements DataPatchInterface
{



    /** @var Bookmark  */
    private $bookmark;

    public function __construct(Bookmark $bookmark)
    {
        $this->bookmark = $bookmark;

    }

    public function apply()
    {
        $this->bookmark->install(['MagentoEse_VMContent::fixtures/bookmarks.csv']);
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