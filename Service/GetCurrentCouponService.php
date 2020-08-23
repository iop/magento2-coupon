<?php
declare(strict_types=1);

namespace Iop\Coupon\Service;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class GetCurrentCouponService
 */
class GetCurrentCouponService
{
    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;
    /**
     * @var CouponRepositoryInterface
     */
    private $couponRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GetCurrentCouponService constructor.
     * @param RuleRepositoryInterface $ruleRepository
     * @param CouponRepositoryInterface $couponRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        RuleRepositoryInterface $ruleRepository,
        CouponRepositoryInterface $couponRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->couponRepository = $couponRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * @param string $couponCode
     * @return bool
     */
    public function isCouponCodeValid(string $couponCode): bool
    {
        $isValid = false;

        $codeLength = strlen($couponCode);
        $isCodeLengthValid = $codeLength && $codeLength <= \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;
        if (!$isCodeLengthValid) {
            return $isValid;
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('code', $couponCode)->create();

        try {
            $couponList = $this->couponRepository->getList($searchCriteria);
            if ($couponList->getTotalCount()) {
                $couponData = $couponList->getItems();
                foreach ($couponData as $coupon) {
                    /** \Magento\SalesRule\Api\Data\CouponInterface $coupon */
                    if ($this->isRuleValid($coupon)) {
                        return $couponId = $coupon->getCouponId() ? true : false;
                    }
                }
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $isValid;
    }

    /**
     * @param CouponInterface $coupon
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isRuleValid(CouponInterface $coupon): bool
    {
        $rule = $this->ruleRepository->getById($coupon->getRuleId());

        if ($rule->getRuleId()) {

            $ruleFromDate = $rule->getFromDate();
            $ruleExpireDate = $rule->getToDate();

            $qtyTimesUses = $coupon->getTimesUsed();
            $usageLimit = $coupon->getUsageLimit();

            if ($ruleExpireDate && strtotime($ruleExpireDate) < strtotime(date("Y-m-d"))) {
                return false;
            } elseif ($ruleFromDate && strtotime($ruleFromDate) > strtotime(date("Y-m-d"))) {
                return false;
            } elseif ($usageLimit && $qtyTimesUses > $usageLimit) {
                return false;
            }

            return true;
        }

        return false;
    }
}
