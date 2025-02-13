<?php
/**
 * Mollie       https://www.mollie.nl
 *
 * @author      Mollie B.V. <info@mollie.nl>
 * @copyright   Mollie B.V.
 * @license     https://github.com/mollie/PrestaShop/blob/master/LICENSE.md
 *
 * @see        https://github.com/mollie/PrestaShop
 * @codingStandardsIgnoreStart
 */

use Mollie\Api\Exceptions\ApiException;
use Mollie\Service\TransactionService;
use Mollie\Utility\TransactionUtility;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../mollie.php';

class MollieWebhookModuleFrontController extends ModuleFrontController
{
    /** @var Mollie */
    public $module;
    /** @var bool */
    public $ssl = true;
    /** @var bool */
    public $display_column_left = false;
    /** @var bool */
    public $display_column_right = false;

    /**
     * Prevent displaying the maintenance page.
     *
     * @return void
     */
    protected function displayMaintenancePage()
    {
    }

    /**
     * @throws ApiException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initContent()
    {
        if (Configuration::get(Mollie\Config\Config::MOLLIE_DEBUG_LOG)) {
            PrestaShopLogger::addLog('Mollie incoming webhook: ' . Tools::file_get_contents('php://input'));
        }

        exit($this->executeWebhook());
    }

    /**
     * @return string
     *
     * @throws ApiException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function executeWebhook()
    {
        /** @var TransactionService $transactionService */
        $transactionService = $this->module->getMollieContainer(TransactionService::class);

        $transactionId = Tools::getValue('id');
        if (!$transactionId) {
            return 'Missing transaction id';
        }

        try {
            if (TransactionUtility::isOrderTransaction($transactionId)) {
                $payment = $transactionService->processTransaction($this->module->api->orders->get($transactionId, ['embed' => 'payments']));
            } else {
                $payment = $transactionService->processTransaction($this->module->api->payments->get($transactionId));
            }
        } catch (\exception $e) {
            return $e->getMessage();
        }

        if (is_string($payment)) {
            return $payment;
        }

        return 'OK';
    }
}
