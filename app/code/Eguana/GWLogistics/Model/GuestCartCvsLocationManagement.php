<?php
/**
 * Created by Eguana Team.
 * User: sonia
 * Date: 6/30/20
 * Time: 9:11 AM
 */

namespace Eguana\GWLogistics\Model;


use Eguana\GWLogistics\Api\Data\QuoteCvsLocationInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class GuestCartCvsLocationManagement implements \Eguana\GWLogistics\Api\GuestCartCvsLocationManagementInterface
{
    /**
     * @var \Magento\Quote\Model\ShippingAddressManagement
     */
    private $shippingAddressManagement;
    /**
     * @var \Eguana\GWLogistics\Api\QuoteCvsLocationRepositoryInterface
     */
    private $quoteCvsLocationRepository;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        \Magento\Quote\Model\ShippingAddressManagement $shippingAddressManagement,
        \Eguana\GWLogistics\Api\QuoteCvsLocationRepositoryInterface $quoteCvsLocationRepository,
        \Psr\Log\LoggerInterface $logger
    ) {

        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->quoteCvsLocationRepository = $quoteCvsLocationRepository;
        $this->logger = $logger;
    }

    /**
     * @param string $cartId
     * @param string|null $data
     * @return bool
     */
    public function selectCvsLocation(string $cartId, string $data = null): bool
    {
        $this->logger->debug('cartId: ', ['cartId' => $cartId]);
        try {
            $address = $this->shippingAddressManagement->get($cartId);
            $cvsLocation = $this->quoteCvsLocationRepository->getByAddressId($address->getId());
            $cvsLocation->setData('is_selected', true);
            $this->quoteCvsLocationRepository->save($cvsLocation);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to select or save cvs location.'), $e);
        }
        return true;
    }
}
