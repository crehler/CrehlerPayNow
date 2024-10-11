<?php
namespace Crehler\PayNowPayment\DTO\Transaction;

use Crehler\PayNowPayment\DTO\Transaction\RequestObjects\TransactionBuyerDto;

class TransactionDto
{
    protected int $amount;

    protected string $currency;

    protected string $externalId;

    protected string $description;

    protected string $continueUrl;

    protected TransactionBuyerDto $buyer;

    protected array $orderItems;

    protected int $validityTime;

    protected ?string $paymentMethodId;

    /**
     * @return string|null
     */
    public function getPaymentMethodId(): ?string
    {
        return $this->paymentMethodId;
    }

    /**
     * @param string|null $paymentMethodId
     */
    public function setPaymentMethodId(?string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    /**
     * @return int
     */
    public function getValidityTime(): int
    {
        return $this->validityTime;
    }

    /**
     * @param int $validityTime
     */
    public function setValidityTime(int $validityTime): void
    {
        $this->validityTime = $validityTime;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     */
    public function setExternalId(string $externalId): void
    {
        $this->externalId = $externalId;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getContinueUrl(): string
    {
        return $this->continueUrl;
    }

    /**
     * @param string $continueUrl
     */
    public function setContinueUrl(string $continueUrl): void
    {
        $this->continueUrl = $continueUrl;
    }

    /**
     * @return TransactionBuyerDto
     */
    public function getBuyer(): TransactionBuyerDto
    {
        return $this->buyer;
    }

    /**
     * @param TransactionBuyerDto $buyer
     */
    public function setBuyer(TransactionBuyerDto $buyer): void
    {
        $this->buyer = $buyer;
    }

    /**
     * @return array
     */
    public function getOrderItems(): array
    {
        return $this->orderItems;
    }

    /**
     * @param array $orderItems
     */
    public function setOrderItems(array $orderItems): void
    {
        $this->orderItems = $orderItems;
    }






}