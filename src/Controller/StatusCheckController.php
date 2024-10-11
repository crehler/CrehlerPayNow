<?php declare(strict_types=1);

namespace Crehler\PayNowPayment\Controller;

use Crehler\PayNowPayment\Controller\StatusCheck\AbstractStatusCheckRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route(defaults: ['_routeScope' => ['storefront']])]
class StatusCheckController extends StorefrontController
{
    private AbstractStatusCheckRoute $route;

    public function __construct(AbstractStatusCheckRoute $route)
    {
        $this->route = $route;
    }

    #[Route(path: '/paynow/payment-check', name: 'frontend.paynow-payment-check', defaults: ['XmlHttpRequest' => true], methods: ['POST'])]
    public function load(Request $request, SalesChannelContext $context): Response
    {
        return $this->route->load($request, $context);
    }
}