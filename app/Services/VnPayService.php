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
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return config('vnpay.tmn_code') !== '' && config('vnpay.hash_secret') !== '';
    }

    public function createPaymentUrl(Order $order, Request $request): string
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $this->isConfigured()) {
            // Luong: Nem loi de dung luong khi dieu kien nghiep vu khong dat.
            throw new RuntimeException('Chưa cấu hình VNPAY_TMN_CODE và VNPAY_HASH_SECRET.');
        }

        // Luong: Gan ket qua xu ly vao bien $environment.
        $environment = (string) config('vnpay.environment', 'sandbox');
        // Luong: Gan ket qua xu ly vao bien $paymentUrl.
        $paymentUrl = (string) config("vnpay.urls.{$environment}", config('vnpay.urls.sandbox'));
        // Luong: Gan ket qua xu ly vao bien $createdAt.
        $createdAt = now();

        // Luong: Gan ket qua xu ly vao bien $params.
        $params = [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_Version' => '2.1.0',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_Command' => 'pay',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_TmnCode' => config('vnpay.tmn_code'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_Amount' => (int) round((float) $order->total_amount * 100),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_CurrCode' => 'VND',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_TxnRef' => $order->order_code,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_OrderInfo' => Str::limit(Str::ascii('Thanh toán đơn hàng ' . $order->order_code), 255, ''),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_OrderType' => 'billpayment',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_Locale' => config('vnpay.locale', 'vn'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_ReturnUrl' => config('vnpay.return_url'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_IpAddr' => $request->ip() ?: '127.0.0.1',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_CreateDate' => $createdAt->format('YmdHis'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'vnp_ExpireDate' => $createdAt->copy()->addMinutes((int) config('vnpay.expire_time', 15))->format('YmdHis'),
        ];

        // Luong: Gan ket qua xu ly vao bien $query.
        $query = $this->buildQueryString($params);
        // Luong: Gan ket qua xu ly vao bien $secureHash.
        $secureHash = $this->secureHash($query);

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $paymentUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash;
    }

    public function verify(array $params): array
    {
        // Luong: Gan ket qua xu ly vao bien $receivedHash.
        $receivedHash = (string) ($params['vnp_SecureHash'] ?? '');
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        // Luong: Gan ket qua xu ly vao bien $hashData.
        $hashData = $this->buildQueryString($params);
        // Luong: Gan ket qua xu ly vao bien $isValid.
        $isValid = hash_equals($this->secureHash($hashData), $receivedHash);
        // Luong: Gan ket qua xu ly vao bien $responseCode.
        $responseCode = (string) ($params['vnp_ResponseCode'] ?? '');
        // Luong: Gan ket qua xu ly vao bien $transactionStatus.
        $transactionStatus = (string) ($params['vnp_TransactionStatus'] ?? '');

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'is_valid' => $isValid,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'is_success' => $isValid && $responseCode === '00' && $transactionStatus === '00',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'txn_ref' => (string) ($params['vnp_TxnRef'] ?? ''),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'amount' => isset($params['vnp_Amount']) ? ((float) $params['vnp_Amount'] / 100) : 0.0,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'transaction_no' => (string) ($params['vnp_TransactionNo'] ?? ''),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'response_code' => $responseCode,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'transaction_status' => $transactionStatus,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'message' => config("vnpay.response_codes.{$responseCode}", 'Không xác định được kết quả thanh toán.'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
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
