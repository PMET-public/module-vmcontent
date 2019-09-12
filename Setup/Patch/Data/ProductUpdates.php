<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Setup\Patch\Data;


use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Model\Product\OptionFactory;
use MagentoEse\VMContent\Model\SetSession;

class ProductUpdates implements DataPatchInterface
{

    /** @var ProductLinkRepositoryInterface  */
    private $productLinkRepository;

    /** @var ProductRepositoryInterface  */
    private $productRepository;

    /** @var ProductCustomOptionInterface  */
    private $productCustomOption;

    /** @var OptionFactory  */
    private $optionFactory;

    public function __construct(ProductLinkRepositoryInterface $productLinkRepository,
                                ProductRepositoryInterface $productRepository, ProductCustomOptionInterface $productCustomOption,
                                OptionFactory $optionFactory, SetSession $session)
    {
        $this->productLinkRepository = $productLinkRepository;
        $this->productRepository = $productRepository;
        $this->productCustomOption = $productCustomOption;
        $this->optionFactory = $optionFactory;
    }

    public function apply()
    {
        $this->removeUpsellsFromCronusPants();

        $optionArray = [
            'title' => 'Add Your Initials',
            'type' => 'field',
            'is_require' => false,
            'sort_order' => 1,
            'price' => 25,
            'price_type' => 'percent',
            'sku' => 'monogram',
            'max_characters' => 5
        ];
        $product = $this->productRepository->get('24-MB04');
        $option = $this->optionFactory->create();
        $option->setProductId($product->getId())
            //->setStoreId($product->getStoreId())
            ->addData($optionArray);
        $option->save();
        $product->addOption($option);
        $this->productRepository->save($product);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    private function removeUpsellsFromCronusPants()
    {
        $product = $this->productRepository->get('MP12');
        /** @var ProductLinkInterface $productLinks */
        $productLinks = $this->productLinkRepository->getList($product);
        /** @var  $productLink */
        foreach ($productLinks as $productLink) {
            $this->productLinkRepository->delete($productLink);
        }
    }
}