<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\ProductVariant;
use App\Services\OrderConfirmationEmailService;
use App\Services\VnPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class VnPayController extends Controller
{
    private const MAX_CART_QUANTITY = 10;

    public function return(Request $request, VnPayService $vnPay, OrderConfirmationEmailService $orderConfirmationEmail): RedirectResponse
    {
        $result = $vnPay->verify($request->query());
        $txnRef = (string) $result['txn_ref'];
        $order = $this->findOrder($result);
        $draft = $order ? null : $this->pendingDraft($txnRef);

        if (! $result['is_valid']) {
            $this->forgetPendingDraft($txnRef);

            return redirect()->route('checkout.index')->with('error', 'Chữ ký thanh toán VNPay không hợp lệ.');
        }

        if (! $order && ! $draft) {
            return redirect()->route('checkout.index')->with('error', 'Không tìm thấy giao dịch VNPay hoặc giao dịch đã hết hạn.');
        }

        $expectedAmount = (float) ($order?->total_amount ?? $draft['total_amount']);
        if (abs($expectedAmount - (float) $result['amount']) > 0.01) {
            if ($order) {
                $this->cancelOrderPayment($order, $result, 'AMOUNT_MISMATCH', 'Số tiền VNPay trả về không khớp với đơn hàng.');
            } else {
                $this->forgetPendingDraft($txnRef);
            }

            return redirect()->route('checkout.index')->with('error', 'Số tiền VNPay trả về không khớp với đơn hàng.');
        }

        if (! $result['is_success']) {
            if ($order) {
                $this->cancelOrderPayment($order, $result, (string) $result['response_code'], (string) $result['message']);
            } else {
                $this->forgetPendingDraft($txnRef);
            }

            return redirect()->route('checkout.index')->with('error', 'Thanh toán VNPay chưa thành công: ' . $result['message']);
        }

        try {
            $order = $order
                ? $this->markPaid($order, $result)
                : $this->createPaidOrderFromDraft($draft, $result);
        } catch (RuntimeException $exception) {
            return redirect()->route('checkout.index')->with('error', $exception->getMessage());
        }

        $this->forgetPendingDraft($txnRef);
        session()->forget('cart');
        $orderConfirmationEmail->send($order);

        if (Auth::id() === $order->user_id) {
            return redirect()->route('account.orders.show', $order)->with('success', 'Thanh toán VNPay thành công.');
        }

        return redirect()->route('home')->with('success', 'Thanh toán VNPay thành công.');
    }

    public function ipn(Request $request, VnPayService $vnPay, OrderConfirmationEmailService $orderConfirmationEmail): JsonResponse
    {
        $result = $vnPay->verify($request->all());
        $txnRef = (string) $result['txn_ref'];
        $order = $this->findOrder($result);
        $draft = $order ? null : $this->pendingDraft($txnRef);

        if (! $result['is_valid']) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        if (! $order && ! $draft) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $expectedAmount = (float) ($order?->total_amount ?? $draft['total_amount']);
        if (abs($expectedAmount - (float) $result['amount']) > 0.01) {
            if ($order) {
                $this->markFailed($order, $result, 'AMOUNT_MISMATCH', 'VNPay returned amount does not match order total.');
            } else {
                $this->forgetPendingDraft($txnRef);
            }

            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        if ($order && $order->payment_status === 'PAID') {
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        try {
            if ($result['is_success']) {
                $order = $order ? $this->markPaid($order, $result) : $this->createPaidOrderFromDraft($draft, $result);
                $this->forgetPendingDraft($txnRef);
                $orderConfirmationEmail->send($order);
            } elseif ($order) {
                $this->markFailed($order, $result, (string) $result['response_code'], (string) $result['message']);
            } else {
                $this->forgetPendingDraft($txnRef);
            }
        } catch (RuntimeException) {
            return response()->json(['RspCode' => '99', 'Message' => 'Cannot confirm order']);
        }

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }

    private function findOrder(array $result): ?Order
    {
        return Order::where('order_code', $result['txn_ref'] ?? '')->first();
    }

    private function pendingDraft(string $txnRef): ?array
    {
        if ($txnRef === '') {
            return null;
        }

        return session("pending_vnpay_checkouts.{$txnRef}") ?: Cache::get("pending_vnpay_checkout:{$txnRef}");
    }

    private function forgetPendingDraft(string $txnRef): void
    {
        if ($txnRef === '') {
            return;
        }

        session()->forget("pending_vnpay_checkouts.{$txnRef}");
        Cache::forget("pending_vnpay_checkout:{$txnRef}");
    }

    private function createPaidOrderFromDraft(array $draft, array $result): Order
    {
        return DB::transaction(function () use ($draft, $result) {
            $existing = Order::where('order_code', $draft['order_code'])->lockForUpdate()->first();
            if ($existing) {
                return $this->markPaid($existing, $result);
            }

            $cart = collect($draft['cart'])
                ->mapWithKeys(fn ($quantity, $variantId) => [(int) $variantId => (int) $quantity])
                ->all();

            if (array_sum(array_map('intval', $cart)) > self::MAX_CART_QUANTITY) {
                throw new RuntimeException('Mỗi đơn chỉ đặt tối đa ' . self::MAX_CART_QUANTITY . ' sản phẩm. Vui lòng giảm số lượng trong giỏ.');
            }

            $variants = ProductVariant::query()
                ->with(['product', 'color', 'lensSize'])
                ->whereIn('id', array_keys($cart))
                ->get();

            if ($variants->isEmpty() || $variants->count() !== count($cart)) {
                throw new RuntimeException('Sản phẩm trong giỏ hàng không còn hợp lệ, chưa thể tạo đơn sau thanh toán.');
            }

            $subtotal = $variants->sum(fn (ProductVariant $variant) => $variant->display_price * (int) $cart[$variant->id]);
            $shippingFee = (float) ($draft['shipping_fee'] ?? 0);
            $discountAmount = min((float) ($draft['discount_amount'] ?? 0), (float) $subtotal);
            $totalAmount = max(0, $subtotal - $discountAmount) + $shippingFee;

            if (abs($totalAmount - (float) $result['amount']) > 0.01) {
                throw new RuntimeException('Số tiền thanh toán không khớp với giỏ hàng hiện tại.');
            }

            $data = $draft['data'];
            $order = Order::create([
                'order_code' => (string) $draft['order_code'],
                'user_id' => $draft['user_id'],
                'recipient_name' => (string) $data['recipient_name'],
                'recipient_phone' => (string) $data['recipient_phone'],
                'shipping_address' => (string) $draft['shipping_address'],
                'payment_method' => 'VNPAY',
                'payment_status' => 'PAID',
                'status' => 'PENDING',
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'promotion_id' => $draft['promotion_id'] ?? null,
                'note' => $this->appendPaymentNote($data['note'] ?? null, (string) $result['transaction_no']),
            ]);

            if (! empty($draft['promotion_id'])) {
                Promotion::whereKey($draft['promotion_id'])->increment('used_count');
            }

            foreach ($variants as $variant) {
                $quantity = (int) $cart[$variant->id];
                $unitPrice = $variant->display_price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'color_name' => $variant->color->name ?? null,
                    'lens_size_name' => $variant->lensSize->name ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'total_price' => $unitPrice * $quantity,
                ]);
            }

            $this->saveSuccessfulPayment($order, $result);

            return $order;
        });
    }

    private function markPaid(Order $order, array $result): Order
    {
        $order->update([
            'payment_status' => 'PAID',
            'status' => 'PENDING',
            'note' => $this->appendPaymentNote($order->note, (string) $result['transaction_no']),
        ]);

        $this->saveSuccessfulPayment($order, $result);

        return $order->refresh();
    }

    private function cancelOrderPayment(Order $order, array $result, string $code, string $message): void
    {
        if ($order->payment_status !== 'PAID') {
            $order->update(['status' => 'CANCELLED']);
            $this->markFailed($order, $result, $code, $message);
        }
    }

    private function saveSuccessfulPayment(Order $order, array $result): void
    {
        Payment::updateOrCreate([
            'payment_code' => $order->order_code,
        ], [
            'order_id' => $order->id,
            'method' => 'VNPAY',
            'amount' => $order->total_amount,
            'status' => 'SUCCESS',
            'paid_at' => now(),
            'transaction_no' => (string) $result['transaction_no'],
            'bank_code' => (string) ($result['raw_data']['vnp_BankCode'] ?? ''),
            'response_code' => (string) $result['response_code'],
            'response_message' => Str::limit((string) $result['message'], 255, ''),
        ]);
    }

    private function markFailed(Order $order, array $result, string $code, string $message): void
    {
        Payment::updateOrCreate([
            'payment_code' => $order->order_code,
        ], [
            'order_id' => $order->id,
            'method' => 'VNPAY',
            'amount' => $order->total_amount,
            'status' => 'FAILED',
            'transaction_no' => (string) ($result['transaction_no'] ?? ''),
            'bank_code' => (string) ($result['raw_data']['vnp_BankCode'] ?? ''),
            'response_code' => $code,
            'response_message' => Str::limit($message, 255, ''),
        ]);
    }

    private function appendPaymentNote(?string $note, string $transactionNo): ?string
    {
        if ($transactionNo === '') {
            return $note;
        }

        $paymentNote = 'VNPay transaction: ' . $transactionNo;
        $currentNote = trim((string) $note);

        if (str_contains($currentNote, $paymentNote)) {
            return $currentNote;
        }

        return $currentNote === '' ? $paymentNote : $currentNote . ' | ' . $paymentNote;
    }
}
