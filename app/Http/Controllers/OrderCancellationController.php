<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderCancellationController extends Controller
{
    // Controller nÃ y xá»­ lÃ½ link khÃ¡ch báº¥m tá»« email xÃ¡c nháº­n há»§y.
    // Admin khÃ´ng Ä‘i qua controller nÃ y; admin chá»‰ táº¡o yÃªu cáº§u há»§y vÃ  gá»­i email.
    public function __construct(private readonly OrderCancellationService $cancellations)
    {
    }

    // GET /don-hang/{order}/xac-nhan-huy/{token}
    // KhÃ¡ch má»Ÿ link trong email thÃ¬ vÃ o Ä‘Ã¢y trÆ°á»›c Ä‘á»ƒ xem láº¡i toÃ n bá»™ thÃ´ng tin Ä‘Æ¡n.
    // Route cÃ³ middleware signed nÃªn náº¿u link bá»‹ sá»­a order/token/expires thÃ¬ Laravel cháº·n.
    public function show(Order $order, string $token): View
    {
        // Load user vÃ  items Ä‘á»ƒ trang xÃ¡c nháº­n hiá»ƒn thá»‹ khÃ¡ch hÃ ng + danh sÃ¡ch sáº£n pháº©m.
        $order->load(['user', 'items']);

        return view('orders.cancel-confirmation', [
            'order' => $order,
            'token' => $token,
            // Náº¿u token sai, háº¿t háº¡n hoáº·c Ä‘Æ¡n khÃ´ng cÃ²n Ä‘Æ°á»£c há»§y, view sáº½ hiá»‡n lá»—i.
            'error' => $this->cancellations->pendingCancellationError($order, $token),
            // confirmed=false nghÄ©a lÃ  khÃ¡ch má»›i Ä‘ang xem trang, chÆ°a báº¥m nÃºt xÃ¡c nháº­n.
            'confirmed' => false,
        ]);
    }

    // POST /don-hang/{order}/xac-nhan-huy/{token}
    // KhÃ¡ch báº¥m nÃºt "XÃ¡c nháº­n há»§y Ä‘Æ¡n" trÃªn trang xÃ¡c nháº­n thÃ¬ vÃ o hÃ m nÃ y.
    // Chá»‰ hÃ m nÃ y má»›i tháº­t sá»± Ä‘á»•i status cá»§a Ä‘Æ¡n sang CANCELLED.
    public function confirm(Request $request, Order $order, string $token): View|RedirectResponse
    {
        $order->load(['user', 'items']);

        // Service kiá»ƒm tra láº¡i token/tráº¡ng thÃ¡i trong transaction rá»“i má»›i há»§y.
        // Kiá»ƒm tra láº¡i á»Ÿ backend lÃ  báº¯t buá»™c vÃ¬ ngÆ°á»i dÃ¹ng cÃ³ thá»ƒ tá»± gá»­i request POST.
        $result = $this->cancellations->confirmCancellation($order, $token);

        if ($result !== true) {
            return view('orders.cancel-confirmation', [
                // fresh() láº¥y láº¡i dá»¯ liá»‡u má»›i nháº¥t sau khi service vá»«a kiá»ƒm tra/xá»­ lÃ½.
                'order' => $order->fresh(['user', 'items']) ?? $order,
                'token' => $token,
                'error' => $result,
                'confirmed' => false,
            ]);
        }

        $order = $order->fresh(['user', 'items']);

        // Náº¿u khÃ¡ch Ä‘ang Ä‘Äƒng nháº­p Ä‘Ãºng tÃ i khoáº£n chá»§ Ä‘Æ¡n thÃ¬ Ä‘Æ°a há» vá» trang chi tiáº¿t Ä‘Æ¡n.
        // Náº¿u há» báº¥m email khi chÆ°a Ä‘Äƒng nháº­p, váº«n cho xÃ¡c nháº­n báº±ng signed link vÃ  hiá»‡n trang káº¿t quáº£.
        if ($request->user()?->id === $order?->user_id) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('success', 'Báº¡n Ä‘Ã£ xÃ¡c nháº­n há»§y Ä‘Æ¡n hÃ ng thÃ nh cÃ´ng.');
        }

        return view('orders.cancel-confirmation', [
            'order' => $order,
            'token' => $token,
            'error' => null,
            'confirmed' => true,
        ]);
    }
}
