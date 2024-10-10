<?php

namespace Crehler\PayNowPayment\Service;


use Crehler\PayNowPayment\Event\PaymentAuthorizeRequestEvent;
use Crehler\PayNowPayment\Event\PaymentAuthorizeResponseEvent;
use Monolog\Logger;
use Paynow\Service\Payment;
use Crehler\PayNowPayment\Common\Serializer;
use Crehler\PayNowPayment\Factory\TransactionDtoFactory;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;


#[Autoconfigure(tags: [['name' => 'shopware.payment.method.async']])]
class PayNowService implements AsynchronousPaymentHandlerInterface

{
    const PAYNOW_PAYMENT_ID = 'paynowPaymentId';

    private $payment;

    public function __construct(
        private readonly LoggerInterface          $logger,
        private readonly PayNowServicesFactory    $factory,
        private readonly TransactionDtoFactory    $transactionDtoFactory,
        private readonly EntityRepository         $orderTransactionRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly IdempotencyKeyGenerator  $idempotencyKeyGenerator,
    )
    {
        $this->payment = $factory->factorPayment();
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $idempotencyKey = $this->idempotencyKeyGenerator->generate($transaction->getOrderTransaction(), $salesChannelContext->getContext());
        $customerBankId = null;
        if (isset($salesChannelContext->getCustomer()->getCustomFields()['pay_now_default_payment_selected_bank'])) {
            $customerBankId = $salesChannelContext->getCustomer()->getCustomFields()['pay_now_default_payment_selected_bank'];
        }

        $transactionDto = $this->transactionDtoFactory->createTransactionDto($transaction->getOrder(), $transaction->getReturnUrl(), $customerBankId, $transaction->getOrderTransaction()->getId());
        $normalizedTransaction = Serializer::getSerializer()->normalize($transactionDto, 'json');

        try {
            $this->eventDispatcher->dispatch(new PaymentAuthorizeRequestEvent($transaction, $salesChannelContext, $this->payment->getClient(), $normalizedTransaction, $transaction->getOrderTransaction()->getId()));
            $result = $this->payment->authorize($normalizedTransaction, $idempotencyKey);
        } catch (\Throwable $exception) {
            $this->logger->error("Error (" . $exception->getCode() . ") registering the transaction for order " . $transaction->getOrder()->getOrderNumber() . "  " . $exception->getMessage());
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $exception->getMessage()
            );
        }

        $this->eventDispatcher->dispatch(new PaymentAuthorizeResponseEvent($transaction, $salesChannelContext, $result));

        $data = $result->getPaymentId();

        $this->orderTransactionRepository->upsert([[
            'id' => $transaction->getOrderTransaction()->getId(),
            'customFields' => [
                self::PAYNOW_PAYMENT_ID => $data
            ]
        ]], $salesChannelContext->getContext());

        return new RedirectResponse($result->getRedirectUrl());
    }

    /**
     * @throws CustomerCanceledAsyncPaymentException
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {

    }
}
