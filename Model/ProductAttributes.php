<?php

namespace MagentoEse\VMContent\Model;


use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use MagentoEse\VMContent\Model\SetSession;
use Magento\Eav\Setup\EavSetup;
use Magento\Catalog\Model\Config as CatalogModel;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Catalog\Model\Product as ProductModel;

class ProductAttributes
{
    /**
     * @var \Magento\Framework\File\Csv
     */
    private $csvReader;

    /**
     * @var FixtureManager
     */
    private $fixtureManager;

    /** @var ProductRepositoryInterface  */
    private $productRepository;

    /** @var ReplaceIds  */
    private $replaceIds;

    /** @var EavSetup  */
    private $eavSetup;

    /** @var CatalogModel  */
    private $catalogModel;

    /** @var AttributeManagementInterface  */
    private $attributeManagement;

    /**
     * ProductAttributes constructor.
     * @param SampleDataContext $sampleDataContext
     * @param ProductRepositoryInterface $productRepository
     * @param ReplaceIds $replaceIds
     * @param \MagentoEse\VMContent\Model\SetSession $setSession
     * @param EavSetup $eavSetup
     * @param CatalogModel $catalogModel
     * @param AttributeManagementInterface $attributeManagement
     */
    public function __construct( SampleDataContext $sampleDataContext,
                                 ProductRepositoryInterface $productRepository,
                                 ReplaceIds $replaceIds,
                                 SetSession $setSession, EavSetup $eavSetup, CatalogModel $catalogModel,
                                 AttributeManagementInterface $attributeManagement
            )
    {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->productRepository = $productRepository;
        $this->replaceIds = $replaceIds;
        $this->eavSetup = $eavSetup;
        $this->catalogModel = $catalogModel;
        $this->attributeManagement = $attributeManagement;
    }

    public function install(array $fixtures){
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                throw new \Exception('File not found: ' . $fileName);
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;

                try{
                    $product = $this->productRepository->get($row['sku']);
                    $product->setCustomAttribute($row['attribute'],$this->replaceIds->getAttributeOptionValueByCode($row['attribute'],$row['value']));
                    //$product->setCustomAttribute($row['attribute'],'6');
                    $this->productRepository->save($product);
                }catch(\Exception $exception){
                    //skip if product doesnt exist
                }

            }
        }
    }
    public function addAttributeToSet($attributeCode, $attributeSet, $attributeGroup,$sortOrder)
    {
        $entityTypeId = $this->eavSetup->getEntityTypeId(ProductModel::ENTITY);
        $attributeSet = $this->eavSetup->getAttributeSet($entityTypeId, $attributeSet);
        if (isset($attributeSet['attribute_set_id'])) {
            $group_id = $this->catalogModel->getAttributeGroupId($attributeSet['attribute_set_id'], $attributeGroup);
            $this->attributeManagement->assign(
                'catalog_product',
                $attributeSet['attribute_set_id'],
                $group_id,
                $attributeCode,
                $sortOrder
            );
        }
    }
}