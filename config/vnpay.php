<?php

return [
    'tmn_code' => env('VNPAY_TMN_CODE', ''),
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),
    'environment' => env('VNPAY_ENVIRONMENT', 'sandbox'),
    'return_url' => env('VNPAY_RETURN_URL', env('APP_URL') . '/vnpay/return'),
    'ipn_url' => env('VNPAY_IPN_URL', env('APP_URL') . '/vnpay/ipn'),
    'expire_time' => (int) env('VNPAY_EXPIRE_TIME', 15),
    'locale' => env('VNPAY_LOCALE', 'vn'),
    'urls' => [
        'sandbox' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        'live' => 'https://pay.vnpay.vn/vpcpay.html',
    ],
    'response_codes' => [
        '00' => 'Giao dịch thành công',
        '05' => 'Tài khoản không đủ số dư',
        '06' => 'Sai mật khẩu OTP',
        '07' => 'Giao dịch nghi ngờ',
        '09' => 'Chưa đăng ký InternetBanking',
        '10' => 'Xác thực sai quá 3 lần',
        '11' => 'Hết hạn chờ thanh toán',
        '12' => 'Thẻ hoặc tài khoản bị khóa',
        '24' => 'Khách hàng hủy giao dịch',
        '65' => 'Vượt quá hạn mức thanh toán',
        '75' => 'Ngân hàng đang bảo trì',
        '79' => 'Sai mật khẩu thanh toán quá số lần quy định',
        '99' => 'Lỗi không xác định',
    ],
];
