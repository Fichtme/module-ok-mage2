<?php

namespace Okitcom\OkLibMagento\Cron;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Okitcom\OkLibMagento\Helper\CheckoutHelper;
use Okitcom\OkLibMagento\Helper\ConfigHelper;
use Okitcom\OkLibMagento\Helper\QuoteHelper;
use Okitcom\OkLibMagento\Model\Checkout;
use \Psr\Log\LoggerInterface;

/**
 * Class TransactionStatus
 * @package Okitcom\OkLibMagento\Cron
 */
class TransactionStatus
{
    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var CheckoutHelper $checkoutHelper
     */
    protected $checkoutHelper;

    /**
     * @var \Okitcom\OkLibMagento\Helper\QuoteHelper $quoteHelper
     */
    protected $quoteHelper;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    protected $quoteRepository;

    /**
     * TransactionStatus constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Okitcom\OkLibMagento\Helper\CheckoutHelper $checkoutHelper
     * @param \Okitcom\OkLibMagento\Helper\QuoteHelper $quoteHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        LoggerInterface $logger,
        CheckoutHelper $checkoutHelper,
        QuoteHelper $quoteHelper,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->logger = $logger;
        $this->checkoutHelper = $checkoutHelper;
        $this->quoteHelper = $quoteHelper;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Update transactions
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function execute()
    {
        $this->logger->info("Running OK transaction status check");

        $okCash = $this->checkoutHelper->getCashService();
        $transactions = $this->checkoutHelper->getAllPending();
        $this->logger->info("Found " . count($transactions) . " transactions to update");

        $canceled = 0;
        $updated = 0;
        $completed = 0;
        $stillPending = 0;

        /** @var Checkout $item */
        foreach ($transactions->getItems() as $item) {
            // Get status by ID of the item
            $guid = $item->getGuid();
            $okResponse = $okCash->get($guid);

            try {
                // Check if the transaction in OK has a different status then known in the shop
                if ($okResponse != null && $okResponse->state != $item->getState()) {
                    $item->setState($okResponse->state);
                    $item->save();

                    // If the transaction in OK is completed but not completed in the shop
                    // Check the item createdAt to make sure double orders are created at the same time
                    // of making the transaction and running the cronjob
                    if (
                        $okResponse->state == ConfigHelper::STATE_CHECKOUT_SUCCESS &&
                        $this->isOlderThanMaxPendingTime($item->getCreatedAt())
                    ) {
                        $this->createOrder($item, $okResponse);
                        $completed++;
                    }

                    $updated++;
                } else {
                    // If the transaction has not been completed or has been open longer than
                    // the configured max pending time, cancel the transaction
                    if ($this->isOlderThanMaxPendingTime($item->getCreatedAt())) {
                        $okCash->cancel($guid);
                        $canceled++;
                    } else {
                        $stillPending++;
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->error("Could not update OK transaction with id " . $item->getId(), [
                    $exception->getCode(),
                    $exception->getMessage()
                ]);
            }
        }

        if ($updated > 0) {
            $this->logger->info(
                "Ran update on " . $transactions->count() . " transactions. (" . $updated . " updated, " . $completed . " completed, " . $canceled . " canceled, " . $stillPending . " still pending)"
            );
        }
    }

    /**
     * Checks if the given timestamp is older than maximum pending time configured
     *
     * @param $timestamp
     *
     * @return bool
     */
    private function isOlderThanMaxPendingTime($timestamp)
    {
        if (strtotime($timestamp) <= strtotime(ConfigHelper::MAX_PENDING_TIME)) {
            return true;
        }

        return false;
    }

    /**
     * Create an order from the checkout
     *
     * @param Checkout $checkout
     * @param $okResponse
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    private function createOrder(Checkout $checkout, $okResponse)
    {
        if ($checkout->getSalesOrderId() == null) {
            $quote = $this->quoteRepository->get($checkout->getQuoteId());
            if ($quote) {
                $order = $this->quoteHelper->createOrder($quote, $okResponse);
                if ($order) {
                    $checkout->setSalesOrderId($order->getEntityId());
                    $checkout->save();
                }
            }
        }
    }
}
