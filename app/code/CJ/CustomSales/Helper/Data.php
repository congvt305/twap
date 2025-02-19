<?php
declare(strict_types=1);

namespace CJ\CustomSales\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Amasty\Promo\Helper\Item as PromoHelper;
use Magento\SalesRule\Model\Rule;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var PromoHelper
     */
    protected $promoHelper;

    /**
     * @param Context $context
     * @param CartRepositoryInterface $cartRepository
     * @param PromoHelper $promoHelper
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        PromoHelper $promoHelper
    ) {
        $this->cartRepository = $cartRepository;
        $this->promoHelper = $promoHelper;
        parent::__construct($context);
    }

    /**
     * Check if there is any product which is not in exclude list sku
     *
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return bool
     */
    public function isValidExcludeSkuRule($rule, $address)
    {
        $quote = $address->getQuote();
        if ($quote->getId()) {
            $listExcludeSkus = $this->getExcludeSkusOfRule($rule);
            $quoteItems = $quote->getItems(); //to avoid get collection item again
            if (!$quoteItems) {
                //there has some special case that make quote item is null
                $quoteItems = $quote->getAllVisibleItems();
            }
            foreach ($quoteItems as $item) {
                if (!$this->promoHelper->isPromoItem($item)) {
                    $productSku = $this->getProductSkuOfItem($item, $listExcludeSkus);
                    if (!in_array($productSku, $listExcludeSkus)) {
                        return true;
                    }
                }
            }

            if ($rule->getCouponType() == Rule::COUPON_TYPE_SPECIFIC) {
                $couponCode = $rule->getCouponCode();
                $curCouponCode = ltrim(preg_replace("/(,?)$couponCode/", '', $quote->getCouponCode()), ',');
                $quote->setCouponCode($curCouponCode)->collectTotals()->save();
            }
        }

        return false;
    }

    /**
     * Get list sku is exclude out of rule
     *
     * @param $rule
     * @param array $listExcludeSku
     * @return array|string[]
     */
    public function getExcludeSkusOfRule($rule)
    {
        $exludeSkus = [];
        if ($rule->getData("exclude_skus")) {
            $exludeSkus = explode(",", $rule->getData("exclude_skus"));
        }
        $exludeSkus = array_map('trim', $exludeSkus);
        return $exludeSkus;
    }

    /**
     * Get product sku from item
     *
     * @param $item
     * @return mixed
     */
    public function getProductSkuOfItem($item, $listExcludeSkus)
    {
        switch ($item->getProductType()) {
            case 'bundle':
                $productSku = $item->getProduct()->getData('sku');
                break;
            case 'simple':
                $productSku = $item->getSku();
                if ($item->getParentItemId()) {
                    if ($item->getParentItem()->getProduct()->getTypeId() == 'bundle') {
                        $productSku = $item->getParentItem()->getProduct()->getData('sku');
                    } else {
                        $productSku = $item->getProduct()->getSku();
                    }
                }
                break;
            case 'configurable':
                $parentProductSku = $item->getProduct()->getData('sku');
                $productSku = $item->getSku(); // child's sku
                if (in_array($parentProductSku, $listExcludeSkus)) {
                    $productSku = $parentProductSku;
                }
                break;
            default:
                $productSku = $item->getSku();
                break;
        }
        return $productSku;
    }
}
