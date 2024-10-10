<?php


namespace Crehler\PayNowPayment\Common;

use Shopware\Core\Checkout\Payment\Cart\Token\TokenFactoryInterfaceV2;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenStruct;

class TokenHandler
{
    protected TokenFactoryInterfaceV2 $tokenFactory;

    public function __construct(TokenFactoryInterfaceV2 $tokenFactory)
    {
        $this->tokenFactory =  $tokenFactory;
    }

    public function generateToken($returnUrl): array
    {
        $parsed = parse_url($returnUrl);

        $token = $parsed['query'];
        $continueUrl = $parsed['host'] . $parsed['path'];

        $tokenStruct = new TokenStruct(
            null,
            $token,
            null,
            null,
            null,
            259200, // 3 days
            null
        );

        return [
            'token'=>$this->tokenFactory->generateToken($tokenStruct), //MFL zwraca token?!
            'continueUrl'=>$continueUrl
        ];

    }


}