<?php declare(strict_types=1);
/**
 * @copyright 2022 Crehler Sp. z o. o.
 * @link https://crehler.com/
 * @support support@crehler.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Crehler\PayNowPayment\Service;

use Paynow\Client;
use Paynow\Environment;
use Paynow\Exception\ConfigurationException;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Paynow\Service\Payment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PayNowServicesFactory
{
    private SystemConfigService $systemConfigService;

    private ParameterBagInterface $parameterBag;

    private ?Client $client;

    public function __construct(SystemConfigService $systemConfigService, ParameterBagInterface $parameterBag)
    {
        $this->systemConfigService = $systemConfigService;
        $this->parameterBag = $parameterBag;
        $this->client = null;
    }

    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client(
                $this->getApiKey(),
                (string) $this->getConfigurationValue('CrehlerPayNowPayment.config.SingatureKeyLabel'),
                $this->systemConfigService->get('CrehlerPayNowPayment.config.EnableSandbox') ?  Environment::SANDBOX : Environment::PRODUCTION,
                $this->getApplicationName()
            );
        }

        return $this->client;
    }

    public function factorClient(): Client
    {
        return $this->getClient();
    }

    /**
     * @throws ConfigurationException
     */
    public function factorPayment(): Payment
    {
        return new Payment($this->getClient());
    }

    public function getApiKey(): string
    {
        return (string) $this->getConfigurationValue('CrehlerPayNowPayment.config.ApiKey');
    }

    public function getSignature(): string
    {
        return (string) $this->getConfigurationValue('CrehlerPayNowPayment.config.SingatureKeyLabel');
    }

    private function getConfigurationValue(string $key): string
    {
        $configValue = (string) $this->systemConfigService->get($key);
        if (empty($configValue)) return '1';
        return $configValue;
    }

    private function getApplicationName(): string
    {
        if ($this->parameterBag->has('kernel.shopware_version')) {
            return "Shopware " . $this->parameterBag->get('kernel.shopware_version');
        }
        return "Shopware 6";
    }


}
