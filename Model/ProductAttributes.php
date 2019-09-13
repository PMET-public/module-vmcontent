<?php

namespace MagentoEse\VMContent\Model;


use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use MagentoEse\VMContent\Model\SetSession;

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

    /**
     * ProductAttributes constructor.
     * @param SampleDataContext $sampleDataContext
     * @param ProductRepositoryInterface $productRepository
     * @param ReplaceIds $replaceIds
     * @param SetSession $setSession
     */
    public function __construct( SampleDataContext $sampleDataContext,
                                 ProductRepositoryInterface $productRepository,
                                 ReplaceIds $replaceIds,
                                 SetSession $setSession)
    {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->productRepository = $productRepository;
        $this->replaceIds = $replaceIds;
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

                $product = $this->productRepository->get($row['sku']);
                $product->setCustomAttribute($row['attribute'],$this->replaceIds->getAttributeOptionValueByCode($row['attribute'],$row['value']));
                //$product->setCustomAttribute($row['attribute'],'6');
                $this->productRepository->save($product);
            }
        }
    }
}