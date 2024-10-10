<?php


namespace Crehler\PayNowPayment\Entity;


use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class PaymentRefundHistoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PaymentRefundHistoryEntity::class;
    }

}