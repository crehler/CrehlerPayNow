<?php declare(strict_types=1);


namespace Crehler\PayNowPayment\Controller\StatusCheck;


use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;


class StatusCheckRouteResponse extends StoreApiResponse
{
    protected $object;

    public function __construct(array $responseData)
    {
        parent::__construct(new ArrayStruct($responseData));
    }

    public function isWaiting(): bool
    {
        return $this->object->get('waiting');
    }

    public function isSuccess(): bool
    {
        return $this->object->get('success');
    }
}




