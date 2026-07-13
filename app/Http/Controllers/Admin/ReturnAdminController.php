<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnDamageAssessment;
use App\Models\ReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReturnAdminController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.returns.index', [
            'requests' => ReturnRequest::with(['order', 'user', 'reason'])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
                ->when($request->filled('keyword'), function ($query) use ($request) {
                    $keyword = '%' . $request->keyword . '%';
                    $query->where(function ($inner) use ($keyword) {
                        $inner->where('return_code', 'like', $keyword)
                            ->orWhereHas('order', fn ($order) => $order->where('order_code', 'like', $keyword))
                            ->orWhereHas('user', fn ($user) => $user->where('full_name', 'like', $keyword)->orWhere('email', 'like', $keyword))
                            ->orWhereHas('reason', fn ($reason) => $reason->where('name', 'like', $keyword));
                    });
                })
                ->latest('requested_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function show(ReturnRequest $return): View
    {
        return view('admin.returns.show', [
            'returnRequest' => $return->load(['order.items', 'user', 'reason', 'items.orderItem.product', 'images', 'damageAssessments']),
            'damageParts' => $this->damagePartOptions(),
        ]);
    }

    public function update(Request $request, ReturnRequest $return): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:PENDING,APPROVED,REJECTED,RECEIVED,COMPLETED,CANCELLED'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'damage' => ['nullable', 'array'],
            'damage.*.percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'damage.*.description' => ['nullable', 'string', 'max:1000'],
        ]);

        $return->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_at' => now(),
            'completed_at' => $data['status'] === 'COMPLETED' ? now() : $return->completed_at,
        ]);

        $this->saveDamageAssessments($return, $data['damage'] ?? []);

        return back()->with('success', 'Đã cập nhật yêu cầu hoàn đổi.');
    }

    private function damagePartOptions(): array
    {
        return [
            'FRAME_LEFT' => 'Gọng trái',
            'FRAME_RIGHT' => 'Gọng phải',
            'LENS_LEFT' => 'Tròng trái',
            'LENS_RIGHT' => 'Tròng phải',
            'HINGE' => 'Bản lề / ốc vít',
            'NOSE_PAD' => 'Đệm mũi',
            'ACCESSORY' => 'Phụ kiện / hộp kính',
            'OTHER' => 'Khác',
        ];
    }

    private function saveDamageAssessments(ReturnRequest $return, array $damageRows): void
    {
        ReturnDamageAssessment::where('return_request_id', $return->id)->delete();

        foreach ($this->damagePartOptions() as $partCode => $partName) {
            $row = $damageRows[$partCode] ?? [];
            $rawPercent = $row['percent'] ?? null;
            $description = trim((string) ($row['description'] ?? ''));

            if (($rawPercent === null || $rawPercent === '') && $description === '') {
                continue;
            }

            $percent = max(0, min(100, (int) $rawPercent));

            ReturnDamageAssessment::create([
                'return_request_id' => $return->id,
                'part_code' => $partCode,
                'part_name' => $partName,
                'damage_percent' => $percent,
                'damage_level' => $this->damageLevelFromPercent($percent),
                'description' => $description,
                'assessed_by' => Auth::id() ?? 1,
                'assessed_at' => now(),
            ]);
        }
    }

    private function damageLevelFromPercent(int $percent): string
    {
        if ($percent === 0) {
            return 'NONE';
        }

        if ($percent <= 20) {
            return 'LIGHT';
        }

        if ($percent <= 50) {
            return 'MEDIUM';
        }

        if ($percent <= 80) {
            return 'HEAVY';
        }

        return 'SEVERE';
    }
}
