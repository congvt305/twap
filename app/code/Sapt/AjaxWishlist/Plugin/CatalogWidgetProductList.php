<?php


namespace Sapt\AjaxWishlist\Plugin;


use Magento\Store\Model\StoreManagerInterface;
use Sapt\AjaxWishlist\ViewModel\AjaxWishlistStatus;

class CatalogWidgetProductList
{
    const TW_LNG_STORE_CODE = 'tw_laneige';
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var AjaxWishlistStatus
     */
    private $viewModel;

    public function __construct(
        AjaxWishlistStatus $viewModel,
        StoreManagerInterface $storeManager
    ) {
        $this->viewModel = $viewModel;
        $this->storeManager = $storeManager;
    }

    public function beforeToHtml(
        \Magento\CatalogWidget\Block\Product\ProductsList $block
    ) {
        if ($this->storeManager->getStore()->getCode() == self::TW_LNG_STORE_CODE) {
            $block->setData('wishlistStatusViewModel', $this->viewModel);
        }
        return [];
    }
}
