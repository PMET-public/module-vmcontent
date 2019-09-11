<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Api\Data\CategoryProductLinkInterfaceFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterfaceFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory;
use Magento\Catalog\Model\Category;
class UpdateProductPosition implements DataPatchInterface
{

    /** @var  CategoryProductLinkInterfaceFactory*/
    private $categoryProductLinkInterface;

    /** @var CategoryInterfaceFactory  */
    private $categoryInterface;

    /** @var CategoryLinkRepositoryInterface  */
    private $categoryLinkRepository;

    /** @var CategoryRepositoryInterface  */
    private $categoryRepository;

    /** @var Category  */
    private $categoryModel;

    public function __construct(CategoryProductLinkInterfaceFactory $categoryProductLinkInterfaceFactory, CategoryLinkRepositoryInterface $categoryLinkRepository,
                                CategoryInterfaceFactory $categoryInterfaceFactory, CategoryRepositoryInterface $categoryRepository, Category $categoryModel)
    {
        $this->categoryProductLinkInterface = $categoryProductLinkInterfaceFactory;
        $this->categoryInterface =$categoryInterfaceFactory;
        $this->categoryLinkRepository = $categoryLinkRepository;
        $this->categoryRepository = $categoryRepository;
        $this->categoryModel = $categoryModel;
    }

    public function apply()
    {
        //save category to trigger position assigments
        $category = $this->categoryModel->load(25);
        //$category->post
        $category = $this->categoryRepository->get(25);

        $category->setPostedPositions;
        $this->categoryRepository->save($category);
        $int = $this->categoryProductLinkInterface->create();
        $int->setCategoryId(25);
        $int->setSku('WH01');
        $int->setPosition(0);
        $this->categoryLinkRepository->save($int);
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