<?php
declare(strict_types=1);

namespace Iop\Coupon\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class ClearCouponObserver
 */
class ClearCouponObserver implements ObserverInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * ClearCouponObserver constructor.
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        SessionManagerInterface $sessionManager
    ) {
        $this->sessionManager = $sessionManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $couponCode = (string)$this->sessionManager->getFrontendCouponCode();

        if ($couponCode) {
            $this->sessionManager->setFrontendCouponCode('');
        }
    }
}
