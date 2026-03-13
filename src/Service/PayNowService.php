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
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PayNowService extends AbstractPaymentHandler
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

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return false;
    }

    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
    {
        try {
            $orderTransaction = $this->getOrderTransaction($transaction->getOrderTransactionId(), $context);

            $idempotencyKey = $this->idempotencyKeyGenerator->generate($orderTransaction, $context);
            $order = $orderTransaction->getOrder();

            $customer = $order->getOrderCustomer();

            $customerBankId = null;
            if ($customer && isset($customer->getCustomFields()['pay_now_default_payment_selected_bank'])) {
                $customerBankId = $customer->getCustomFields()['pay_now_default_payment_selected_bank'];
            }

            $transactionDto = $this->transactionDtoFactory->createTransactionDto($order, $transaction->getReturnUrl(), $customerBankId, $transaction->getOrderTransactionId());
            $normalizedTransaction = Serializer::getSerializer()->normalize($transactionDto, 'json');

            $requestEvent = new PaymentAuthorizeRequestEvent($transaction, $context, $this->payment->getClient(), $normalizedTransaction, $idempotencyKey);
            $this->eventDispatcher->dispatch($requestEvent);
            $result = $this->payment->authorize($requestEvent->getData(), $idempotencyKey);

            $this->eventDispatcher->dispatch(new PaymentAuthorizeResponseEvent($transaction, $context, $result));

            $data = $result->getPaymentId();

            $this->orderTransactionRepository->upsert([[
                'id' => $transaction->getOrderTransactionId(),
                'customFields' => [
                    self::PAYNOW_PAYMENT_ID => $data
                ]
            ]], $context);

            return new RedirectResponse($result->getRedirectUrl());
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf('Error (%s) registering the transaction for transaction %s: %s', (string)$exception->getCode(), $transaction->getOrderTransactionId(), $exception->getMessage()),
                ['exception' => $exception]
            );
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransactionId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $exception->getMessage()
            );
        }
    }

    /**
     * This method is called after redirect from PayNow payment provider
     */
    public function finalize(Request $request, PaymentTransactionStruct $transaction, Context $context): void
    {
        if ($request->query->getBoolean('cancel') || $request->query->get('error')) {
            throw PaymentException::customerCanceled(
                $transaction->getOrderTransactionId(),
                'Customer canceled the payment on the PayNow page'
            );
        }
    }

    /**
     * Helper method to fetch order data from transaction ID
     */
    private function getOrderTransaction(string $orderTransactionId, Context $context): OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociation('order.orderCustomer');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('order.addresses');
        $criteria->addAssociation('order.currency');

        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if (!$orderTransaction) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'Order transaction not found'
            );
        }

        return $orderTransaction;
    }
}
