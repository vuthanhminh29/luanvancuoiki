<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;color:#111827;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 24px;border-bottom:1px solid #e5e7eb;">
                            <div style="font-size:12px;font-weight:700;color:#0f766e;text-transform:uppercase;">Thông báo hủy đơn hàng</div>
                            <h1 style="margin:8px 0 0;font-size:22px;line-height:1.35;color:#111827;">{{ $statusLine }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 24px;">
                            <p style="margin:0 0 12px;font-size:15px;line-height:1.6;">Xin chào {{ $customerName }},</p>
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">{{ $noteLine }}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                <tr>
                                    <td style="padding:12px 14px;background:#f8fafc;color:#64748b;font-size:13px;font-weight:700;">Mã đơn</td>
                                    <td style="padding:12px 14px;font-size:13px;font-weight:700;text-align:right;">{{ $orderCode }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;color:#64748b;font-size:13px;font-weight:700;border-top:1px solid #e5e7eb;">Ngày đặt</td>
                                    <td style="padding:12px 14px;font-size:13px;font-weight:700;text-align:right;border-top:1px solid #e5e7eb;">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f8fafc;color:#64748b;font-size:13px;font-weight:700;border-top:1px solid #e5e7eb;">Trạng thái</td>
                                    <td style="padding:12px 14px;background:#f8fafc;font-size:13px;font-weight:700;text-align:right;border-top:1px solid #e5e7eb;">{{ $statusLabel }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;color:#64748b;font-size:13px;font-weight:700;border-top:1px solid #e5e7eb;">Tổng thanh toán</td>
                                    <td style="padding:12px 14px;font-size:13px;font-weight:700;text-align:right;border-top:1px solid #e5e7eb;">{{ $totalAmount }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;background:#f8fafc;color:#64748b;font-size:13px;font-weight:700;border-top:1px solid #e5e7eb;">Lý do hủy</td>
                                    <td style="padding:12px 14px;background:#f8fafc;font-size:13px;font-weight:700;text-align:right;border-top:1px solid #e5e7eb;">{{ $order->cancel_reason ?: '-' }}</td>
                                </tr>
                            </table>

                            <p style="margin:18px 0 0;font-size:14px;line-height:1.6;color:#475569;">Nếu bạn cần hỗ trợ thêm, vui lòng liên hệ cửa hàng.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
