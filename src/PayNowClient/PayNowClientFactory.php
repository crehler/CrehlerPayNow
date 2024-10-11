<?php

namespace Crehler\PayNowPayment\PayNowClient;

use Paynow\Client;
use Paynow\Environment;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PayNowClientFactory
{
    public function createPayNowClient(SystemConfigService $systemConfigService): Client
    {
        return  new Client(
            $systemConfigService->get('CrehlerPayNowPayment.config.ApiKey'),
            $systemConfigService->get('CrehlerPayNowPayment.config.SingatureKeyLabel'),
            $systemConfigService->get('CrehlerPayNowPayment.config.EnableSandbox') ?  Environment::SANDBOX : Environment::PRODUCTION
        );
    }
}