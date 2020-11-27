<?php
/**
 * Copyright (c) 2012-2020, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @author     Mollie B.V. <info@mollie.nl>
 * @copyright  Mollie B.V.
 * @license    Berkeley Software Distribution License (BSD-License 2) http://www.opensource.org/licenses/bsd-license.php
 *
 * @category   Mollie
 *
 * @see       https://www.mollie.nl
 * @codingStandardsIgnoreStart
 */

namespace Mollie\Service;

use Exception;
use Mollie;
use Mollie\Repository\PaymentMethodRepository;
use PrestaShopLogger;

class MollieOrderInfoService
{
	/**
	 * @var PaymentMethodRepository
	 */
	private $paymentMethodRepository;
	/**
	 * @var RefundService
	 */
	private $refundService;
	/**
	 * @var ShipService
	 */
	private $shipService;
	/**
	 * @var CancelService
	 */
	private $cancelService;
	/**
	 * @var ShipmentService
	 */
	private $shipmentService;
	/**
	 * @var Mollie
	 */
	private $module;
	/**
	 * @var ApiService
	 */
	private $apiService;

	public function __construct(
		Mollie $module,
		PaymentMethodRepository $paymentMethodRepository,
		RefundService $refundService,
		ShipService $shipService,
		CancelService $cancelService,
		ShipmentService $shipmentService,
		ApiService $apiService
	) {
		$this->module = $module;
		$this->paymentMethodRepository = $paymentMethodRepository;
		$this->refundService = $refundService;
		$this->shipService = $shipService;
		$this->cancelService = $cancelService;
		$this->shipmentService = $shipmentService;
		$this->apiService = $apiService;
	}

	/**
	 * @param $input
	 *
	 * @return array
	 *
	 * @since 3.3.0
	 */
	public function displayMollieOrderInfo($input)
	{
		try {
			$mollieData = $this->paymentMethodRepository->getPaymentBy('transaction_id', $input['transactionId']);
			if ('payments' === $input['resource']) {
				switch ($input['action']) {
					case 'refund':
						if (!isset($input['amount']) || empty($input['amount'])) {
							// No amount = full refund
							$status = $this->refundService->doPaymentRefund($mollieData['transaction_id']);
						} else {
							$status = $this->refundService->doPaymentRefund($mollieData['transaction_id'], $input['amount']);
						}

						return [
							'success' => isset($status['status']) && 'success' === $status['status'],
							'payment' => $this->apiService->getFilteredApiPayment($this->module->api, $input['transactionId'], false),
						];
					case 'retrieve':
						return [
							'success' => true,
							'payment' => $this->apiService->getFilteredApiPayment($this->module->api, $input['transactionId'], false),
						];
					default:
						return ['success' => false];
				}
			} elseif ('orders' === $input['resource']) {
				switch ($input['action']) {
					case 'retrieve':
						$info = $this->paymentMethodRepository->getPaymentBy('transaction_id', $input['transactionId']);
						if (!$info) {
							return ['success' => false];
						}
						$tracking = $this->shipmentService->getShipmentInformation($info['order_reference']);

						return [
							'success' => true,
							'order' => $this->apiService->getFilteredApiOrder($this->module->api, $input['transactionId']),
							'tracking' => $tracking,
						];
					case 'ship':
						$status = $this->shipService->doShipOrderLines($input['transactionId'], isset($input['orderLines']) ? $input['orderLines'] : [], isset($input['tracking']) ? $input['tracking'] : null);

						return array_merge($status, ['order' => $this->apiService->getFilteredApiOrder($this->module->api, $input['transactionId'])]);
					case 'refund':
						$status = $this->refundService->doRefundOrderLines($input['order'], isset($input['orderLines']) ? $input['orderLines'] : []);

						return array_merge($status, ['order' => $this->apiService->getFilteredApiOrder($this->module->api, $input['order']['id'])]);
					case 'cancel':
						$status = $this->cancelService->doCancelOrderLines($input['transactionId'], isset($input['orderLines']) ? $input['orderLines'] : []);

						return array_merge($status, ['order' => $this->apiService->getFilteredApiOrder($this->module->api, $input['transactionId'])]);
					default:
						return ['success' => false];
				}
			}
		} catch (Exception $e) {
			PrestaShopLogger::addLog("Mollie module error: {$e->getMessage()}");

			return ['success' => false];
		}

		return ['success' => false];
	}
}
