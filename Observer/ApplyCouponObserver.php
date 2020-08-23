<?php
declare(strict_types=1);

namespace Iop\Coupon\Observer;

use Exception;
use Iop\Coupon\Service\GetCurrentCouponService;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class ApplyCouponObserver
 */
class ApplyCouponObserver implements ObserverInterface
{
    /**
     * Sales quote repository
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var GetCurrentCouponService
     */
    private $couponService;

    /**
     * ApplyCouponObserver constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param ManagerInterface $messageManager
     * @param Escaper $escaper
     * @param SessionManagerInterface $sessionManager
     * @param GetCurrentCouponService $couponService
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        ManagerInterface $messageManager,
        Escaper $escaper,
        SessionManagerInterface $sessionManager,
        GetCurrentCouponService $couponService
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->messageManager = $messageManager;
        $this->escaper = $escaper;
        $this->sessionManager = $sessionManager;
        $this->couponService = $couponService;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $couponCode = (string)$this->sessionManager->getFrontendCouponCode();

        /** @var Magento\Checkout\Model\Cart $cart */
        $cart = $observer->getData('cart');
        $cartQuote = $cart->getQuote();

        if (!empty($couponCode)) {
            try {
                if ($this->couponService->isCouponCodeValid($couponCode)) {
                    /** apply coupon code */
                    $cartQuote->setCouponCode($couponCode);
                    $this->quoteRepository->save($cartQuote->collectTotals());
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(
                    __(
                        'We cannot apply the coupon code "%1".',
                        $this->escaper->escapeHtml($couponCode)
                    )
                );
            }
        }
    }
}
