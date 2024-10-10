<?php

namespace Crehler\PayNowPayment\Factory;

use Crehler\PayNowPayment\Common\OrderAmountFormat;
use Crehler\PayNowPayment\DTO\Transaction\RequestObjects\TransactionBuyerDto;
use Crehler\PayNowPayment\DTO\Transaction\RequestObjects\TransactionBuyerPhoneDto;
use Crehler\PayNowPayment\DTO\Transaction\RequestObjects\TransactionOrderDto;
use Crehler\PayNowPayment\DTO\Transaction\TransactionDto;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderEntity;
use Brick\PhoneNumber\PhoneNumber;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class TransactionDtoFactory
{
    protected EntityRepository $paynowPaymentTokensRepository;

    protected RouterInterface $router;

    public function __construct(EntityRepository $paynowPaymentTokensRepository, RouterInterface $router)
    {
        $this->paynowPaymentTokensRepository = $paynowPaymentTokensRepository;
        $this->router = $router;
    }

    public function createTransactionDto(OrderEntity $order,string $returnUrl, ?string $customerBankId, string $transactionId): TransactionDto
    {
        $customerNumber = $order->getBillingAddress()->getPhoneNumber();
        $orderCustomer = $order->getOrderCustomer();

        $buyer = new TransactionBuyerDto();
        $buyer->setEmail($orderCustomer->getEmail());
        $buyer->setFirstName($orderCustomer->getFirstName());
        $buyer->setLastName($orderCustomer->getLastName());

        $orderProducts = $order->getLineItems()?->getElements();

        $lineItemsArray = [];
        $sumOrder = 0;

        /** @var ProductEntity $product */
        foreach ($orderProducts as $product) {

            // remove promotion from the line items
            if ($product->getType() === LineItem::PROMOTION_LINE_ITEM_TYPE) {
                continue;
            }

            $productUnitPrice = $product->getCustomFields()['crehler_line_item_price']['unitPrice'];
            $productQuantity = $product->getCustomFields()['crehler_line_item_price']['quantity'];
            $sumOrder += $productUnitPrice;

            $orderItem = new TransactionOrderDto();
            $orderItem->setName($product->getLabel());
            $orderItem->setCategory($product->getType());
            $orderItem->setPrice(OrderAmountFormat::floatToInt($productUnitPrice));
            $orderItem->setQuantity($productQuantity);

            $lineItemsArray[] = $orderItem;
        }

       if ($sumOrder > $order->getAmountTotal()) {
            $lastLineItem = end($lineItemsArray);

            $lastLineItem->setPrice($lastLineItem->getPrice() - 1);
       }

        if ($sumOrder < $order->getAmountTotal()) {
            $lastLineItem = end($lineItemsArray);

            $lastLineItem->setPrice($lastLineItem->getPrice() + 1);
        }

        $transactionDto = new TransactionDto();

        if ($customerNumber) {
            $phone = $this->createPhoneNo($customerNumber, $order->getBillingAddress()->getCountry()->getIso());
            $buyer->setPhone($phone);
        }
        if ($customerBankId) {
            $transactionDto->setPaymentMethodId($customerBankId);
        }

        $url_components = (parse_url($returnUrl));
        parse_str($url_components['query'], $params);

        $returnUrl = $this->router->generate('frontend.paynow.check', ['transactionId' => $transactionId], UrlGeneratorInterface::ABSOLUTE_URL);

        $transactionDto->setOrderItems($lineItemsArray);
        $transactionDto->setAmount(OrderAmountFormat::floatToInt($order->getAmountTotal()));
        $transactionDto->setBuyer($buyer);
        $transactionDto->setContinueUrl($returnUrl);
        $transactionDto->setCurrency($order->getCurrency()->getIsoCode());
        $transactionDto->setDescription($order->getOrderNumber());
        $transactionDto->setExternalId($order->getOrderNumber());

        return $transactionDto;
    }

    private function createPhoneNo($numberCustomer, $iso): TransactionBuyerPhoneDto
    {
        $phone = new TransactionBuyerPhoneDto();

        try{
            $number = PhoneNumber::parse($numberCustomer);
            $phone->setPrefix("+" .$number->getCountryCode());
            $phone->setNumber($number->getNationalNumber());
        }catch (\Throwable $e){
            $country = PhoneNumber::getExampleNumber($iso);
            $phone->setPrefix("+" . $country->getCountryCode());
            $phone->setNumber($numberCustomer);
        }
        return $phone;
    }

    private function fixTotalAmount(OrderEntity $orderEntity, TransactionOrderDto $transactionOrderDto, int $sumPrice): void
    {
        if ($transactionOrderDto->getPrice()) {

        }
    }
}