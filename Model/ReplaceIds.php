<?php


namespace MagentoEse\VMContent\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollection;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory as SegmentCollection;
use Magento\CustomerSegment\Model\Segment;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Banner\Model\ResourceModel\Banner\CollectionFactory as BannerCollection;

class ReplaceIds
{

    /** @var  AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var BlockRepositoryInterface  */
    private $blockRepository;

    /** @var SegmentCollection  */
    private $segmentCollection;

    /** @var AttributeSetRepositoryInterface  */
    private $attributeSetRepository;

    /** @var CategoryCollection  */
    private $categoryCollection;

    /** @var GroupRepositoryInterface  */
    private $groupRepository;

    /** @var StoreRepositoryInterface  */
    private $storeRepository;

    /** @var BannerCollection  */
    private $bannerCollection;

    /**
     * ReplaceIds constructor.
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param BlockRepositoryInterface $blockRepository
     * @param SegmentCollection $segmentCollection
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param CategoryCollection $categoryCollection
     * @param GroupRepositoryInterface $groupRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param BannerCollection $bannerCollection
     */
    public function __construct(AttributeRepositoryInterface $attributeRepository,
                                    SearchCriteriaBuilder $searchCriteriaBuilder,
                                    BlockRepositoryInterface $blockRepository,
                                    SegmentCollection $segmentCollection,
                                    AttributeSetRepositoryInterface $attributeSetRepository,
                                    CategoryCollection $categoryCollection,
                                    GroupRepositoryInterface $groupRepository,
                                    StoreRepositoryInterface $storeRepository,
                                    BannerCollection $bannerCollection){
        $this->attributeRepository = $attributeRepository;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->blockRepository = $blockRepository;
        $this->segmentCollection = $segmentCollection;
        $this->categoryCollection = $categoryCollection;
        $this->groupRepository = $groupRepository;
        $this->storeRepository = $storeRepository;
        $this->bannerCollection = $bannerCollection;
    }


    public function replaceAll($content){
        $content = $this->replaceSegments($content);
        $content = $this->replaceProductAttributes($content);
        $content = $this->replaceCustomerAttributes($content);
        $content = $this->replaceBlocks($content);
        $content = $this->replaceAttributeSets($content);
        $content = $this->replaceCategories($content);
        $content = $this->replaceDynamicBlocks($content);
        $content = $this->replaceCategories($content);
        $content = $this->replaceCustomerGroups($content);
        return $content;
    }

    public function replaceSegments($content){
        /* use _segment_segmentname as token */
        $segments = $this->segmentCollection->create()->getItems();
       /** @var Segment $segment */
        foreach($segments as $segment){
            $content = str_replace("_segment_".$segment->getName(),$segment->getId(),$content);
       }
        return $content;
    }

    public function replaceCustomerAttributes($content){
        /* use _customerAttribute_attributecode_value as token */
        $search = $this->searchCriteriaBuilder
            ->addFilter(AttributeInterface::ATTRIBUTE_ID,'','neq')->create();
        $attributeList = $this->attributeRepository->getList(Customer::ENTITY,$search)->getItems();
        //replace attribute code with current Id
        foreach($attributeList as $attribute){
            $attributeOptions = $attribute->getOptions();
            foreach($attributeOptions as $attributeOption){
                if(is_string($attributeOption->getValue())){
                    $content = str_replace("_customerAttribute_".$attribute->getAttributeCode()."_".$attributeOption->getLabel(),$attributeOption->getValue(),$content);
                }

            }

        }
        return $content;
    }

    public function replaceProductAttributes($content){
        /* use _productAttribute_attributecode_value as token */
        $search = $this->searchCriteriaBuilder
            ->addFilter(AttributeInterface::ATTRIBUTE_ID,'','neq')->create();
        $attributeList = $this->attributeRepository->getList(Product::ENTITY,$search)->getItems();
        //replace attribute code with current Id
        foreach($attributeList as $attribute){
            $attributeOptions = $attribute->getOptions();
            foreach($attributeOptions as $attributeOption){
                if(is_string($attributeOption->getValue())){
                   $content = str_replace("_productAttribute_".$attribute->getAttributeCode()."_".$attributeOption->getLabel(),$attributeOption->getValue(),$content);
                }

            }
        }
        return $content;
    }


    public function replaceBlocks($content){
        /* use _block_indentifier as token */
        $search = $this->searchCriteriaBuilder
            ->addFilter(BlockInterface::IDENTIFIER,'','neq')->create();
        $blocklist = $this->blockRepository->getList($search)->getItems();
        foreach($blocklist as $block){
            $content = str_replace("_block_".$block->getIdentifier(),$block->getId(),$content);
        }
        return $content;
    }

    public function replaceDynamicBlocks($content){
        /* use _banner_bannername as token */
        $banners = $this->bannerCollection->create()->getItems();
        /** @var \Magento\Banner\Model\Banner $banner */
        foreach($banners as $banner){
            $content = str_replace("_banner_".$banner->getName(),$banner->getId(),$content);
        }
        return $content;
    }

    public function replaceAttributeSets($content){
        /* use _attributeSet_name as token */
        $search = $this->searchCriteriaBuilder
            ->addFilter('attribute_set_id','','neq')->create();
        $attributeSetList = $this->attributeSetRepository->getList($search)->getItems();
        foreach($attributeSetList as $attributeSet){
            $content = str_replace("_attributeSet_".$attributeSet->getAttributeSetName(),$attributeSet->getAttributeSetId(),$content);
        }
        return $content;
    }

    public function replaceCategories($content){
        /* use _category_urlkey as token */
        $categoriesList = $this->categoryCollection->create();
        $categoriesList->addAttributeToSelect("*");
        /** @var Category $category */
        foreach ($categoriesList as $category) {
            if($category->getUrlKey()!=''){
                $content = str_replace("_category_".$category->getUrlKey(),$category->getId(),$content);
            }

        }
        return $content;
    }

    public function replaceCustomerGroups($content){
        //* use _customerGroup_name as token */
        $search = $this->searchCriteriaBuilder
            ->addFilter(GroupInterface::ID,'','neq')->create();
        $groupList = $this->groupRepository->getList($search)->getItems();
        /** @var GroupInterface $group */
        foreach($groupList as $group){
            $content = str_replace("_customerGroup_".$group->getCode(),$group->getId(),$content);
        }
        return $content;
    }

    public function getSegmentIdByName($segmentName){
        $segment = $this->segmentCollection->addFilter('name',$segmentName,'eq')->getFirstItem();
        return $segment->getId();
    }

    public function getStoreidByCode($storeCode){
        return $this->storeRepository->get($storeCode)->getId();
    }

    public function getAttributeOptionValueByCode($attributeCode,$option){
        $attribute = $this->attributeRepository->get(Product::ENTITY,$attributeCode);
        $attributeOptions = $attribute->getOptions();
        foreach($attributeOptions as $attributeOption){
            if($attributeOption->getLabel()==$option){
                return $attributeOption->getValue();
            }

        }
    }
}