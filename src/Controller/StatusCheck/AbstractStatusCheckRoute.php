<?php


namespace Crehler\PayNowPayment\Controller\StatusCheck;


use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;


abstract class AbstractStatusCheckRoute
{
    abstract public function getDecorated(): AbstractStatusCheckRoute;

    abstract public function load(Request $request, SalesChannelContext $context): StatusCheckRouteResponse;
}




