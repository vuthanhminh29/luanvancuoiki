<?php

namespace App\Services;

use App\Models\Order;
use App\Support\QueuedRawMail as Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderConfirmationEmailService
{
    // Gá»­i email xÃ¡c nháº­n Ä‘Æ¡n hÃ ng thÃ nh cÃ´ng cho cáº£ COD vÃ  VNPay.
    // Email nÃ y chá»‰ thÃ´ng bÃ¡o thÃ´ng tin Ä‘Æ¡n hÃ ng, khÃ´ng cÃ³ link xÃ¡c nháº­n hay link thao tÃ¡c.
    public function send(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            // KhÃ³a order Ä‘á»ƒ VNPay return vÃ  IPN khÃ´ng gá»­i trÃ¹ng email cÃ¹ng lÃºc.
            $lockedOrder = Order::query()
                ->with(['user', 'items'])
                ->lockForUpdate()
                ->find($order->id);

            if (! $lockedOrder) {
                return false;
            }

            // Náº¿u Ä‘Ã£ gá»­i rá»“i thÃ¬ bá» qua, coi nhÆ° thÃ nh cÃ´ng.
            if ($lockedOrder->order_confirmation_email_sent_at !== null) {
                return true;
            }

            // Láº¥y email tá»« tÃ i khoáº£n user gáº¯n vá»›i Ä‘Æ¡n hÃ ng.
            // ÄÆ¡n hÃ ng checkout báº¯t buá»™c Ä‘Äƒng nháº­p nÃªn user thÆ°á»ng luÃ´n cÃ³ email.
            $email = $this->customerEmail($lockedOrder);

            if ($email === null) {
                Log::warning('Order confirmation email skipped because customer email is missing.', [
                    'order_id' => $lockedOrder->id,
                ]);

                return false;
            }

            try {
                Mail::raw(
                    $this->emailBody($lockedOrder),
                    fn ($message) => $message
                        ->to($email)
                        ->subject('XÃ¡c nháº­n Ä‘Æ¡n hÃ ng thÃ nh cÃ´ng ' . ($lockedOrder->order_code ?: '#' . $lockedOrder->id))
                );
            } catch (\Throwable $exception) {
                // KhÃ´ng rollback Ä‘Æ¡n hÃ ng náº¿u mail lá»—i; chá»‰ log Ä‘á»ƒ admin kiá»ƒm tra SMTP.
                Log::error('Order confirmation email could not be sent.', [
                    'order_id' => $lockedOrder->id,
                    'email' => $email,
                    'message' => $exception->getMessage(),
                ]);

                return false;
            }

            // ÄÃ¡nh dáº¥u Ä‘Ã£ gá»­i email Ä‘á»ƒ cÃ¡c láº§n gá»i sau khÃ´ng gá»­i láº¡i.
            $lockedOrder->forceFill([
                'order_confirmation_email_sent_at' => now(),
            ])->save();

            return true;
        });
    }

    private function customerEmail(Order $order): ?string
    {
        // Trim Ä‘á»ƒ trÃ¡nh trÆ°á»ng há»£p email chá»‰ toÃ n khoáº£ng tráº¯ng.
        $email = trim((string) ($order->user?->email ?? ''));

        return $email === '' ? null : $email;
    }

    private function emailBody(Order $order): string
    {
        // Ná»™i dung email gá»“m Ä‘áº§y Ä‘á»§ snapshot Ä‘Æ¡n hÃ ng.
        // Snapshot láº¥y tá»« order_items nÃªn sáº£n pháº©m Ä‘á»•i tÃªn/giÃ¡ sau nÃ y cÅ©ng khÃ´ng lÃ m email sai.
        $lines = [
            'Xin chÃ o ' . ($order->user?->full_name ?: $order->recipient_name) . ',',
            '',
            'ÄÆ¡n hÃ ng cá»§a báº¡n Ä‘Ã£ Ä‘Æ°á»£c ghi nháº­n thÃ nh cÃ´ng táº¡i ' . config('app.name') . '.',
            'DÆ°á»›i Ä‘Ã¢y lÃ  toÃ n bá»™ thÃ´ng tin Ä‘Æ¡n hÃ ng:',
            '',
            'THÃ”NG TIN ÄÆ N HÃ€NG',
            'MÃ£ Ä‘Æ¡n: ' . ($order->order_code ?: '#' . $order->id),
            'NgÃ y Ä‘áº·t: ' . $order->created_at?->format('d/m/Y H:i'),
            'Tráº¡ng thÃ¡i Ä‘Æ¡n: ' . $this->statusLabel($order->status),
            'PhÆ°Æ¡ng thá»©c thanh toÃ¡n: ' . $this->paymentLabel($order->payment_method),
            'Tráº¡ng thÃ¡i thanh toÃ¡n: ' . $this->paymentStatusLabel($order->payment_status),
            '',
            'THÃ”NG TIN GIAO HÃ€NG',
            'NgÆ°á»i nháº­n: ' . $order->recipient_name,
            'Sá»‘ Ä‘iá»‡n thoáº¡i: ' . $order->recipient_phone,
            'Äá»‹a chá»‰: ' . $order->shipping_address,
            'Ghi chÃº: ' . ($order->note ?: '-'),
            '',
            'Sáº¢N PHáº¨M',
        ];

        foreach ($order->items as $item) {
            $variant = trim(implode(' ', array_filter([$item->color_name, $item->lens_size_name])));
            $lines[] = '- ' . $item->product_name;

            if ($item->sku) {
                $lines[] = '  SKU: ' . $item->sku;
            }

            if ($variant !== '') {
                $lines[] = '  PhÃ¢n loáº¡i: ' . $variant;
            }

            $lines[] = '  Sá»‘ lÆ°á»£ng: ' . $item->quantity;
            $lines[] = '  ÄÆ¡n giÃ¡: ' . $this->money($item->unit_price);
            $lines[] = '  ThÃ nh tiá»n: ' . $this->money($item->total_price);
        }

        return implode(\n, array_merge($lines, [
            '',
            'THANH TOÃN',
            'Tá»•ng tiá»n hÃ ng: ' . $this->money($order->subtotal_amount),
            'Giáº£m giÃ¡: ' . $this->money($order->discount_amount),
            'PhÃ­ váº­n chuyá»ƒn: ' . $this->money($order->shipping_fee),
            'Tá»•ng thanh toÃ¡n: ' . $this->money($order->total_amount),
            '',
            'Cáº£m Æ¡n báº¡n Ä‘Ã£ mua hÃ ng táº¡i ' . config('app.name') . '.',
        ]));
    }

    private function money(mixed $amount): string
    {
        // Äá»‹nh dáº¡ng tiá»n Viá»‡t Nam giá»‘ng cÃ¡c mÃ n hÃ¬nh Ä‘Æ¡n hÃ ng.
        return number_format((float) $amount, 0, ',', '.') . 'Ä‘';
    }

    private function paymentLabel(?string $method): string
    {
        // Chuyá»ƒn mÃ£ phÆ°Æ¡ng thá»©c trong database thÃ nh chá»¯ dá»… hiá»ƒu trong email.
        return match ($method) {
            'COD' => 'Thanh toÃ¡n khi nháº­n hÃ ng',
            'VNPAY' => 'VNPay',
            default => $method ?: '-',
        };
    }

    private function paymentStatusLabel(?string $status): string
    {
        // COD lÃºc má»›i Ä‘áº·t lÃ  UNPAID, VNPay thÃ nh cÃ´ng lÃ  PAID.
        return match ($status) {
            'PAID' => 'ÄÃ£ thanh toÃ¡n',
            'UNPAID' => 'ChÆ°a thanh toÃ¡n',
            default => $status ?: '-',
        };
    }

    private function statusLabel(?string $status): string
    {
        // Chuyá»ƒn mÃ£ tráº¡ng thÃ¡i Ä‘Æ¡n hÃ ng thÃ nh tiáº¿ng Viá»‡t Ä‘á»ƒ khÃ¡ch Ä‘á»c email dá»… hiá»ƒu.
        return match ($status) {
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
            default => $status ?: '-',
        };
    }
}
