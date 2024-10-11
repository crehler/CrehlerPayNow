<?php

namespace Crehler\PayNowPayment\Factory;

use Crehler\PayNowPayment\DTO\Transaction\TransactionResponseDTO;

class TransactionResponseDtoFactory
{
    public function createResponseTransactionDto(string $paymentId,string $redirectUrl,string $status): TransactionResponseDTO
    {
        $responseDto = new TransactionResponseDTO;

        $responseDto->setPaymentId($paymentId);
        $responseDto->setRedirectUrl($redirectUrl);
        $responseDto->setStatus($status);

        return $responseDto;
    }
}