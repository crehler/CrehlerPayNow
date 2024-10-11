<?php
namespace Crehler\PayNowPayment\Lifecycle;

use Doctrine\DBAL\Connection;

class Uninstall
{
    /** @var Connection */
    private $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    public function uninstall($context)
    {
        if(!$context->keepUserData()){
            if (!$context->keepUserData()) {
                try {
                    $this->connection->executeStatement("DROP TABLE IF EXISTS  `paynow_refund_history`");
                    $this->connection->executeStatement("DROP TABLE IF EXISTS  `paynow_payment_tokens`");
                } catch (\Exception $e) {}
            }
        }
    }


}