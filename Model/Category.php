<?php
/**
 * Copyright © 2019 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\VMContent\Model;

use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\VisualMerchandiser\Model\Rules;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterfaceFactory;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use \Magento\Framework\Data\Tree\Node;
/**
 * Class Category
 */
class Category
{
    /**
     * @var \Magento\Framework\Setup\SampleData\FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var CategoryInterfaceFactory
     */
    protected $categoryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var TreeFactory
     */
    protected $resourceCategoryTreeFactory;


    /**
     * @var Node
     */
    protected $categoryTree;

    /**
     * @var StoreInterfaceFactory
     */
    protected $storeFactory;

    /**
     * @var BlockInterfaceFactory
     */
    protected $blockFactory;

    /** @var Rules  */
    protected $rules;

    /** @var CategoryRepositoryInterface  */
    protected $categoryRepository;

    /**
     * Category constructor.
     * @param SampleDataContext $sampleDataContext
     * @param CategoryInterfaceFactory $categoryFactory
     * @param TreeFactory $resourceCategoryTreeFactory
     * @param StoreManagerInterface $storeManager
     * @param StoreInterfaceFactory $storeFactory
     * @param BlockInterfaceFactory $blockFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Rules $rules
     */

    public function __construct(
        SampleDataContext $sampleDataContext, CategoryInterfaceFactory $categoryFactory,
        TreeFactory $resourceCategoryTreeFactory, StoreManagerInterface $storeManager,
        StoreInterfaceFactory $storeFactory, BlockInterfaceFactory $blockFactory, CategoryRepositoryInterface $categoryRepository,
        Rules $rules
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->categoryFactory = $categoryFactory;
        $this->resourceCategoryTreeFactory = $resourceCategoryTreeFactory;
        $this->storeManager = $storeManager;
        $this->storeFactory = $storeFactory;
        $this->blockFactory = $blockFactory;
        $this->rules = $rules;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param array $fixtures
     * @throws \Exception
     */
    public function install(array $fixtures)
    {
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }
            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);
            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $this->createCategory($data);
            }
        }
    }

    /**
     * @param array $row
     * @param \Magento\Catalog\Model\Category $category
     * @return void
     */
    protected function setAdditionalData($row, $category)
    {
        $additionalAttributes = [
            'position',
            'display_mode',
            'page_layout',
            'custom_layout_update',
            'look_book_main_image',
            'description',
            'landing_page'
        ];

        foreach ($additionalAttributes as $categoryAttribute) {
            if (!empty($row[$categoryAttribute])) {
                if($categoryAttribute == 'landing_page'){
                    $attributeData = [$categoryAttribute => $this->getCmsBlockId($row[$categoryAttribute])];
                }else {
                    $attributeData = [$categoryAttribute => $row[$categoryAttribute]];
                }
                $category->addData($attributeData);

            }
        }
    }

    /**
     * Get category name by path
     *
     * @param string $path
     * @return \Magento\Framework\Data\Tree\Node
     */
    protected function getCategoryByPath($path,$storeIdentifier)
    {
        $store = $this->storeFactory->create();
        $store->load($storeIdentifier);
        $rootCatId = $store->getGroup()->getDefaultStore()->getRootCategoryId();
        $names = array_filter(explode('/', $path));
        $tree = $this->getTree($rootCatId);
        foreach ($names as $name) {
            $tree = $this->findTreeChild($tree, $name);
            if (!$tree) {
                $tree = $this->findTreeChild($this->getTree($rootCatId, true), $name);
            }
            if (!$tree) {
                break;
            }
        }
        return $tree;
    }

    /**
     * Get child categories
     *
     * @param \Magento\Framework\Data\Tree\Node $tree
     * @param string $name
     * @return mixed
     */
    protected function findTreeChild($tree, $name)
    {
        $foundChild = null;
        if ($name) {
            foreach ($tree->getChildren() as $child) {
                if ($child->getName() == $name) {
                    $foundChild = $child;
                    break;
                }
            }
        }
        return $foundChild;
    }

    /**
     * Get category tree
     *
     * @param int|null $rootNode
     * @param bool $reload
     * @return \Magento\Framework\Data\Tree\Node
     */
    protected function getTree($rootNode = null, $reload = false)
    {
        if (!$this->categoryTree || $reload) {
            if ($rootNode === null) {
                $rootNode = $this->storeManager->getDefaultStoreView()->getRootCategoryId();
            }

            $tree = $this->resourceCategoryTreeFactory->create();
            $node = $tree->loadNode($rootNode)->loadChildren();

            $tree->addCollectionData(null, false, $rootNode);

            $this->categoryTree = $node;
        }
        return $this->categoryTree;
    }

    /**
     * @param array $row
     * @return void
     */
    protected function createCategory($row)
    {
        $category = $this->getCategoryByPath($row['path'] . '/' . $row['name'],$row['store']);
        if (!$category) {
            $parentCategory = $this->getCategoryByPath($row['path'],$row['store']);
            $data = [
                'parent_id' => $parentCategory->getId(),
                'name' => $row['name'],
                'is_active' => $row['active'],
                'is_anchor' => $row['is_anchor'],
                'include_in_menu' => $row['include_in_menu'],
                'url_key' => $row['url_key'],
                'store_id' => 0
            ];
            /** @var CategoryInterface $category */
            $category = $this->categoryFactory->create();
            $category->setData($data)
                ->setPath($parentCategory->getData('path'))
                ->setAttributeSetId($category->getDefaultAttributeSetId());
            $this->setAdditionalData($row, $category);
            $category->save();
//            $categoryId = $category->getId();
//            //set Visual Merch conditions
//            if($row['conditions_serialized']!=''){
//                $rule = $this->rules->loadByCategory($category);
//                $rule->setData([
//                    'rule_id' => $rule->getId(),
//                    'category_id' => $category->getId(),
//                    'is_active' => '1',
//                    'conditions_serialized' => $row['conditions_serialized']
//                ]);
//                $rule->save();
//            }
            //second save is to trigger rule to run to  populate category
           // echo("save category\n");
            //$updatedCategory = $this->categoryRepository->get($categoryId);
            //$this->categoryRepository->save($updatedCategory);

        }
    }
    /**
     * @param string $blockName
     * @return int
     */
    protected function getCmsBlockId($blockName)
    {
        $block = $this->blockFactory->create();
        $block->load($blockName, 'identifier');
        return $block->getId();

    }
}
