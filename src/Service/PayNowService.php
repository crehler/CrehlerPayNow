<?php

namespace Crehler\PayNowPayment\Service;


use Monolog\Logger;
use Paynow\Client;
use Paynow\Service\Payment;
use Crehler\PayNowPayment\Common\Serializer;
use Crehler\PayNowPayment\Factory\TransactionDtoFactory;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;


class PayNowService implements AsynchronousPaymentHandlerInterface

{
    const PAYNOW_PAYMENT_ID = 'paynowPaymentId';
    private Logger $logger;
    private Client $client;
    private TransactionDtoFactory $transactionDtoFactory;
    private EntityRepositoryInterface $transactionRepository;


    public function __construct(
        Logger $logger,
        Client $client,
        TransactionDtoFactory $transactionDtoFactory,
        EntityRepositoryInterface $transactionRepository
    ) {
        $this->transactionDtoFactory = $transactionDtoFactory;
        $this->logger = $logger;
        $this->client = $client;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $customerBankId = null;
        if(isset($salesChannelContext->getCustomer()->getCustomFields()['pay_now_default_payment_selected_bank'])){
            $customerBankId = $salesChannelContext->getCustomer()->getCustomFields()['pay_now_default_payment_selected_bank'];
        }

        $transactionDto = $this->transactionDtoFactory->createTransactionDto($transaction->getOrder(), $transaction->getReturnUrl(), $customerBankId, $transaction->getOrderTransaction()->getId());

        $normalizedTransaction = Serializer::getSerializer()->normalize($transactionDto, 'json');

        try{
            $payment = new Payment($this->client);
            $result = $payment->authorize($normalizedTransaction, $transaction->getOrderTransaction()->getId());
        }catch(\Throwable $exception){
            $this->logger->error('MESSAGE ' . $exception->getMessage() . ' CODE ' . $exception->getCode());
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $exception->getMessage()
            );
        }

        $data = $result->getPaymentId();

        $this->transactionRepository->upsert([[
            'id' => $transaction->getOrderTransaction()->getId(),
            'customFields' => [
                self::PAYNOW_PAYMENT_ID => $data
            ]
        ]], $salesChannelContext->getContext());

        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            $redirectUrl = $this->sendReturnUrlToExternalGateway($result->getRedirectUrl());
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }

        // Redirect to external gateway
        return new RedirectResponse($redirectUrl);
    }

    /**
     * @throws CustomerCanceledAsyncPaymentException
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {

    }

    private function sendReturnUrlToExternalGateway(string $getReturnUrl): string
    {
        $paymentProviderUrl = $getReturnUrl;

        // Do some API Call to your payment provider
        return $paymentProviderUrl;
    }

}
