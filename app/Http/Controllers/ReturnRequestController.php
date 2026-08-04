<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnReason;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReturnRequestController extends Controller
{
    // Chá»‰ cÃ¡c Ä‘Æ¡n á»Ÿ nhá»¯ng tráº¡ng thÃ¡i nÃ y má»›i Ä‘Æ°á»£c khÃ¡ch gá»­i yÃªu cáº§u hoÃ n/Ä‘á»•i.
    // DELIVERED: Ä‘Æ¡n Ä‘Ã£ giao xong.
    // RETURN_PENDING: Ä‘Æ¡n Ä‘Ã£ cÃ³ yÃªu cáº§u hoÃ n/Ä‘á»•i Ä‘ang xá»­ lÃ½.
    // RETURNED/EXCHANGED: Ä‘Æ¡n Ä‘Ã£ xá»­ lÃ½ má»™t yÃªu cáº§u trÆ°á»›c Ä‘Ã³ nhÆ°ng váº«n cÃ³ thá»ƒ cÃ²n sáº£n pháº©m/sá»‘ lÆ°á»£ng khÃ¡c Ä‘á»ƒ táº¡o tiáº¿p.
    private const ELIGIBLE_ORDER_STATUSES = ['DELIVERED', 'RETURN_PENDING', 'RETURNED', 'EXCHANGED'];

    // Hiá»ƒn thá»‹ danh sÃ¡ch yÃªu cáº§u hoÃ n/Ä‘á»•i cá»§a khÃ¡ch Ä‘ang Ä‘Äƒng nháº­p.
    // Route: GET /hoan-doi.
    public function index(): View
    {
        return view('returns.index', [
            // with(['order', 'reason']) load sáºµn Ä‘Æ¡n hÃ ng vÃ  lÃ½ do Ä‘á»ƒ view hiá»ƒn thá»‹.
            'requests' => ReturnRequest::with(['order', 'reason'])
                // Chá»‰ láº¥y yÃªu cáº§u cá»§a chÃ­nh user Ä‘ang Ä‘Äƒng nháº­p.
                ->where('user_id', Auth::id())
                // YÃªu cáº§u má»›i nháº¥t lÃªn trÆ°á»›c.
                ->latest('requested_at')
                // Má»—i trang 10 yÃªu cáº§u.
                ->paginate(10),
        ]);
    }

    // Má»Ÿ form táº¡o yÃªu cáº§u hoÃ n/Ä‘á»•i cho má»™t Ä‘Æ¡n hÃ ng cá»¥ thá»ƒ.
    // Route: GET /hoan-doi/don-hang/{order}.
    public function create(Request $request, Order $order): View|RedirectResponse
    {
        // Cháº·n truy cáº­p náº¿u Ä‘Æ¡n hÃ ng khÃ´ng thuá»™c tÃ i khoáº£n Ä‘ang Ä‘Äƒng nháº­p.
        abort_unless($order->user_id === Auth::id(), 403);

        // Kiá»ƒm tra tráº¡ng thÃ¡i Ä‘Æ¡n cÃ³ Ä‘á»§ Ä‘iá»u kiá»‡n hoÃ n/Ä‘á»•i khÃ´ng.
        if (! $this->isOrderEligible($order)) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('error', $this->returnIneligibleMessage($order));
        }

        // Load cÃ¡c dÃ²ng sáº£n pháº©m trong Ä‘Æ¡n vÃ  quan há»‡ product.
        $order->load('items.product');

        // Lá»c ra nhá»¯ng dÃ²ng sáº£n pháº©m cÃ²n sá»‘ lÆ°á»£ng cÃ³ thá»ƒ hoÃ n/Ä‘á»•i.
        $returnableItems = $this->returnableItems($order);

        // Náº¿u táº¥t cáº£ sáº£n pháº©m Ä‘Ã£ Ä‘Æ°á»£c yÃªu cáº§u Ä‘á»§ sá»‘ lÆ°á»£ng thÃ¬ khÃ´ng má»Ÿ form ná»¯a.
        if ($returnableItems->isEmpty()) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('error', 'CÃ¡c sáº£n pháº©m trong Ä‘Æ¡n Ä‘Ã£ Ä‘Æ°á»£c yÃªu cáº§u hoÃ n/Ä‘á»•i Ä‘á»§ sá»‘ lÆ°á»£ng.');
        }

        $requestedItemId = (int) $request->query('item');
        $selectedItem = $returnableItems->firstWhere('id', $requestedItemId) ?? $returnableItems->first();

        // Má»Ÿ view resources/views/returns/create.blade.php.
        return view('returns.create', [
            'order' => $order,
            'returnableItems' => $returnableItems,
            'selectedOrderItemId' => $selectedItem?->id,
            // remainingQuantities lÃ  máº£ng order_item_id => sá»‘ lÆ°á»£ng cÃ²n Ä‘Æ°á»£c yÃªu cáº§u.
            'remainingQuantities' => $returnableItems->mapWithKeys(fn (OrderItem $item) => [
                $item->id => $this->remainingReturnQuantity($order, $item),
            ]),
            // Chá»‰ hiá»ƒn thá»‹ lÃ½ do Ä‘ang ACTIVE Ä‘á»ƒ khÃ¡ch chá»n.
            'reasons' => ReturnReason::active()->orderBy('name')->get(),
        ]);
    }

    // LÆ°u yÃªu cáº§u hoÃ n/Ä‘á»•i do khÃ¡ch gá»­i lÃªn.
    // Route: POST /hoan-doi/don-hang/{order}.
    public function store(Request $request, Order $order): RedirectResponse
    {
        // ÄÆ¡n pháº£i thuá»™c Ä‘Ãºng khÃ¡ch hÃ ng hiá»‡n táº¡i.
        abort_unless($order->user_id === Auth::id(), 403);

        // ÄÆ¡n pháº£i Ä‘ang á»Ÿ tráº¡ng thÃ¡i Ä‘Æ°á»£c phÃ©p táº¡o yÃªu cáº§u.
        if (! $this->isOrderEligible($order)) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('error', $this->returnIneligibleMessage($order));
        }

        // Kiá»ƒm tra dá»¯ liá»‡u form hoÃ n/Ä‘á»•i.
        $data = $request->validate([
            // type chá»‰ Ä‘Æ°á»£c RETURN hoáº·c EXCHANGE theo bÃ¡o cÃ¡o.
            'type' => ['required', Rule::in(['RETURN', 'EXCHANGE'])],
            // reason_id pháº£i tá»“n táº¡i trong return_reasons vÃ  Ä‘ang ACTIVE.
            'reason_id' => ['required', Rule::exists('return_reasons', 'id')->where('status', 'ACTIVE')],
            // order_item_id lÃ  dÃ²ng sáº£n pháº©m khÃ¡ch muá»‘n hoÃ n/Ä‘á»•i.
            'order_item_id' => ['required', 'exists:order_items,id'],
            // Sá»‘ lÆ°á»£ng yÃªu cáº§u tá»‘i thiá»ƒu 1.
            'quantity' => ['required', 'integer', 'min:1'],
            // MÃ´ táº£ lÃ½ do tá»‘i Ä‘a 1000 kÃ½ tá»±.
            'reason_detail' => ['nullable', 'string', 'max:1000'],
            // Ghi chÃº tÃ¬nh tráº¡ng tá»‘i Ä‘a 500 kÃ½ tá»±.
            'condition_note' => ['nullable', 'string', 'max:500'],
        ], [
            'type.required' => 'Vui lÃ²ng chá»n loáº¡i yÃªu cáº§u.',
            'type.in' => 'Loáº¡i yÃªu cáº§u khÃ´ng há»£p lá»‡.',
            'reason_id.required' => 'Vui lÃ²ng chá»n lÃ½ do hoÃ n/Ä‘á»•i.',
            'reason_id.exists' => 'LÃ½ do hoÃ n/Ä‘á»•i khÃ´ng há»£p lá»‡ hoáº·c Ä‘Ã£ ngá»«ng sá»­ dá»¥ng.',
            'order_item_id.required' => 'Vui lÃ²ng chá»n sáº£n pháº©m cáº§n hoÃ n/Ä‘á»•i.',
            'order_item_id.exists' => 'Sáº£n pháº©m Ä‘Æ°á»£c chá»n khÃ´ng há»£p lá»‡.',
            'quantity.required' => 'Vui lÃ²ng nháº­p sá»‘ lÆ°á»£ng cáº§n hoÃ n/Ä‘á»•i.',
            'quantity.integer' => 'Sá»‘ lÆ°á»£ng pháº£i lÃ  sá»‘ nguyÃªn.',
            'quantity.min' => 'Sá»‘ lÆ°á»£ng yÃªu cáº§u tá»‘i thiá»ƒu lÃ  1.',
            'reason_detail.max' => 'MÃ´ táº£ lÃ½ do tá»‘i Ä‘a 1.000 kÃ½ tá»±.',
            'condition_note.max' => 'Ghi chÃº tÃ¬nh tráº¡ng tá»‘i Ä‘a 500 kÃ½ tá»±.',
        ]);

        // Láº¥y dÃ²ng sáº£n pháº©m tá»« chÃ­nh quan há»‡ items cá»§a Ä‘Æ¡n.
        // Viá»‡c nÃ y Ä‘áº£m báº£o sáº£n pháº©m Ä‘Æ°á»£c chá»n thuá»™c Ä‘Ãºng Ä‘Æ¡n hÃ ng.
        $item = $order->items()->whereKey($data['order_item_id'])->firstOrFail();

        // TÃ­nh sá»‘ lÆ°á»£ng cÃ²n láº¡i cÃ³ thá»ƒ hoÃ n/Ä‘á»•i cá»§a dÃ²ng sáº£n pháº©m nÃ y.
        $remainingQuantity = $this->remainingReturnQuantity($order, $item);

        // Náº¿u khÃ´ng cÃ²n sá»‘ lÆ°á»£ng nÃ o thÃ¬ tá»« chá»‘i.
        if ($remainingQuantity < 1) {
            return back()
                ->withErrors(['order_item_id' => 'Sáº£n pháº©m nÃ y Ä‘Ã£ Ä‘Æ°á»£c yÃªu cáº§u hoÃ n/Ä‘á»•i Ä‘á»§ sá»‘ lÆ°á»£ng.'])
                ->withInput();
        }

        // KhÃ´ng cho khÃ¡ch yÃªu cáº§u nhiá»u hÆ¡n sá»‘ lÆ°á»£ng cÃ²n Ä‘Æ°á»£c phÃ©p.
        if ((int) $data['quantity'] > $remainingQuantity) {
            return back()
                ->withErrors(['quantity' => 'Sá»‘ lÆ°á»£ng yÃªu cáº§u khÃ´ng Ä‘Æ°á»£c vÆ°á»£t quÃ¡ sá»‘ lÆ°á»£ng cÃ²n cÃ³ thá»ƒ hoÃ n/Ä‘á»•i lÃ  ' . $remainingQuantity . '.'])
                ->withInput();
        }

        // Táº¡o yÃªu cáº§u vÃ  chi tiáº¿t yÃªu cáº§u trong transaction.
        $returnRequest = DB::transaction(function () use ($data, $order, $item) {
            // Táº¡o báº£n ghi chÃ­nh trong báº£ng return_requests.
            $returnRequest = ReturnRequest::create([
                'return_code' => 'RTN' . now()->format('YmdHis') . Str::upper(Str::random(3)),
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'type' => $data['type'],
                'reason_id' => $data['reason_id'],
                'reason_detail' => $data['reason_detail'] ?? null,
                'status' => 'PENDING',
            ]);

            ReturnRequestItem::create([
                'return_request_id' => $returnRequest->id,
                'order_item_id' => $item->id,
                'quantity' => min((int) $data['quantity'], (int) $item->quantity),
                'condition_note' => $data['condition_note'] ?? null,
            ]);

            return $returnRequest;
        });

        return redirect()->route('returns.show', $returnRequest)->with('success', 'Đã gửi yêu cầu hoàn đổi.');
    }

    public function show(ReturnRequest $return): View
    {
        abort_unless($return->user_id === Auth::id(), 403);

        return view('returns.show', [
            'returnRequest' => $return->load(['order.items', 'items.orderItem', 'reason']),
        ]);
    }
}
