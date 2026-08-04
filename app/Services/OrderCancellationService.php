<?php

namespace App\Services;

use App\Models\Order;
use App\Support\QueuedRawMail as Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class OrderCancellationService
{
    // Chá»‰ cÃ¡c tráº¡ng thÃ¡i nÃ y Ä‘Æ°á»£c phÃ©p báº¯t Ä‘áº§u luá»“ng há»§y.
    // ÄÆ¡n Ä‘ang giao/Ä‘Ã£ giao/hoÃ n Ä‘á»•i thÃ¬ khÃ´ng gá»­i email há»§y ná»¯a Ä‘á»ƒ trÃ¡nh sai quy trÃ¬nh.
    private const CANCELLABLE_STATUSES = ['PENDING', 'AWAITING_PAYMENT', 'CONFIRMED'];

    // BÆ°á»›c 1 cá»§a nghiá»‡p vá»¥:
    // Admin báº¥m há»§y -> há»‡ thá»‘ng táº¡o token, lÆ°u lÃ½ do há»§y, gá»­i email cho khÃ¡ch.
    // HÃ m nÃ y chÆ°a Ä‘á»•i status sang CANCELLED, vÃ¬ khÃ¡ch pháº£i xÃ¡c nháº­n trÆ°á»›c.
    public function requestCancellation(Order $order, ?string $reason = null): true|string
    {
        // Token tháº­t chá»‰ gá»­i qua email. Database chá»‰ lÆ°u token Ä‘Ã£ hash.
        // Náº¿u database bá»‹ lá»™, ngÆ°á»i khÃ¡c cÅ©ng khÃ´ng láº¥y Ä‘Æ°á»£c link xÃ¡c nháº­n tháº­t.
        $token = Str::random(72);
        $tokenHash = hash('sha256', $token);
        $reason = $this->normalizeReason($reason);

        $result = DB::transaction(function () use ($order, $reason, $tokenHash): array|string {
            // lockForUpdate khÃ³a dÃ²ng order Ä‘á»ƒ trÃ¡nh 2 admin/request cÃ¹ng xá»­ lÃ½ má»™t Ä‘Æ¡n má»™t lÃºc.
            $lockedOrder = Order::query()
                ->with(['user', 'items'])
                ->lockForUpdate()
                ->find($order->id);

            if (! $lockedOrder) {
                return 'KhÃ´ng tÃ¬m tháº¥y Ä‘Æ¡n hÃ ng cáº§n xá»­ lÃ½.';
            }

            if (! $this->canCancel($lockedOrder)) {
                return 'KhÃ´ng thá»ƒ yÃªu cáº§u há»§y Ä‘Æ¡n hÃ ng á»Ÿ tráº¡ng thÃ¡i hiá»‡n táº¡i.';
            }

            // Email láº¥y tá»« user cá»§a Ä‘Æ¡n hÃ ng vÃ¬ khÃ¡ch cáº§n nháº­n link xÃ¡c nháº­n há»§y.
            $email = $this->customerEmail($lockedOrder);

            if ($email === null) {
                return 'ÄÆ¡n hÃ ng nÃ y chÆ°a cÃ³ email khÃ¡ch hÃ ng Ä‘á»ƒ gá»­i xÃ¡c nháº­n há»§y.';
            }

            // LÆ°u tráº¡ng thÃ¡i "Ä‘ang chá» khÃ¡ch xÃ¡c nháº­n há»§y".
            // status váº«n giá»¯ nguyÃªn Ä‘á»ƒ bÃ¡o cÃ¡o khÃ´ng tÃ­nh lÃ  Ä‘Ã£ há»§y sá»›m.
            $lockedOrder->forceFill([
                'cancel_confirmation_token_hash' => $tokenHash,
                'cancel_reason' => $reason,
                'cancel_requested_at' => now(),
                'cancel_confirmed_at' => null,
            ])->save();

            return [
                'email' => $email,
                'order' => $lockedOrder->fresh(['user', 'items']),
            ];
        });

        if (is_string($result)) {
            return $result;
        }

        // Signed URL cÃ³ expires + signature.
        // KhÃ¡ch khÃ´ng cáº§n Ä‘Äƒng nháº­p váº«n xÃ¡c nháº­n Ä‘Æ°á»£c, nhÆ°ng khÃ´ng Ä‘Æ°á»£c sá»­a id/token trong URL.
        $url = URL::temporarySignedRoute(
            'orders.cancel-confirm.show',
            now()->addDays(3),
            [
                'order' => $result['order']->id,
                'token' => $token,
            ]
        );

        try {
            // Mail::raw dÃ¹ng email text Ä‘Æ¡n giáº£n, giá»‘ng style Ä‘ang cÃ³ trong AuthController.
            // Ná»™i dung email Ä‘Æ°á»£c gom trong emailBody() Ä‘á»ƒ hÃ m chÃ­nh dá»… Ä‘á»c.
            Mail::raw(
                $this->emailBody($result['order'], $url),
                fn ($message) => $message
                    ->to($result['email'])
                    ->subject('XÃ¡c nháº­n há»§y Ä‘Æ¡n hÃ ng ' . ($result['order']->order_code ?: '#' . $result['order']->id))
            );
        } catch (\Throwable $exception) {
            // Náº¿u gá»­i email lá»—i thÃ¬ xÃ³a token vá»«a lÆ°u.
            // NhÆ° váº­y admin cÃ³ thá»ƒ sá»­a SMTP rá»“i báº¥m gá»­i láº¡i, trÃ¡nh giá»¯ yÃªu cáº§u há»§y khÃ´ng ai nháº­n Ä‘Æ°á»£c.
            Order::whereKey($order->id)
                ->where('cancel_confirmation_token_hash', $tokenHash)
                ->update([
                    'cancel_confirmation_token_hash' => null,
                    'cancel_requested_at' => null,
                ]);

            Log::error('Order cancellation confirmation email could not be sent.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return 'ChÆ°a gá»­i Ä‘Æ°á»£c email xÃ¡c nháº­n há»§y. Vui lÃ²ng kiá»ƒm tra cáº¥u hÃ¬nh SMTP trong file .env.';
        }

        return true;
    }

    // DÃ¹ng cho trang GET vÃ  POST xÃ¡c nháº­n há»§y.
    // Tráº£ null nghÄ©a lÃ  link cÃ²n há»£p lá»‡; tráº£ chuá»—i nghÄ©a lÃ  cÃ³ lá»—i Ä‘á»ƒ view hiá»ƒn thá»‹ cho khÃ¡ch.
    public function pendingCancellationError(Order $order, string $token): ?string
    {
        if ($order->status === 'CANCELLED') {
            return 'ÄÆ¡n hÃ ng nÃ y Ä‘Ã£ Ä‘Æ°á»£c há»§y trÆ°á»›c Ä‘Ã³.';
        }

        if (! $this->canCancel($order)) {
            return 'ÄÆ¡n hÃ ng hiá»‡n khÃ´ng cÃ²n á»Ÿ tráº¡ng thÃ¡i Ä‘Æ°á»£c phÃ©p há»§y.';
        }

        // So sÃ¡nh token khÃ¡ch gá»­i lÃªn vá»›i hash Ä‘ang lÆ°u trong database.
        // hash_equals giÃºp trÃ¡nh so sÃ¡nh chuá»—i theo kiá»ƒu dá»… bá»‹ timing attack.
        if (! $order->cancel_confirmation_token_hash || ! hash_equals($order->cancel_confirmation_token_hash, hash('sha256', $token))) {
            return 'LiÃªn káº¿t xÃ¡c nháº­n há»§y khÃ´ng há»£p lá»‡.';
        }

        // Link tá»± háº¿t háº¡n sau 3 ngÃ y ká»ƒ tá»« lÃºc admin gá»­i yÃªu cáº§u há»§y.
        if (! $order->cancel_requested_at || $order->cancel_requested_at->lt(now()->subDays(3))) {
            return 'LiÃªn káº¿t xÃ¡c nháº­n há»§y Ä‘Ã£ háº¿t háº¡n. Vui lÃ²ng liÃªn há»‡ cá»­a hÃ ng Ä‘á»ƒ Ä‘Æ°á»£c há»— trá»£.';
        }

        return null;
    }

    // BÆ°á»›c 2 cá»§a nghiá»‡p vá»¥:
    // KhÃ¡ch báº¥m xÃ¡c nháº­n -> kiá»ƒm tra token láº§n cuá»‘i -> Ä‘á»•i status sang CANCELLED.
    public function confirmCancellation(Order $order, string $token): true|string
    {
        return DB::transaction(function () use ($order, $token): true|string {
            // KhÃ³a order trong lÃºc xÃ¡c nháº­n Ä‘á»ƒ trÃ¡nh khÃ¡ch/admin thao tÃ¡c trÃ¹ng thá»i Ä‘iá»ƒm.
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->find($order->id);

            if (! $lockedOrder) {
                return 'KhÃ´ng tÃ¬m tháº¥y Ä‘Æ¡n hÃ ng cáº§n há»§y.';
            }

            $error = $this->pendingCancellationError($lockedOrder, $token);

            if ($error !== null) {
                return $error;
            }

            // Chá»‰ tá»›i Ä‘Ã¢y Ä‘Æ¡n má»›i tháº­t sá»± bá»‹ há»§y.
            // XÃ³a token sau khi dÃ¹ng Ä‘á»ƒ link email khÃ´ng thá»ƒ dÃ¹ng láº¡i láº§n hai.
            $lockedOrder->forceFill([
                'status' => 'CANCELLED',
                'cancel_confirmed_at' => now(),
                'cancel_confirmation_token_hash' => null,
                'note' => $this->cancelNote($lockedOrder->note, $lockedOrder->cancel_reason),
            ])->save();

            return true;
        });
    }

    public function canCancel(Order $order): bool
    {
        // HÃ m nÃ y Ä‘Æ°á»£c AdminController vÃ  service dÃ¹ng chung Ä‘á»ƒ thá»‘ng nháº¥t Ä‘iá»u kiá»‡n há»§y.
        return in_array($order->status, self::CANCELLABLE_STATUSES, true);
    }

    private function customerEmail(Order $order): ?string
    {
        // Trim Ä‘á»ƒ trÃ¡nh email toÃ n khoáº£ng tráº¯ng váº«n bá»‹ xem lÃ  há»£p lá»‡.
        $email = trim((string) ($order->user?->email ?? ''));

        return $email === '' ? null : $email;
    }

    private function normalizeReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : $reason;
    }

    private function cancelNote(?string $currentNote, ?string $cancelReason): ?string
    {
        // Khi Ä‘Æ¡n Ä‘Æ°á»£c há»§y tháº­t sá»±, lÃ½ do há»§y Ä‘Æ°á»£c ná»‘i thÃªm vÃ o note Ä‘á»ƒ admin xem lá»‹ch sá»­.
        $cancelReason = $this->normalizeReason($cancelReason);

        if ($cancelReason === null) {
            return $currentNote;
        }

        $line = '[Há»§y Ä‘Æ¡n ' . now()->format('d/m/Y H:i') . '] ' . $cancelReason;
        $currentNote = trim((string) $currentNote);

        return $currentNote === '' ? $line : $currentNote . PHP_EOL . $line;
    }

    private function emailBody(Order $order, string $url): string
    {
        // Email ghi Ä‘á»§ thÃ´ng tin Ä‘Æ¡n hÃ ng Ä‘á»ƒ khÃ¡ch biáº¿t chÃ­nh xÃ¡c Ä‘Æ¡n nÃ o Ä‘ang Ä‘Æ°á»£c yÃªu cáº§u há»§y.
        // DÃ¹ng dá»¯ liá»‡u snapshot trong order_items, khÃ´ng phá»¥ thuá»™c tÃªn/giÃ¡ sáº£n pháº©m hiá»‡n táº¡i.
        $lines = [
            'Xin chÃ o ' . ($order->user?->full_name ?: $order->recipient_name) . ',',
            '',
            'Cá»­a hÃ ng gá»­i yÃªu cáº§u xÃ¡c nháº­n há»§y Ä‘Æ¡n hÃ ng dÆ°á»›i Ä‘Ã¢y. Náº¿u báº¡n Ä‘á»“ng Ã½ há»§y, vui lÃ²ng báº¥m vÃ o liÃªn káº¿t xÃ¡c nháº­n á»Ÿ cuá»‘i email.',
            '',
            'THÃ”NG TIN ÄÆ N HÃ€NG',
            'MÃ£ Ä‘Æ¡n: ' . ($order->order_code ?: '#' . $order->id),
            'NgÃ y Ä‘áº·t: ' . $order->created_at?->format('d/m/Y H:i'),
            'Tráº¡ng thÃ¡i hiá»‡n táº¡i: ' . $this->statusLabel($order->status),
            'PhÆ°Æ¡ng thá»©c thanh toÃ¡n: ' . $this->paymentLabel($order->payment_method),
            'Tráº¡ng thÃ¡i thanh toÃ¡n: ' . ($order->payment_status ?: '-'),
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

        return implode("\n", array_merge($lines, [
            '',
            'THANH TOÃN',
            'Tá»•ng tiá»n hÃ ng: ' . $this->money($order->subtotal_amount),
            'Giáº£m giÃ¡: ' . $this->money($order->discount_amount),
            'PhÃ­ váº­n chuyá»ƒn: ' . $this->money($order->shipping_fee),
            'Tá»•ng thanh toÃ¡n: ' . $this->money($order->total_amount),
            '',
            'LÃ½ do há»§y tá»« cá»­a hÃ ng: ' . ($order->cancel_reason ?: '-'),
            '',
            'Báº¥m vÃ o liÃªn káº¿t sau Ä‘á»ƒ xem láº¡i vÃ  xÃ¡c nháº­n há»§y Ä‘Æ¡n:',
            $url,
            '',
            'LiÃªn káº¿t cÃ³ hiá»‡u lá»±c trong 3 ngÃ y. Náº¿u báº¡n khÃ´ng Ä‘á»“ng Ã½ há»§y, vui lÃ²ng bá» qua email nÃ y hoáº·c liÃªn há»‡ cá»­a hÃ ng.',
        ]));
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 0, ',', '.') . 'Ä‘';
    }

    private function paymentLabel(?string $method): string
    {
        return match ($method) {
            'COD' => 'Thanh toÃ¡n khi nháº­n hÃ ng',
            'VNPAY' => 'VNPay',
            default => $method ?: '-',
        };
    }

    private function statusLabel(?string $status): string
    {
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
