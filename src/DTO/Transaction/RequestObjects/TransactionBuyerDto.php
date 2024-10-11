<?php declare(strict_types=1);

namespace Crehler\PayNowPayment\DTO\Transaction\RequestObjects;

class TransactionBuyerDto
{
    protected string $email;

    protected string $firstName;

    protected string $lastName;

    protected TransactionBuyerPhoneDto $phone;

    protected string $locale;

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }


    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return TransactionBuyerPhoneDto
     */
    public function getPhone(): TransactionBuyerPhoneDto
    {
        return $this->phone;
    }

    /**
     * @param TransactionBuyerPhoneDto $phone
     */
    public function setPhone(TransactionBuyerPhoneDto $phone): void
    {
        $this->phone = $phone;
    }

}