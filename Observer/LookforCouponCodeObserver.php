<?php
declare(strict_types=1);

namespace Iop\Coupon\Observer;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\ObserverInterface;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;
use Iop\Coupon\Service\GetCurrentCouponService;

/**
 * Class LookforCouponCodeObserver
 */
class LookforCouponCodeObserver implements ObserverInterface
{
    const URL_COUPON_PARAM = 'cpn';

    /**
     * @var ActionFlag
     */
    protected $_actionFlag;

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
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var GetCurrentCouponService
     */
    private $couponService;

    /**
     * LookforCouponCodeObserver constructor.
     * @param ActionFlag $actionFlag
     * @param RedirectInterface $redirect
     * @param ManagerInterface $messageManager
     * @param CustomerCart $cart
     * @param \Magento\Framework\Escaper $escaper
     * @param SessionManagerInterface $sessionManager
     * @param CartRepositoryInterface $quoteRepository
     * @param LoggerInterface $logger
     * @param GetCurrentCouponService $couponService
     */
    public function __construct(
        ActionFlag $actionFlag,
        RedirectInterface $redirect,
        ManagerInterface $messageManager,
        CustomerCart $cart,
        \Magento\Framework\Escaper $escaper,
        SessionManagerInterface $sessionManager,
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger,
        GetCurrentCouponService $couponService
    ) {
        $this->cart = $cart;
        $this->messageManager = $messageManager;
        $this->escaper = $escaper;
        $this->_actionFlag = $actionFlag;
        $this->redirect = $redirect;
        $this->sessionManager = $sessionManager;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->couponService = $couponService;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $couponCode = (string)$observer->getEvent()->getRequest()->getParam(self::URL_COUPON_PARAM, '');

        if (!empty($couponCode)) {
            try {
                if ($this->couponService->isCouponCodeValid($couponCode)) {

                    /** save coupon code to the session */
                    $this->sessionManager->setFrontendCouponCode($couponCode);

                    $cartQuote = $this->cart->getQuote();
                    $itemsCount = $cartQuote->getItemsCount();
                    if ($itemsCount) {
                        $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                        $cartQuote->setCouponCode($couponCode)->collectTotals();
                        $this->quoteRepository->save($cartQuote);

                        $this->messageManager->addSuccessMessage(
                            __(
                                'Coupon code "%1" is applied.',
                                $this->escaper->escapeHtml($couponCode)
                            )
                        );
                    } else {
                        $this->messageManager->addSuccessMessage(
                            __(
                                'Your coupon code "%1" is ready to apply. Add item(s) to the shopping cart to process.',
                                $this->escaper->escapeHtml($couponCode)
                            )
                        );
                    }
                } else {
                    $this->messageManager->addErrorMessage(
                        __(
                            'The coupon code "%1" is not valid.',
                            $this->escaper->escapeHtml($couponCode)
                        )
                    );
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('We cannot apply the coupon code.') . $e->getMessage());
            }

            $controller = $observer->getControllerAction();
            $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
            $this->redirect->redirect($controller->getResponse(), 'checkout/cart/index');
        }
    }
}
