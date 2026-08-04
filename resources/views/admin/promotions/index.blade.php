@extends('admin.layouts.app')

@section('title', 'Khuyến mãi')

@php
    $num = fn ($value) => number_format((float) $value, 0, ',', '.');
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    $statusBadge = fn ($status) => match ($status) {
        'ACTIVE' => ['Đang bật', 'success'],
        'INACTIVE' => ['Tạm tắt', 'muted'],
        'SCHEDULED' => ['Lên lịch', 'info'],
        'EXPIRED' => ['Hết hạn', 'danger'],
        default => [$status ?: '-', 'muted'],
    };
    $isEditingPromotion = (bool) $editingPromotion;
    $promotionForm = $editingPromotion;
    $promotionValue = fn (string $key, $default = '') => old($key, $promotionForm?->{$key} ?? $default);
    $promotionDate = function (string $key, string $default = '') use ($promotionForm) {
        $value = old($key);
        if ($value !== null) {
            return $value;
        }

        $source = $promotionForm?->{$key};
        return $source ? \Illuminate\Support\Carbon::parse($source)->format('Y-m-d\TH:i') : $default;
    };
    $promotionStackable = (bool) (int) old('stackable', $promotionForm?->stackable ? 1 : 0);
@endphp

@push('styles')
<style>
.promo-page{background:#f5f7fb;color:#172033;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.promo-inner{max-width:1500px;margin:0 auto}.promo-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.promo-title small{color:#0f8a7a;font-size:13px;font-weight:900;text-transform:uppercase}.promo-title h4{color:#111827;font-size:27px;font-weight:900;line-height:1.2;margin:6px 0}.promo-title p{color:#667085;font-size:14px;margin:0}.promo-actions{display:flex;gap:9px;justify-content:flex-end}.promo-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.promo-btn.primary{background:#0f8a7a;border-color:#0f8a7a;color:#fff}.promo-btn:hover{filter:brightness(.98);color:inherit}.promo-btn.primary:hover{color:#fff}
.promo-summary{display:grid;gap:12px;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:14px}.promo-stat{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);min-height:96px;padding:15px}.promo-stat i{align-items:center;border-radius:8px;display:inline-flex;height:34px;justify-content:center;width:34px}.promo-stat span{color:#667085;display:block;font-size:12px;font-weight:900;margin-top:11px}.promo-stat strong{color:#111827;display:block;font-size:24px;font-weight:900;line-height:1;margin-top:5px}.promo-stat:nth-child(1) i{background:#eef2ff;color:#4f46e5}.promo-stat:nth-child(2) i{background:#dcfce7;color:#166534}.promo-stat:nth-child(3) i{background:#e0f2fe;color:#0369a1}.promo-stat:nth-child(4) i{background:#fee2e2;color:#991b1b}
.promo-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.promo-layout{display:grid;gap:14px;grid-template-columns:360px minmax(0,1fr);padding:14px}.promo-form{background:#fbfcfd;border:1px solid #eef2f6;border-radius:8px;padding:14px}.promo-form h6,.promo-table-head h6{color:#111827;font-size:16px;font-weight:900;margin:0 0 10px}.promo-field{display:grid;gap:6px;margin-bottom:10px}.promo-field label{color:#667085;font-size:11px;font-weight:900;text-transform:uppercase}.promo-field input,.promo-field select,.promo-field textarea{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:38px;padding:8px 10px;width:100%}.promo-field textarea{min-height:76px;resize:vertical}.promo-field-help{color:#667085;font-size:12px;font-weight:700;line-height:1.4}.promo-code-row{display:grid;gap:8px;grid-template-columns:minmax(0,1fr) 92px}.promo-suggest{background:#f0fdfa;border:1px solid #99f6e4;border-radius:8px;margin:4px 0 12px;padding:10px}.promo-suggest-title{align-items:center;color:#0f766e;display:flex;font-size:12px;font-weight:900;gap:7px;margin-bottom:8px;text-transform:uppercase}.promo-preset-grid{display:grid;gap:8px;grid-template-columns:repeat(3,minmax(0,1fr))}.promo-preset{background:#fff;border:1px solid #99f6e4;border-radius:7px;color:#0f172a;cursor:pointer;display:grid;font-size:12px;font-weight:800;gap:2px;min-height:58px;padding:8px;text-align:left}.promo-preset strong{color:#0f766e;font-size:18px;line-height:1}.promo-preset span{color:#667085;font-size:11px;font-weight:800;line-height:1.35}.promo-preset:hover{border-color:#0f8a7a;box-shadow:0 6px 16px rgba(15,138,122,.12)}.promo-auto-note{color:#0f766e;font-size:12px;font-weight:800;line-height:1.4;margin:8px 0 0}.promo-check{align-items:center;color:#344054;display:flex;font-size:13px;font-weight:800;gap:9px;margin:2px 0 12px}.promo-check input{height:16px;width:16px}
.promo-table-head{align-items:center;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 14px}.promo-table-head small{color:#667085;font-size:12px;font-weight:800}.promo-table-wrap{overflow-x:auto}.promo-table{border-collapse:collapse;min-width:900px;width:100%}.promo-table th{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:11px;font-weight:900;letter-spacing:.03em;padding:10px 12px;text-align:left;text-transform:uppercase;white-space:nowrap}.promo-table td{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;padding:11px 12px;vertical-align:middle}.promo-table tr:hover td{background:#fafafa}.promo-name{color:#111827;font-weight:900}.promo-sub{color:#667085;font-size:12px;line-height:1.45;margin-top:3px}.promo-money{color:#111827;font-weight:900;white-space:nowrap}.promo-badge{border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;min-height:25px;padding:4px 9px;white-space:nowrap}.promo-badge.success{background:#dcfce7;color:#166534}.promo-badge.danger{background:#fee2e2;color:#991b1b}.promo-badge.info{background:#dbeafe;color:#1d4ed8}.promo-badge.muted{background:#f3f4f6;color:#4b5563}.promo-row-actions{display:flex;gap:7px;justify-content:flex-end}.promo-mini-form{display:inline}.promo-icon-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;height:32px;justify-content:center;width:34px}.promo-icon-btn:hover{background:#f8fafc}.promo-empty{color:#667085;padding:32px 12px;text-align:center}.promo-alert{background:#f0fdfa;border:1px solid #99f6e4;border-radius:8px;color:#0f766e;font-size:13px;font-weight:800;line-height:1.5;margin-bottom:12px;padding:11px 12px}
@media(max-width:1200px){.promo-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.promo-layout{grid-template-columns:1fr}.promo-form{max-width:none}}@media(max-width:760px){.promo-page{margin:-24px -12px 0;padding:16px 12px}.promo-head{flex-direction:column}.promo-actions,.promo-btn{width:100%}.promo-summary{grid-template-columns:1fr}.promo-table{min-width:760px}}
</style>
@endpush

@section('content')
<div class="promo-page">
    <div class="promo-inner">
        <div class="promo-head">
            <div class="promo-title">
                <small>Mắt kính admin</small>
                <h4>Quản lý khuyến mãi</h4>
                <p>Tạo, cập nhật, bật tắt và theo dõi lượt sử dụng mã giảm giá cho đơn hàng.</p>
            </div>
            <div class="promo-actions">
                <a class="promo-btn" href="{{ route('admin.business.index') }}"><i class="fa fa-briefcase"></i> Nghiệp vụ</a>
                <a class="promo-btn primary" href="#promotionForm"><i class="fa fa-plus"></i> Thêm mã</a>
            </div>
        </div>

        <div class="promo-summary">
            <div class="promo-stat"><i class="fa fa-tags"></i><span>Tổng mã</span><strong>{{ $num($summary['total']) }}</strong></div>
            <div class="promo-stat"><i class="fa fa-toggle-on"></i><span>Đang bật</span><strong>{{ $num($summary['active']) }}</strong></div>
            <div class="promo-stat"><i class="fa fa-calendar-alt"></i><span>Lên lịch</span><strong>{{ $num($summary['scheduled']) }}</strong></div>
            <div class="promo-stat"><i class="fa fa-hourglass-end"></i><span>Hết hạn</span><strong>{{ $num($summary['expired']) }}</strong></div>
        </div>

        @if (session('success'))
            <div class="promo-alert">{{ session('success') }}</div>
        @endif

        <div class="promo-card">
            <div class="promo-layout">
                <form class="promo-form" id="promotionForm" method="post" action="{{ route('admin.promotions.store') }}">
                    @csrf
                    <input type="hidden" name="_promotion_action" value="save">
                    <input type="hidden" name="id" value="{{ $isEditingPromotion ? $promotionForm->id : 0 }}">
                    <h6>{{ $isEditingPromotion ? 'Sửa khuyến mãi: ' . $promotionForm->promotion_code : 'Thêm khuyến mãi' }}</h6>
                    @if ($isEditingPromotion)
                        <a class="promo-btn w-100 mb-2" href="{{ route('admin.promotions.index') }}"><i class="fa fa-times"></i> Hủy sửa</a>
                    @endif

                    <div class="promo-field">
                        <label>Mã giảm giá</label>
                        <div class="promo-code-row">
                            <input id="promo-code-input" name="promotion_code" value="{{ old('promotion_code', $isEditingPromotion ? $promotionForm->promotion_code : '') }}" placeholder="Bỏ trống để tự tạo" maxlength="20" autocomplete="off" oninput="this.value = this.value.toUpperCase()" @readonly($isEditingPromotion)>
                            <button class="promo-btn" type="button" id="promo-code-generate" @disabled($isEditingPromotion)>Tự tạo</button>
                        </div>
                        <div class="promo-field-help">Có thể bỏ trống, hệ thống sẽ tự sinh mã khi lưu.</div>
                    </div>
                    <div class="promo-suggest" aria-label="Gợi ý cấu hình mã giảm giá">
                        <div class="promo-suggest-title"><i class="fa fa-magic"></i> Gợi ý nhanh</div>
                        <div class="promo-preset-grid">
                            <button class="promo-preset" type="button" data-promo-preset="10"><strong>10%</strong><span>Đơn từ 300.000đ<br>Tối đa 200.000đ</span></button>
                            <button class="promo-preset" type="button" data-promo-preset="20"><strong>20%</strong><span>Đơn từ 500.000đ<br>Tối đa 1.000.000đ</span></button>
                            <button class="promo-preset" type="button" data-promo-preset="30"><strong>30%</strong><span>Đơn từ 1.000.000đ<br>Tối đa 1.500.000đ</span></button>
                        </div>
                        <p class="promo-auto-note" id="promo-auto-note">Bấm một mức giảm, form sẽ tự điền giá trị giảm, đơn tối thiểu và giảm tối đa.</p>
                    </div>
                    <div class="promo-field"><label>Tên chương trình</label><input id="promo-name-input" name="name" value="{{ $promotionValue('name') }}" required></div>
                    <div class="promo-field"><label>Mô tả</label><textarea name="description">{{ $promotionValue('description') }}</textarea></div>
                    <div class="promo-field">
                        <label>Kiểu giảm</label>
                        <select id="promo-discount-type" name="discount_type">
                            <option value="PERCENT" @selected($promotionValue('discount_type', 'PERCENT') === 'PERCENT')>Phần trăm</option>
                            <option value="FIXED_AMOUNT" @selected($promotionValue('discount_type', 'PERCENT') === 'FIXED_AMOUNT')>Số tiền</option>
                        </select>
                    </div>
                    <div class="promo-field"><label>Giá trị giảm</label><input id="promo-discount-value" type="number" min="0" step="0.1" name="discount_value" value="{{ $promotionValue('discount_value') }}" required></div>
                    <div class="promo-field"><label>Đơn tối thiểu</label><input id="promo-min-order" type="number" min="0" step="1000" name="min_order_amount" value="{{ $promotionValue('min_order_amount', 0) }}"></div>
                    <div class="promo-field"><label>Giảm tối đa</label><input id="promo-max-discount" type="number" min="0" step="1000" name="max_discount_amount" value="{{ $promotionValue('max_discount_amount') }}" placeholder="Bỏ trống nếu không giới hạn"></div>
                    <div class="promo-field"><label>Tổng số lượt</label><input type="number" min="1" step="1" name="usage_limit" value="{{ $promotionValue('usage_limit') }}" placeholder="Bỏ trống nếu không giới hạn"></div>
                    <div class="promo-field"><label>Mỗi khách</label><input type="number" min="1" step="1" name="usage_per_user" value="{{ $promotionValue('usage_per_user', 1) }}"></div>
                    <div class="promo-field"><label>Bắt đầu</label><input type="datetime-local" name="start_at" value="{{ $promotionDate('start_at', now()->format('Y-m-d\TH:i')) }}" required></div>
                    <div class="promo-field"><label>Kết thúc</label><input type="datetime-local" name="end_at" value="{{ $promotionDate('end_at') }}"></div>
                    <div class="promo-field">
                        <label>Trạng thái</label>
                        <select name="status">
                            <option value="ACTIVE" @selected($promotionValue('status', 'ACTIVE') === 'ACTIVE')>Đang bật</option>
                            <option value="SCHEDULED" @selected($promotionValue('status', 'ACTIVE') === 'SCHEDULED')>Lên lịch</option>
                            <option value="INACTIVE" @selected($promotionValue('status', 'ACTIVE') === 'INACTIVE')>Tạm tắt</option>
                            <option value="EXPIRED" @selected($promotionValue('status', 'ACTIVE') === 'EXPIRED')>Hết hạn</option>
                        </select>
                    </div>
                    <input type="hidden" name="stackable" value="0">
                    <label class="promo-check"><input type="checkbox" name="stackable" value="1" @checked($promotionStackable)> Cho phép dùng cùng ưu đãi khác</label>
                    <button class="promo-btn primary w-100" type="submit"><i class="fa fa-save"></i> {{ $isEditingPromotion ? 'Cập nhật khuyến mãi' : 'Lưu khuyến mãi' }}</button>
                </form>

                <div class="promo-card">
                    <div class="promo-table-head"><h6>Danh sách khuyến mãi</h6><small>{{ $promotions->count() }} dòng mới nhất</small></div>
                    <div class="promo-table-wrap">
                        <table class="promo-table">
                            <thead><tr><th>Mã</th><th>Chương trình</th><th>Giảm</th><th>Điều kiện</th><th>Thời gian</th><th>Đã dùng</th><th>Trạng thái</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($promotions as $promotion)
                                @php
                                    [$label, $class] = $statusBadge($promotion->status);
                                @endphp
                                <tr>
                                    <td>{{ $promotion->promotion_code }}</td>
                                    <td><div class="promo-name">{{ $promotion->name }}</div><div class="promo-sub">{{ $promotion->stackable ? 'Cho cộng dồn' : 'Không cộng dồn' }}</div></td>
                                    <td class="promo-money">{{ $promotion->discount_type === 'PERCENT' ? $num($promotion->discount_value) . '%' : $num($promotion->discount_value) . 'đ' }}</td>
                                    <td><div>Tối thiểu {{ $num($promotion->min_order_amount) }}đ</div><div class="promo-sub">Tối đa {{ $promotion->max_discount_amount ? $num($promotion->max_discount_amount) . 'đ' : 'không giới hạn' }}</div></td>
                                    <td><div>{{ $date($promotion->start_at) }}</div><div class="promo-sub">{{ $date($promotion->end_at) }}</div></td>
                                    <td><div>{{ $num($promotion->used_count) }}{{ $promotion->usage_limit ? ' / ' . $num($promotion->usage_limit) : '' }}</div><div class="promo-sub">Mỗi khách {{ $num($promotion->usage_per_user ?: 1) }}</div></td>
                                    <td><span class="promo-badge {{ $class }}">{{ $label }}</span></td>
                                    <td>
                                        <div class="promo-row-actions">
                                            <a class="promo-icon-btn" href="{{ route('admin.promotions.index', ['edit' => $promotion->id]) }}#promotionForm" title="Sửa"><i class="fa fa-edit"></i></a>
                                            <form class="promo-mini-form" method="post" action="{{ route('admin.promotions.store') }}">
                                                @csrf
                                                <input type="hidden" name="_promotion_action" value="toggle">
                                                <input type="hidden" name="id" value="{{ $promotion->id }}">
                                                <button class="promo-icon-btn" title="Bật/tắt"><i class="fa fa-power-off"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="promo-empty" colspan="8">Chưa có khuyến mãi.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const presets = {
        10: { min: 300000, max: 200000, name: 'Giảm 10% cho đơn từ 300.000đ' },
        20: { min: 500000, max: 1000000, name: 'Giảm 20% cho đơn từ 500.000đ' },
        30: { min: 1000000, max: 1500000, name: 'Giảm 30% cho đơn từ 1.000.000đ' },
    };

    const form = document.getElementById('promotionForm');
    const codeInput = document.getElementById('promo-code-input');
    const codeGenerate = document.getElementById('promo-code-generate');
    const nameInput = document.getElementById('promo-name-input');
    const discountType = document.getElementById('promo-discount-type');
    const discountValue = document.getElementById('promo-discount-value');
    const minOrder = document.getElementById('promo-min-order');
    const maxDiscount = document.getElementById('promo-max-discount');
    const autoNote = document.getElementById('promo-auto-note');

    if (!form || !discountType || !discountValue || !minOrder || !maxDiscount) {
        return;
    }

    function money(value) {
        return Number(value).toLocaleString('vi-VN') + 'đ';
    }

    function generateCode(percent) {
        const suffix = Math.random().toString(36).slice(2, 5).toUpperCase();
        return 'SALE' + percent + suffix;
    }

    function applyPreset(percent, shouldGenerateCode) {
        const preset = presets[percent];

        if (!preset) {
            return;
        }

        discountType.value = 'PERCENT';
        discountValue.value = percent;
        minOrder.value = preset.min;
        maxDiscount.value = preset.max;

        if (nameInput && !nameInput.value.trim()) {
            nameInput.value = preset.name;
        }

        if (shouldGenerateCode && codeInput && !codeInput.readOnly) {
            codeInput.value = generateCode(percent);
        }

        if (autoNote) {
            autoNote.textContent = percent + '%: đơn tối thiểu ' + money(preset.min) + ', giảm tối đa ' + money(preset.max) + '.';
        }
    }

    document.querySelectorAll('[data-promo-preset]').forEach(function (button) {
        button.addEventListener('click', function () {
            applyPreset(button.dataset.promoPreset, true);
        });
    });

    discountValue.addEventListener('change', function () {
        if (discountType.value !== 'PERCENT') {
            return;
        }

        applyPreset(String(Number(discountValue.value)), false);
    });

    discountType.addEventListener('change', function () {
        if (discountType.value === 'PERCENT') {
            applyPreset(String(Number(discountValue.value)), false);
        } else if (autoNote) {
            autoNote.textContent = 'Kiểu số tiền sẽ giữ nguyên các ô bạn đã nhập.';
        }
    });

    if (codeGenerate && codeInput) {
        codeGenerate.addEventListener('click', function () {
            if (codeInput.readOnly) {
                return;
            }

            const percent = presets[Number(discountValue.value)] ? Number(discountValue.value) : 20;
            codeInput.value = generateCode(percent);
        });
    }
});
</script>
@endpush
