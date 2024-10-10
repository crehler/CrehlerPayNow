<?php


namespace Crehler\PayNowPayment\Controller\PaymentMethods;


use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

class PaymentResponse  extends StoreApiResponse
{
    /**
     * @var ArrayStruct
     */
    protected $object;

    public function __construct($paymentMethodsArray)
    {
        parent::__construct(new ArrayStruct([
            'paymentMethods' => $paymentMethodsArray,
        ], 'abc'));
    }

    public function getResult(): ArrayStruct
    {
        return $this->object;
    }

    public function getPaymentMethods(): PaymentMethodsCollection
    {
        return $this->object->get('paymentMethods');
    }


}