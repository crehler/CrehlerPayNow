<?php


namespace Crehler\PayNowPayment\Controller\PaymentMethods;


use Paynow\Model\PaymentMethods\Status;
use Shopware\Core\Framework\Struct\Struct;

class PaymentMethodStruct extends Struct

{
    protected $id;
    protected $type;
    protected $name;
    protected $description;
    protected $image;
    protected $status;

    public function __construct($id, $type, $name, $description, $image, $status)
    {
        $this->id = $id;
        $this->type = $type;
        $this->name = $name;
        $this->description = $description;
        $this->image = $image;
        $this->status = $status;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isEnabled()
    {
        return $this->status == Status::ENABLED;
    }
}
