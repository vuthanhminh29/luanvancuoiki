<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ReturnReason;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReturnRequestController extends Controller
{
    public function index(): View
    {
        return view('returns.index', [
            'requests' => ReturnRequest::with(['order', 'reason'])
                ->where('user_id', Auth::id())
                ->latest('requested_at')
                ->paginate(10),
        ]);
    }

    public function create(Order $order): View
    {
        abort_unless($order->user_id === Auth::id(), 403);

        return view('returns.create', [
            'order' => $order->load('items'),
            'reasons' => ReturnReason::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $data = $request->validate([
            'type' => ['required', 'in:RETURN,EXCHANGE'],
            'reason_id' => ['required', 'exists:return_reasons,id'],
            'order_item_id' => ['required', 'exists:order_items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason_detail' => ['nullable', 'string', 'max:1000'],
            'condition_note' => ['nullable', 'string', 'max:500'],
        ]);

        $item = $order->items()->whereKey($data['order_item_id'])->firstOrFail();

        $returnRequest = DB::transaction(function () use ($data, $order, $item) {
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
