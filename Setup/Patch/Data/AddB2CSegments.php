<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\VMContent\Model\Segment;


class AddB2CSegments implements DataPatchInterface
{

    /** @var Segment  */
    private $segment;

    /**
     * AddB2CSegments constructor.
     * @param Segment $segment
     */
    public function __construct(Segment $segment)
    {
        $this->segment = $segment;
    }

    public function apply()
    {
        $this->segment->install(['MagentoEse_VMContent::fixtures/segments.csv']);
    }

    public static function getDependencies()
    {
        return [AddRunnerCustomerAttribute::class,AddRunningAttributeToProducts::class,SetFeaturedAndSaleProducts::class];
    }

    public function getAliases()
    {
        return [];
    }
}