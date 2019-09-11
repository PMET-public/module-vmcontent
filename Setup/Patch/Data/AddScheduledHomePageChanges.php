<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\VMContent\Model\Staging;


class AddScheduledHomePageChanges implements DataPatchInterface
{

    /** @var Staging  */
    private $staging;

    public function __construct(Staging $staging)
    {
        $this->staging = $staging;
    }

    public function apply()
    {
        $this->staging->addScheduledUpdates(['MagentoEse_VMContent::fixtures/homepageCampaign/homepage_campaign.csv']);
    }

    public static function getDependencies()
    {
        return [AddCatalogRules::class];
    }

    public function getAliases()
    {
        return [];
    }
}