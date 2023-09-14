<?php


namespace Okay\Modules\OkayCMS\Gercpay\Controllers;

use DateTime;
use DateTimeZone;
use Exception;
use Okay\Core\Money;
use Okay\Core\Notify;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\OrdersEntity;
use Okay\Entities\PaymentsEntity;
use Okay\Controllers\AbstractController;
use Psr\Log\LoggerInterface;

/**
 * Class CallbackController
 * @package Okay\Modules\OkayCMS\Gercpay\Controllers
 */
class CallbackController extends AbstractController
{
    const ORDER_IS_PAID         = 1;
    const ORDER_NOT_PAID        = 0;
    const ORDER_STATUS_DELETED  = 5;
    const ORDER_APPROVED        = 'Approved';
    const ORDER_DECLINED        = 'Declined';
    const RESPONSE_TYPE_PAYMENT = 'payment';
    const RESPONSE_TYPE_REVERSE = 'reverse';

    /**
     * @param Money $money
     * @param Notify $notify
     * @param OrdersEntity $ordersEntity
     * @param CurrenciesEntity $currenciesEntity
     * @param PaymentsEntity $paymentsEntity
     * @param LoggerInterface $logger
     * @throws Exception
     */
    public function payOrder(
        Money $money,
        Notify $notify,
        OrdersEntity $ordersEntity,
        CurrenciesEntity $currenciesEntity,
        PaymentsEntity $paymentsEntity,
        LoggerInterface $logger
    ) {
        $keysForSignature = [
            'merchantAccount',
            'orderReference',
            'amount',
            'currency'
        ];

        $this->response->setContentType(RESPONSE_TEXT);

        $data = json_decode(file_get_contents("php://input"), false);
        if (empty($data->orderReference)) {
            $this->response->setContent("Wrong data")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        $order_parse = !empty($data->orderReference) ? explode('#', $data->orderReference) : null;
        if (is_array($order_parse)) {
            $orderId = $order_parse[0];
        } else {
            $orderId = $order_parse;
        }

        $order = $ordersEntity->get((int) $orderId);
        if (empty($order)) {
            $logger->warning("GercPay notice: 'Order not found'. Order #{$orderId}");
            $this->response->setContent("Order not found")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        $method = $paymentsEntity->get((int) $order->payment_method_id);
        if (empty($method)) {
            $logger->warning("GercPay notice: 'Invalid payment method'. Order #{$orderId}");
            $this->response->setContent("Invalid payment method")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        $currency = $currenciesEntity->get((int) $method->currency_id);
        if ($data->currency !== $currency->code) {
            $logger->warning("GercPay notice: 'Invalid currency'. Order #{$orderId}");
            $this->response->setContent("Invalid currency")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        $amount = !empty($data->amount) ? $data->amount : null;
        $gercpayAmount = round($amount, 2);
        $orderAmount = round($money->convert($order->total_price, $method->currency_id, false), 2);
        if ((float)$orderAmount !== (float)$gercpayAmount) {
            $logger->warning("GercPay notice: 'Invalid total order amount'. Order #{$orderId}");
            $this->response->setContent("Invalid total order amount")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        $settings = unserialize($method->settings, [true]);

        $sign = array();
        foreach ($keysForSignature as $dataKey) {
            if (array_key_exists($dataKey, $data)) {
                $sign[] = $data->$dataKey;
            }
        }

        $sign = implode(';', $sign);
        $sign = hash_hmac('md5', $sign, $settings['gercpay_secretkey']);
        if (!empty($data->merchantSignature) && $data->merchantSignature !== $sign) {
            $logger->warning("GercPay notice: 'Invalid merchant signature'. Order #{$orderId}");
            $this->response->setContent("Invalid merchant signature")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        if (!empty($data->type) &&
            $data->type !== self::RESPONSE_TYPE_PAYMENT &&
            $data->type !== self::RESPONSE_TYPE_REVERSE
        ) {
            $logger->warning("GercPay notice: 'Unknown operation type'. Order #{$orderId}");
            $this->response->setContent("Unknown operation type. Type: " . $data->type)->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        if ((int) $order->status_id === self::ORDER_STATUS_DELETED) {
            $logger->warning("GercPay notice: 'Order already canceled'. Order #{$orderId}");
            $this->response->setContent("Order already canceled")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        if ($order->paid && $data->type !== self::RESPONSE_TYPE_REVERSE) {
            $logger->warning("GercPay notice: 'Order already paid'. Order #{$orderId}");
            $this->response->setContent("Order already paid")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        if (!empty($data->transactionStatus) && $data->transactionStatus === self::ORDER_DECLINED) {
            $reasonCode = $data->reasonCode ?? '';
            $reason = $data->reason ?? '';
            $logger->warning("GercPay notice: 'Payment declined'. Order #{$orderId}");
            $this->response->setContent("Payment declined. Error: " . $reasonCode . '. ' . $reason)->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        if (!empty($data->transactionStatus) && $data->transactionStatus === self::ORDER_APPROVED) {
            $sendNotify = false;
            if ($data->type === self::RESPONSE_TYPE_PAYMENT) {
                // Purchase callback.
                // Set flag 'paid' (this is not a status!).
                $ordersEntity->update((int)$order->id, ['paid' => self::ORDER_IS_PAID]);
                // Set flag 'closed' (reserve products)
                $ordersEntity->close((int)$order->id);
                $sendNotify = true;
            } elseif ($data->type === self::RESPONSE_TYPE_REVERSE) {
                // Refunded payment callback.
                // Set paid flag 'Not paid', order status 'Deleted', admin note 'Refund payment'.
                $ordersEntity->update((int)$order->id, [
                    'paid' => self::ORDER_NOT_PAID,
                    'status_id' => self::ORDER_STATUS_DELETED,
                    'note' => 'Refund payment'
                ]);
                // Remove flag 'closed', order opening (return reserved products quantity).
                $ordersEntity->open((int)$order->id);
                $sendNotify = true;
            }

            if ($sendNotify) {
                $notify->emailOrderUser((int)$order->id);
                $notify->emailOrderAdmin((int)$order->id);
            }
        }
    }
}
