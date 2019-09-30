<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\VMContent\Model\Page;
use Magento\Framework\Api\SearchCriteriaBuilder;

class AddHomePages implements DataPatchInterface
{

    /** @var Page  */
    private $page;

    /** @var PageRepositoryInterface  */
    private $pageRepository;

    /** @var SearchCriteriaBuilder  */
    private $searchCriteriaBuilder;

    /**
     * AddHomePages constructor.
     * @param Page $page
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(Page $page, PageRepositoryInterface $pageRepository, SearchCriteriaBuilder $searchCriteriaBuilder)
    {
        $this->page = $page;
        $this->pageRepository = $pageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function apply(){
        $this->page->install(['MagentoEse_VMContent::fixtures/luma_pages.csv']);
        //update seo on venia page
        $this->updateVeniaSEO();
    }

    private function updateVeniaSEO()
    {
        $search = $this->searchCriteriaBuilder->addFilter(PageInterface::TITLE, "Home Page - Venia", 'eq')->create();
        $pages = $this->pageRepository->getList($search)->getItems();
        foreach ($pages as $page) {
            $page->setMetaTitle('VENIA Official Online Store');
            $page->setMetaDescription('With 50 stores spanning 40 states and growing, Venia is a nationally recognized high fashion retailer for women. We’re passionate about helping you look your best.');
            $page->setMetaKeywords('fashion,women,blouse,top,pant,dress,venia');
            $this->pageRepository->save($page);
        }
    }


    public static function getDependencies()
    {
        return [AddCustomBrand::class,AddB2CSegments::class,AddDynamicBlocks::class,SetFeaturedAndSaleProducts::class];
    }

    public function getAliases()
    {
        return [];
    }


}