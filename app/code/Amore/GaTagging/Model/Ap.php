<?php
/**
 * Created by Eguana Team.
 * User: sonia
 * Date: 7/2/20
 * Time: 2:44 PM
 */

namespace Amore\GaTagging\Model;


class Ap
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @var \Amore\GaTagging\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $catalogProductHelper;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Amore\GaTagging\Helper\Data $helper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Helper\Product $catalogProductHelper
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Amore\GaTagging\Helper\Data $helper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Product $catalogProductHelper,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->catalogProductHelper = $catalogProductHelper;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param $product
     * @param $qty
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductInfo($product, $qty = null)
    {
        $productDataArr = [];
        if ($product->getTypeId() === 'bundle') {
            $productData = $this->getBundleProductInfo($product, $qty);
            foreach ($productData as $productDatum) {
                $productDataArr[] = $this->jsonSerializer->serialize($productDatum);
            }
        } elseif ($product->getTypeId() === 'configurable') {
            $productData = $this->getConfigurableProductInfo($product, $qty);
            foreach ($productData as $productDatum) {
                $productDataArr[] = $this->jsonSerializer->serialize($productDatum);
            }
        } else {
            $productData = $this->getSimpleProductInfo($product, $qty);
            $productDataArr[] = $this->jsonSerializer->serialize($productData);
        }
        return $productDataArr;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     */
    protected function getSimpleProductInfo($product, $qty=null, $rate=null)
    {
        $productInfo = [];
        $productInfo['name'] = $product->getName();
        $productInfo['code'] = $product->getSku();
        $productInfo['v2code'] = $product->getId();
        $productInfo['sapcode'] = $product->getSku();
        $productInfo['brand'] = $this->helper->getSiteName() ?? '';
        $productInfo['prdprice'] = intval($product->getPrice());
        $productInfo['price'] = intval($product->getPriceInfo()->getPrice('final_price')->getValue());
        $productInfo['variant'] = '';
        $productInfo['promotion'] = '';
        $productInfo['cate'] = $this->helper->getProductCategory($product);
        $productInfo['catecode'] = '';
        $productInfo['url'] = $product->getProductUrl();
        $productInfo['img_url'] = $this->catalogProductHelper->getThumbnailUrl($product);
        $productInfo['quantity'] = $qty ? intval($qty) : 0;
        if ($rate) {
            $productInfo['rate'] = $rate;
        }
        return $productInfo;
    }

    /**
     * @param $product
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getBundleProductInfo($product, $qty = null)
    {
        $productInfos = [];
        $productInfo = [];
        $productInfo['name'] = $product->getName();
        $productInfo['code'] = $product->getSku();
        $productInfo['v2code'] = $product->getId();
        $productInfo['sapcode'] = $product->getSku();
        $productInfo['brand'] = $this->helper->getSiteName() ?? '';
        $productInfo['prdprice'] = $this->getProductOriginalPrice($product);
        $productInfo['variant'] = '';
        $productInfo['promotion'] = '';
        $productInfo['cate'] = $this->helper->getProductCategory($product);
        $productInfo['catecode'] = '';
        $productInfo['price'] = $this->getProductDiscountedPrice($product);
        $productInfo['url'] = $product->getProductUrl();
        $productInfo['img_url'] = $this->catalogProductHelper->getThumbnailUrl($product);
        $productInfo['quantity'] = $qty ? intval($qty) : 0;
        $productInfos[] = $productInfo;
        return $productInfos;
    }

    /**
     * @param $product
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getConfigurableProductInfo($product, $qty = null)
    {
        $productInfos = [];
        $productInfo = [];
        $productInfo['name'] = $product->getName();
        $productInfo['code'] = $product->getSku();
        $productInfo['v2code'] = $product->getId();
        $productInfo['sapcode'] = $product->getSku();
        $productInfo['brand'] = $this->helper->getSiteName() ?? '';
        $productInfo['prdprice'] = $this->getProductOriginalPrice($product);
        $productInfo['variant'] = '';
        $productInfo['promotion'] = '';
        $productInfo['cate'] = $this->helper->getProductCategory($product);
        $productInfo['catecode'] = '';
        $productInfo['price'] = $this->getProductDiscountedPrice($product);
        $productInfo['url'] = $product->getProductUrl();
        $productInfo['img_url'] = $this->catalogProductHelper->getThumbnailUrl($product);
        $productInfo['quantity'] = $qty ? intval($qty) : 0;
        $productInfos[] = $productInfo;
        return $productInfos;
    }

    /**
     * @param $currentProduct
     * @return mixed
     */
    public function getProductOriginalPrice($currentProduct)
    {
        $productType = $currentProduct->getTypeId();
        if ($productType == 'bundle') {
            $originalPrice = intval($currentProduct->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue());
        } else if ($productType == 'configurable') {
            $originalPrice = intval($currentProduct->getPriceInfo()->getPrice('regular_price')->getMinRegularAmount()->getValue());
        } else {
            $originalPrice = intval($currentProduct->getPrice());
        }

        return $originalPrice;
    }

    /**
     * @param $currentProduct
     * @return float|mixed
     */
    public function getProductDiscountedPrice($currentProduct)
    {
        $productType = $currentProduct->getTypeId();
        if ($productType == 'bundle') {
            $price = intval($currentProduct->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue());
        } elseif ($productType == 'configurable'){
            $price = intval($currentProduct->getPriceInfo()->getPrice('final_price')->getValue());
        } else {
            $price = intval($currentProduct->getFinalPrice());
        }

        return $price;
    }

}
