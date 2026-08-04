@php
    $invoiceCode = $order->order_code ?: ('DH' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT));
    $paymentMap = ['COD' => 'Thanh toÃ¡n khi nháº­n hÃ ng', 'VNPAY' => 'VNPay'];
    $statusMap = [
        'PENDING' => 'Chá» xÃ¡c nháº­n',
        'AWAITING_PAYMENT' => 'Chá» thanh toÃ¡n',
        'CONFIRMED' => 'ÄÃ£ xÃ¡c nháº­n',
        'DELIVERING' => 'Äang giao',
        'DELIVERED' => 'Giao thÃ nh cÃ´ng',
        'CANCELLED' => 'ÄÃ£ há»§y',
        'RETURN_PENDING' => 'Chá» hoÃ n/Ä‘á»•i',
        'RETURNED' => 'ÄÃ£ hoÃ n tráº£',
        'EXCHANGED' => 'ÄÃ£ Ä‘á»•i hÃ ng',
        'LOST_IN_TRANSIT' => 'Máº¥t hÃ ng khi giao',
    ];
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HÃ³a Ä‘Æ¡n {{ $invoiceCode }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f7; color: #111827; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { position: sticky; top: 0; z-index: 2; display: flex; justify-content: center; gap: 10px; padding: 14px; background: rgba(238, 242, 247, .94); border-bottom: 1px solid #dbe3ef; }
        .btn { min-height: 38px; display: inline-flex; align-items: center; justify-content: center; padding: 0 14px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #1f2937; font-size: 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .btn.primary { background: #111827; color: #fff; border-color: #111827; }
        .mail-status { width: 210mm; margin: 14px auto 0; padding: 10px 14px; border-radius: 8px; border: 1px solid #dbeafe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 700; }
        .mail-status.error { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }
        .page { width: 210mm; min-height: 297mm; margin: 18px auto; padding: 18mm; background: #fff; box-shadow: 0 16px 45px rgba(15, 23, 42, .14); }
        .top { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #111827; padding-bottom: 18px; }
        .brand h1 { margin: 0; font-size: 26px; letter-spacing: 0; }
        .brand p, .invoice-meta p { margin: 6px 0 0; color: #4b5563; font-size: 13px; line-height: 1.5; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { margin: 0; font-size: 28px; letter-spacing: 0; }
        .invoice-title strong { display: block; margin-top: 8px; font-size: 15px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 20px; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; }
        .box h3 { margin: 0 0 10px; font-size: 14px; text-transform: uppercase; letter-spacing: 0; color: #374151; }
        .line { display: flex; justify-content: space-between; gap: 16px; padding: 6px 0; font-size: 14px; line-height: 1.45; }
        .line span:first-child { color: #6b7280; }
        .line strong { text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 22px; }
        th { background: #111827; color: #fff; font-size: 13px; text-align: left; padding: 10px; }
        td { border-bottom: 1px solid #e5e7eb; padding: 10px; font-size: 14px; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .muted { color: #6b7280; font-size: 12px; margin-top: 4px; }
        .totals { width: 320px; margin: 20px 0 0 auto; }
        .totals .line { border-bottom: 1px solid #e5e7eb; }
        .totals .grand { border-bottom: 0; padding-top: 12px; font-size: 18px; }
        .note { margin-top: 24px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; color: #4b5563; font-size: 13px; line-height: 1.55; }
        @media print {
            body { background: #fff; }
            .toolbar, .mail-status { display: none; }
            .page { width: auto; min-height: 297mm; margin: 0; padding: 14mm; box-shadow: none; }
            @page { size: A4; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn primary" type="button" onclick="window.print()">In / Táº£i PDF</button>
        <a class="btn" href="{{ $backUrl }}">Quay láº¡i Ä‘Æ¡n hÃ ng</a>
    </div>

    @isset($invoiceEmailSent)
        <div class="mail-status {{ $invoiceEmailSent ? '' : 'error' }}">
            {{ $invoiceEmailSent ? 'HÃ³a Ä‘Æ¡n Ä‘Ã£ Ä‘Æ°á»£c gá»­i vá» email cá»§a báº¡n.' : 'ChÆ°a gá»­i Ä‘Æ°á»£c email hÃ³a Ä‘Æ¡n, báº¡n váº«n cÃ³ thá»ƒ in hoáº·c táº£i PDF táº¡i trang nÃ y.' }}
        </div>
    @endisset

    <main class="page">
        <section class="top">
            <div class="brand">
                <h1>{{ config('app.name', 'Cá»­a hÃ ng') }}</h1>
                <p>HÃ³a Ä‘Æ¡n bÃ¡n hÃ ng Ä‘Æ°á»£c xuáº¥t tá»« há»‡ thá»‘ng quáº£n lÃ½ Ä‘Æ¡n hÃ ng.</p>
                <p>NgÃ y xuáº¥t: {{ now()->format('d/m/Y H:i') }}</p>
            </div>
            <div class="invoice-title">
                <h2>HÃ“A ÄÆ N</h2>
                <strong>#{{ $invoiceCode }}</strong>
                <div class="invoice-meta">
                    <p>NgÃ y Ä‘áº·t: {{ $order->created_at?->format('d/m/Y H:i') }}</p>
                    <p>Tráº¡ng thÃ¡i: {{ $statusMap[$order->status] ?? $order->status }}</p>
                </div>
            </div>
        </section>

        <section class="grid">
            <div class="box">
                <h3>ThÃ´ng tin khÃ¡ch hÃ ng</h3>
                <div class="line"><span>KhÃ¡ch hÃ ng</span><strong>{{ $order->user->full_name ?? $order->recipient_name }}</strong></div>
                <div class="line"><span>Email</span><strong>{{ $order->user->email ?? 'KhÃ´ng cÃ³' }}</strong></div>
                <div class="line"><span>Sá»‘ Ä‘iá»‡n thoáº¡i</span><strong>{{ $order->recipient_phone }}</strong></div>
            </div>
            <div class="box">
                <h3>ThÃ´ng tin nháº­n hÃ ng</h3>
                <div class="line"><span>NgÆ°á»i nháº­n</span><strong>{{ $order->recipient_name }}</strong></div>
                <div class="line"><span>Äá»‹a chá»‰</span><strong>{{ $order->shipping_address }}</strong></div>
                <div class="line"><span>Thanh toÃ¡n</span><strong>{{ $paymentMap[$order->payment_method] ?? 'PhÆ°Æ¡ng thá»©c khÃ¡c' }}</strong></div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th style="width: 44px;">STT</th>
                    <th>Sáº£n pháº©m</th>
                    <th class="num">ÄÆ¡n giÃ¡</th>
                    <th class="num">SL</th>
                    <th class="num">ThÃ nh tiá»n</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $item->product_name }}</strong>
                            @if ($item->sku || $item->color_name || $item->lens_size_name)
                                <div class="muted">
                                    @if ($item->sku) SKU: {{ $item->sku }} @endif
                                    @if ($item->color_name) | MÃ u: {{ $item->color_name }} @endif
                                    @if ($item->lens_size_name) | Size: {{ $item->lens_size_name }} @endif
                                </div>
                            @endif
                        </td>
                        <td class="num">{{ number_format((float) $item->unit_price, 0, ',', '.') }}Ä‘</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ number_format((float) $item->total_price, 0, ',', '.') }}Ä‘</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <section class="totals">
            <div class="line"><span>Tá»•ng tiá»n hÃ ng</span><strong>{{ number_format((float) $order->subtotal_amount, 0, ',', '.') }}Ä‘</strong></div>
            <div class="line"><span>PhÃ­ váº­n chuyá»ƒn</span><strong>{{ (float) $order->shipping_fee > 0 ? number_format((float) $order->shipping_fee, 0, ',', '.') . 'Ä‘' : 'Miá»…n phÃ­' }}</strong></div>
            @if ((float) $order->discount_amount > 0)
                <div class="line"><span>Giáº£m giÃ¡</span><strong>-{{ number_format((float) $order->discount_amount, 0, ',', '.') }}Ä‘</strong></div>
            @endif
            <div class="line grand"><span>Tá»•ng thanh toÃ¡n</span><strong>{{ number_format((float) $order->total_amount, 0, ',', '.') }}Ä‘</strong></div>
        </section>

        @if (trim((string) $order->note) !== '')
            <div class="note"><strong>Ghi chÃº:</strong> {{ $order->note }}</div>
        @endif

    </main>
</body>
</html>
