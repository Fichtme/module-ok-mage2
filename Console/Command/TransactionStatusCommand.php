<?php
/**
 * Created by PhpStorm.
 * User: dannyvanderwaal
 * Date: 21/06/2019
 * Time: 10:27
 */

namespace Okitcom\OkLibMagento\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Okitcom\OkLibMagento\Cron\TransactionStatus;
use Magento\Quote\Api\CartRepositoryInterface;
use Okitcom\OkLibMagento\Helper\CheckoutHelper;
use Okitcom\OkLibMagento\Helper\QuoteHelper;
use \Psr\Log\LoggerInterface;

/**
 * Class TransactionStatusCommand
 * @package Okitcom\OkLibMagento\Command
 */
class TransactionStatusCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CheckoutHelper
     */
    protected $checkoutHelper;

    /**
     * @var \Okitcom\OkLibMagento\Helper\QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * TransactionStatusCommand constructor.
     *
     * @param LoggerInterface         $logger
     * @param CheckoutHelper          $checkoutHelper
     * @param QuoteHelper             $quoteHelper
     * @param CartRepositoryInterface $quoteRepository
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

        parent::__construct();
    }
    /**
     * Configuring the command
     */
    protected function configure()
    {
        $this->setName('ok:transaction-status');
        $this->setDescription('OK Betalen transaction status cron');

        parent::configure();
    }

    /**
     * Executing the command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Executing the OK Betalen transaction status cron');
        $transaction = new TransactionStatus($this->logger, $this->checkoutHelper, $this->quoteHelper, $this->quoteRepository);
        $transaction->execute();
    }
}
