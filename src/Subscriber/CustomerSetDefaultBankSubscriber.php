<?php

namespace Crehler\PayNowPayment\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextSwitchEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Crehler\PayNowPayment\CrehlerPayNowPayment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;



class CustomerSetDefaultBankSubscriber implements EventSubscriberInterface
{

    /** @var EntityRepository */
    private $customerRepository;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(EntityRepository $customerRepository, RequestStack $requestStack)
    {
        $this->customerRepository = $customerRepository;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SalesChannelContextSwitchEvent::class => 'onSalesChannelContextSwitch'
        ];
    }

    public function onSalesChannelContextSwitch(SalesChannelContextSwitchEvent $event): void
    {
        $context = $event->getContext();
        $customer = $event->getSalesChannelContext()->getCustomer();
        $selectedBankId = $event->getRequestDataBag()->getInt('payNowBank');

        if (empty($selectedBankId)) {
            $selectedBankId = (int) $this->requestStack->getCurrentRequest()->get('payNowBank');
        }

        if ($customer === null || empty($selectedBankId)) {
            return;
        }

        $this->customerRepository->update([
            [
                'id' => $customer->getId(),
                'customFields' => [CrehlerPayNowPayment::CUSTOMER_CUSTOM_FIELDS_PAY_NOW_SELECTED_BANK => $selectedBankId]
            ]
        ], $context);
    }




}