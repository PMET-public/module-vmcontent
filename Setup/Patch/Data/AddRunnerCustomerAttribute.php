<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\VMContent\Model\CustomerAttributes;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;

class AddRunnerCustomerAttribute implements DataPatchInterface
{

    /** @var CustomerAttributes  */
    private $customerAttributes;

    /** @var CustomerRepositoryInterface  */
    private $customerRepository;

    /** @var Customer  */
    private $customer;

    /** @var CustomerFactory  */
    private $customerFactory;

    /** @var AttributeRepositoryInterface  */
    private $attributeRepository;

    /**
     * AddRunnerCustomerAttribute constructor.
     * @param CustomerAttributes $customerAttributes
     * @param CustomerRepositoryInterface $customerRepository
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(CustomerAttributes $customerAttributes,
                                CustomerRepositoryInterface $customerRepository,
                                Customer $customer,
                                CustomerFactory $customerFactory,
                                AttributeRepositoryInterface $attributeRepository)
    {

        $this->customerAttributes = $customerAttributes;
        $this->customerRepository = $customerRepository;
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
        $this->attributeRepository = $attributeRepository;
    }

    public function apply()
    {
        echo "installing " , get_class($this) , "\n";
        $useInForms=['adminhtml_customer','adminhtml_checkout','customer_account_edit','customer_account_create'];
        $attributeOptions = ['Running','Crossfit','Pilates','Yoga'];
        $attributeCode = 'preferred_activities';
        $mainSettings = [
            'type'         => 'varchar',
            'label'        => 'Preferred Activities',
            'input'        => 'multiselect',
            'required'     => 0,
            'visible'      => 1,
            'is_used_in_grid' => 1,
            'is_filterable_in_grid' => 1,
            'user_defined' => 1,
            'position'     => 100,
            'system'       => 0,
            'multiline_count' => 1,
            'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table'
        ];

        $this->customerAttributes->install($attributeCode,$mainSettings, $useInForms, $attributeOptions, 1);
        $this->setCustomerAttribute('runner@thelumastory.com',$attributeCode,'Running');
    }

    public function setCustomerAttribute($email,$attributeCode,$value){
        //getCustomer
        $customer = $this->customerRepository->get($email);
        $id = $this->getOptionCode($attributeCode,$value);
        $customer->setCustomAttribute($attributeCode,$id);
        $this->customerRepository->save($customer);
    }
    public function getOptionCode($attributeCode,$textValue){
        $attribute = $this->attributeRepository->get(Customer::ENTITY,$attributeCode);
        $options = $attribute->getOptions();
        /** @var AttributeOptionInterfaceFactory $option */
        foreach($options as $option){
            if($option->getLabel()==$textValue){
                return $option->getValue();
                break;
            }
        }
    }

    public static function getDependencies()
    {
        return [AddCustomBrand::class];
    }

    public function getAliases()
    {
        return [];
    }
}