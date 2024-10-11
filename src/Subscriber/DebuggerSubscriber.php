<?php declare(strict_types=1);
/**
 * @copyright 2022 Crehler Sp. z o. o.
 * @link https://crehler.com/
 * @support support@crehler.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Crehler\PayNowPayment\Subscriber;

use Crehler\PayNowPayment\Event\PaymentAuthorizeRequestEvent;
use Crehler\PayNowPayment\Event\PaymentAuthorizeResponseEvent;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Paynow\Client;

class DebuggerSubscriber implements EventSubscriberInterface
{
    protected SystemConfigService $systemConfigService;
    protected LoggerInterface $logger;
    protected Client $client;

    public function __construct(SystemConfigService $systemConfigService, LoggerInterface $logger, Client $client)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
        $this->client = $client;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentAuthorizeRequestEvent::class => 'onPaymentAuthorizeRequest',
            PaymentAuthorizeResponseEvent::class => 'onPaymentAuthorizeResponse'
        ];
    }

    public function onPaymentAuthorizeRequest(PaymentAuthorizeRequestEvent $event)
    {
        if (!$this->isEnabled()) return;
        $data = $this->anonymizeRequestData($event->getData());
        $message = "Register transaction for order " . $event->getTransaction()->getOrder()->getOrderNumber() . " (" . $event->getTransaction()->getOrder()->getId() . ")";
        if (is_string($event->getIdempotencyKey())) {
            $message .= " idempotencyKey " . $event->getIdempotencyKey();
        }
        $message .= " transactionId " . $event->getTransaction()->getOrderTransaction()->getId();
        $message .= " data: " . json_encode($data);

        $this->addClientDataAndLog($message, $event->getClient());
    }

    public function onPaymentAuthorizeResponse(PaymentAuthorizeResponseEvent $event)
    {
        if (!$this->isEnabled()) return;
        $message = "Register response for order " . $event->getTransaction()->getOrder()->getOrderNumber() . " (" . $event->getTransaction()->getOrder()->getId() . ")";
        $message .= " transactionId " . $event->getTransaction()->getOrderTransaction()->getId();
        $message .= " paynowPaymentId " . $event->getAuthorize()->getPaymentId();
        $message .= " paynowStatus " . $event->getAuthorize()->getStatus();
        $message .= " paynowRedirectUrl " . $event->getAuthorize()->getRedirectUrl();

        $this->addClientDataAndLog($message, null);
    }

    private function isEnabled(): bool
    {
        return (bool) $this->systemConfigService->get('CrehlerPayNowPayment.config.enableDebugLogging');
    }

    private function needAnonymize(): bool
    {
        return (bool) $this->systemConfigService->get('CrehlerPayNowPayment.config.enableLogAnonymize');
    }

    private function anonymizeRequestData(array $data): array
    {
        if (!$this->needAnonymize()) return $data;
        if (!array_key_exists('buyer', $data)) return $data;

        $buyer = $data['buyer'];
        $fields = ['email', 'firstName', 'lastName'];

        foreach ($fields as $field) {
            if (array_key_exists($field, $buyer) && is_string($buyer[$field])) {
                $buyer[$field] = $this->anonymizeString($buyer[$field]);
            }
        }

        $data['buyer'] = $buyer;

        return $data;
    }

    private function anonymizeString(string $input): string
    {
        return preg_replace("/(?!^).(?!$)/", "*", $input);
    }

    private function addClientDataAndLog(string $message, ?Client $client = null): void
    {
        if ($client === null) {
            $client = $this->client;
            $message .= " DI Client";
        }

        $message .= " Client Environment: " . $client->getConfiguration()->getEnvironment();
        $message .= " Client Application Name: " . $client->getConfiguration()->getApplicationName();

        $this->logger->info($message);
    }
}
