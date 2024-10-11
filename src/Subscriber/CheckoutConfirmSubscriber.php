<?php

namespace Crehler\PayNowPayment\Subscriber;


use Crehler\PayNowPayment\Controller\PaymentMethods\AbstractRetrieveController;
use Crehler\PayNowPayment\Controller\PaymentMethods\RetrieverController;
use Crehler\PayNowPayment\Service\PayNowService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class CheckoutConfirmSubscriber implements EventSubscriberInterface
{
    protected RetrieverController $paymentMethods;

    protected SystemConfigService $systemConfigService;

    public function __construct(
        AbstractRetrieveController $paymentMethods,
        SystemConfigService        $systemConfigService
    )
    {
        $this->paymentMethods = $paymentMethods;
        $this->systemConfigService = $systemConfigService;

    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'addPaymentMethods',
            AccountEditOrderPageLoadedEvent::class => 'addPaymentMethodsOnEdit'
        ];
    }

    public function addPaymentMethods(CheckoutConfirmPageLoadedEvent $event): void
    {
        $payNowHandler = PayNowService::class;
        $payments = $event->getPage()->getPaymentMethods();
        if ($this->systemConfigService->get('CrehlerPayNowPayment.config.EnableLevelZero')) {
            $newPaymentMethods = $this->paymentMethods->load($event->getSalesChannelContext());
            foreach ($payments as $payment) {
                if ($payment->getHandlerIdentifier() === $payNowHandler) {
                    $payment->addExtension('payNowBankList', $newPaymentMethods->getResult());
                }
            }
        }
    }

    public function addPaymentMethodsOnEdit(AccountEditOrderPageLoadedEvent $event): void
    {
        $payNowHandler = PayNowService::class;
        $payments = $event->getPage()->getPaymentMethods();
        if ($this->systemConfigService->get('CrehlerPayNowPayment.config.EnableLevelZero')) {
            $newPaymentMethods = $this->paymentMethods->load($event->getSalesChannelContext());
            foreach ($payments as $payment) {
                if ($payment->getHandlerIdentifier() === $payNowHandler) {
                    $payment->addExtension('payNowBankList', $newPaymentMethods->getResult());
                }
            }
        }
    }
}