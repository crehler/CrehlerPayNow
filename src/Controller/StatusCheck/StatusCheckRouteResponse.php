<?php

declare(strict_types=1);

namespace Crehler\PayNowPayment\Controller\StatusCheck;

use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;


class StatusCheckRouteResponse extends StoreApiResponse
{
    public function __construct(array $responseData)
    {
        parent::__construct(new ArrayStruct($responseData));
    }
}




