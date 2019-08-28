<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Model;


use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\Customer;

class CustomerAttributes
{


    /** @var EavSetupFactory  */
    private $eavSetupFactory;

    /** @var Config  */
    private $eavConfig;

    /** @var AttributeRepositoryInterface  */
    private $attributeRepository;

    /**
     * CustomerAttributes constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeRepository = $attributeRepository;
    }




    public function install($attributeCode,array $mainSettings,array $useInForms,array $attributeOptions, $customerSegment)
    {
        $newAttribute = $this->eavConfig->getAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode);
        $newAttribute->setData('used_in_forms', $useInForms);
        $newAttribute->setData('is_used_for_customer_segment', $customerSegment);
        $newAttribute->save($newAttribute);

        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode, $mainSettings);
        $eavSetup->addAttributeToSet(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            null,
            $attributeCode);

        if($attributeOptions){
            $this->addOptions(0, $attributeCode, $attributeOptions);
        }

    }

    private function addOptions($store, $attributeCode, array $options){
        $attribute = $this->attributeRepository->get(Customer::ENTITY,$attributeCode);
        $option=array();
        $option['attribute_id'] = $attribute->getAttributeId();
        foreach($options as $key=>$value){
            $option['value'][$value][$store]=$value;
            //foreach($allStores as $store){
            //    $option['value'][$value][$store->getId()] = $value;
            //}
        }
        $eavSetup = $this->eavSetupFactory->create();

        $eavSetup->addAttributeOption($option);

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