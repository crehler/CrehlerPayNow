<?php


namespace Crehler\PayNowPayment\Controller\PaymentMethods;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractRetrieveController
{
    abstract public function getDecorated(): AbstractRetrieveController;

    abstract public function load(SalesChannelContext $context): PaymentResponse;
}




