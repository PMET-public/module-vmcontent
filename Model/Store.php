<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Model;


use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Store\Api\Data\GroupInterfaceFactory;
use Magento\Store\Api\Data\StoreInterfaceFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ResourceModel\Group as GroupResourceModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Theme\Model\Theme\Registration as ThemeRegistration;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Sitemap\Model\SitemapFactory;

class Store
{

    /** @var CategoryInterfaceFactory   */
    protected $categoryFactory;

    /** @var CategoryRepositoryInterface  */
    protected $categoryRepository;

    /** @var WebsiteRepositoryInterface  */
    protected $websiteRepository;

    /** @var GroupInterfaceFactory  */
    protected $groupInterface;

    /** @var GroupRepositoryInterface  */
    protected $groupRepositoryInterface;

    /** @var GroupResourceModel  */
    protected $groupResourceModel;

    /** @var StoreRepositoryInterface  */
    protected $storeRepository;

    /** @var StoreFactory  */
    protected $storeFactory;

    /** @var ThemeRegistration  */
    protected $themeRegistration;

    /** @var ThemeCollection  */
    protected $themeCollection;

    /** @var ResourceConfig  */
    protected $resourceConfig;

    /** @var SitemapFactory  */
    protected $sitemapFactory;

    public function __construct(CategoryInterfaceFactory $categoryFactory, CategoryRepositoryInterface $categoryRepository,
                                WebsiteRepositoryInterface $websiteRepository, GroupInterfaceFactory $groupInterface,
                                GroupRepositoryInterface $groupRepositoryInterface, GroupResourceModel $groupResourceModel,
                                StoreRepositoryInterface $storeRepository, StoreFactory $storeFactory,
                                ThemeRegistration $themeRegistration, ThemeCollection $themeCollection, ResourceConfig $resourceConfig,
                                SitemapFactory $sitemapFactory)
    {
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->websiteRepository = $websiteRepository;
        $this->groupInterface = $groupInterface;
        $this->groupRepositoryInterface = $groupRepositoryInterface;
        $this->groupResourceModel = $groupResourceModel;
        $this->storeRepository = $storeRepository;
        $this->storeFactory = $storeFactory;
        $this->themeRegistration = $themeRegistration;
        $this->themeCollection = $themeCollection;
        $this->resourceConfig = $resourceConfig;
        $this->sitemapFactory = $sitemapFactory;
    }

    public function setup($rootCategory,$storeCode,$storeName,$viewCode,$viewName,$viewActive,$viewSortOrder,$assignView = false)
    {
        //create root catalog
        $rootCategoryId = $this->createCategory($rootCategory);
        //$rootCategoryId=62;
        //create store and view
        //get website
        try{
            $website = $this->websiteRepository->get('base');
            //create store(group)
            $group = $this->createStore($website, $rootCategoryId,$storeName,$storeCode);

            //create view (store)
            $newStore = $this->createView($website, $group,$viewName,$viewCode,$viewActive,$viewSortOrder);

            //assign view as default store if required
            if($assignView){
                $group->setDefaultStoreId($newStore->getId());
            }

            $this->groupResourceModel->save($group);

            return $newStore->getId();




        }catch(NoSuchEntityException $entityException){
            echo "base Website not found. Cannot add custom store";
        }

         //custom site map
        //add custom theme to catalog
        //custom theme in Theme customizer
    }

    public function addThemeToStore($themePath,$storeId){
        $this->themeRegistration->register();
        $themeId = $this->themeCollection->getThemeByFullPath($themePath)->getThemeId();
        //set theme for store
        $this->resourceConfig->saveConfig("design/theme/theme_id", $themeId, "stores",$storeId);
    }

    public function setHomepage($pageIdentifier,$storeId,$scope){
        $this->resourceConfig->saveConfig("web/default/cms_home_page", $pageIdentifier, $scope,$storeId);
    }

    private function createCategory($categoryName)
    {
        $data = [
            'parent_id' => 1,
            'name' =>$categoryName,
            'is_active' => 1,
            'is_anchor' => 1,
            'include_in_menu' => 0,
            'position'=>10,
            'store_id'=>0
        ];
        $category = $this->categoryFactory->create();
        $category->setData($data)
            ->setPath('1');
        $this->categoryRepository->save($category);
        return $category->getId();

    }

    /**
     * @param $groupCode string
     * @return int
     */
    public function getExistingGroupId($groupCode){
        $groups=$this->groupRepositoryInterface->getList();
        foreach($groups as $group){
            if($group->getCode()==$groupCode){
                return $group->getId();
                break;
            }
        }
        return 0;
    }

    /**
     * @param $storeCode string
     * @return int
     */
    public function getExistingStoreId($storeCode){
        $stores =$this->storeRepository->getList();
        foreach($stores as $store){
            if($store->getCode()==$storeCode){
                return $store->getId();
                break;
            }
        }
        return 0;
    }



    /**
     * @param WebsiteInterface $website
     * @param $rootCategoryId
     * @param $name
     * @param $code
     * @return \Magento\Store\Api\Data\GroupInterface
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createStore(WebsiteInterface $website, $rootCategoryId,$name,$code)
    {
        //Check if group exists. if it does, load and update
        $existingGroupId = $this->getExistingGroupId($code);
        if ($existingGroupId != 0) {
            $group = $this->groupRepositoryInterface->get($existingGroupId);
        } else {
            $group = $this->groupInterface->create();
        }
        $group->setWebsiteId($website->getId());
        $group->setName($name);
        $group->setRootCategoryId($rootCategoryId);
        $group->setCode($code);
        $this->groupResourceModel->save($group);
        return $group;
    }

    /**
     * @param WebsiteInterface $website
     * @param \Magento\Store\Api\Data\GroupInterface $group
     * @return \Magento\Store\Api\Data\StoreInterface|\Magento\Store\Model\Store
     * @throws NoSuchEntityException
     */
    public function createView(WebsiteInterface $website, \Magento\Store\Api\Data\GroupInterface $group,$name,$code,$isActive,$sortOrder)
    {
        //check if view exists, if it does load and update
        $existingStoreId = $this->getExistingStoreId($code);
        if ($existingStoreId != 0) {
            $newStore = $this->storeRepository->get($code);
        } else {
            $newStore = $this->storeFactory->create();
        }
        $newStore->setName($name);
        $newStore->setCode($code);
        $newStore->setWebsiteId($website->getId());
        // GroupId is a Store ID (in adminhtml terms)
        $newStore->setStoreGroupId($group->getId());
        $newStore->setIsActive($isActive);
        $newStore->setSortOrder($sortOrder);
        $newStore->save();
        return $newStore;
    }

    public function createSitemap($storeId,$fileName,$path){
        $map = $this->sitemapFactory->create();
        $map->setSitemapFilename($fileName);
        $map->setSitemapPath($path);
        $map->setStoreId($storeId);
        $map->save();
        $map->generateXml();
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