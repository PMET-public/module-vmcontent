<?php


namespace MagentoEse\VMContent\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use MagentoEse\VMContent\Model\Staging;

class RecurringData implements InstallDataInterface
{
    /** @var Staging  */
    private $staging;

    public function __construct(Staging $staging)
    {
        $this->staging = $staging;
    }

       
    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->staging->addScheduledUpdates(['MagentoEse_VMContent::fixtures/homepageCampaign/homepage_campaign.csv']);
    }
}
