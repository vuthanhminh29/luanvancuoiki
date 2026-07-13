<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class VnPayService
{
    public function isConfigured(): bool
    {
        return config('vnpay.tmn_code') !== '' && config('vnpay.hash_secret') !== '';
    }

    public function createPaymentUrl(Order $order, Request $request): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Chưa cấu hình VNPAY_TMN_CODE và VNPAY_HASH_SECRET.');
        }

        $environment = (string) config('vnpay.environment', 'sandbox');
        $paymentUrl = (string) config("vnpay.urls.{$environment}", config('vnpay.urls.sandbox'));
        $createdAt = now();

        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => config('vnpay.tmn_code'),
            'vnp_Amount' => (int) round((float) $order->total_amount * 100),
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $order->order_code,
            'vnp_OrderInfo' => Str::limit(Str::ascii('Thanh toán đơn hàng ' . $order->order_code), 255, ''),
            'vnp_OrderType' => 'billpayment',
            'vnp_Locale' => config('vnpay.locale', 'vn'),
            'vnp_ReturnUrl' => config('vnpay.return_url'),
            'vnp_IpAddr' => $request->ip() ?: '127.0.0.1',
            'vnp_CreateDate' => $createdAt->format('YmdHis'),
            'vnp_ExpireDate' => $createdAt->copy()->addMinutes((int) config('vnpay.expire_time', 15))->format('YmdHis'),
        ];

        $query = $this->buildQueryString($params);
        $secureHash = $this->secureHash($query);

        return $paymentUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash;
    }

    public function verify(array $params): array
    {
        $receivedHash = (string) ($params['vnp_SecureHash'] ?? '');
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        $hashData = $this->buildQueryString($params);
        $isValid = hash_equals($this->secureHash($hashData), $receivedHash);
        $responseCode = (string) ($params['vnp_ResponseCode'] ?? '');
        $transactionStatus = (string) ($params['vnp_TransactionStatus'] ?? '');

        return [
            'is_valid' => $isValid,
            'is_success' => $isValid && $responseCode === '00' && $transactionStatus === '00',
            'txn_ref' => (string) ($params['vnp_TxnRef'] ?? ''),
            'amount' => isset($params['vnp_Amount']) ? ((float) $params['vnp_Amount'] / 100) : 0.0,
            'transaction_no' => (string) ($params['vnp_TransactionNo'] ?? ''),
            'response_code' => $responseCode,
            'transaction_status' => $transactionStatus,
            'message' => config("vnpay.response_codes.{$responseCode}", 'Không xác định được kết quả thanh toán.'),
            'raw_data' => $params,
        ];
    }

    private function buildQueryString(array $params): string
    {
        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $parts[] = urlencode((string) $key) . '=' . urlencode((string) $value);
        }

        return implode('&', $parts);
    }

    private function secureHash(string $data): string
    {
        return hash_hmac('sha512', $data, (string) config('vnpay.hash_secret'));
    }
}
