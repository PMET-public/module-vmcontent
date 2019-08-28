<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;



use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\VMContent\Model\Staging;

class Test implements DataPatchInterface
{

    /** @var AddScheduledHomePageChanges  */
    private $addScheduledHomePageChanges;

    public function __construct(
        Staging $addScheduledHomePageChanges
    ){
        $this->addScheduledHomePageChanges = $addScheduledHomePageChanges;
    }




    public function apply(){
        //print_r($this->addScheduledHomePageChanges->adjustDates("2019-10-04 17:00:00","2019-10-31 16:59:59"));
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