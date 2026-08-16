@extends('admin.layouts.app')

@section('title', $returnRequest->return_code)

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $statusMeta = fn ($status) => match ($status) {
        'PENDING' => ['Chờ xét duyệt', 'warning', 'fa-clock'],
        'APPROVED' => ['Đã duyệt', 'info', 'fa-clipboard-check'],
        'RECEIVED' => ['Đã nhận hàng', 'moving', 'fa-box-open'],
        'COMPLETED' => ['Đã xử lý', 'success', 'fa-check-circle'],
        'REJECTED' => ['Từ chối', 'danger', 'fa-times-circle'],
        'CANCELLED' => ['Đã hủy', 'dark', 'fa-ban'],
        default => [$status, 'dark', 'fa-question-circle'],
    };
    $typeMeta = fn ($type) => $type === 'EXCHANGE'
        ? ['Đổi hàng', 'exchange', 'fa-exchange-alt']
        : ['Hoàn trả', 'return', 'fa-undo'];
    $damageLevel = function ($percent) {
        $percent = (int) $percent;
        if ($percent === 0) return ['Không hư hại', 'none'];
        if ($percent <= 20) return ['Nhẹ', 'light'];
        if ($percent <= 50) return ['Trung bình', 'medium'];
        if ($percent <= 80) return ['Nặng', 'heavy'];
        return ['Rất nặng', 'severe'];
    };
    [$statusText, $statusClass, $statusIcon] = $statusMeta($returnRequest->status);
    [$typeText, $typeClass, $typeIcon] = $typeMeta($returnRequest->type);
    $damageMap = $returnRequest->damageAssessments->keyBy('part_code');
    $order = $returnRequest->order;
    $customer = $returnRequest->user;
    $returnProgressSteps = [
        'PENDING' => ['Khách gửi yêu cầu', 'fa-paper-plane', 'pending'],
        'APPROVED' => ['Admin đã duyệt', 'fa-clipboard-check', 'info'],
        'RECEIVED' => ['Đã nhận hàng', 'fa-box-open', 'moving'],
        'COMPLETED' => [$returnRequest->type === 'EXCHANGE' ? 'Đổi hàng xong' : 'Hoàn trả xong', 'fa-check-circle', 'success'],
    ];
    $returnProgressOrder = array_keys($returnProgressSteps);
    $returnProgressStatus = in_array($returnRequest->status, ['REJECTED', 'CANCELLED'], true) ? null : $returnRequest->status;
    $returnProgressIndex = $returnProgressStatus ? array_search($returnProgressStatus, $returnProgressOrder, true) : false;
    $returnEndMeta = [
        'REJECTED' => ['Yêu cầu bị từ chối', 'danger', 'fa-times-circle'],
        'CANCELLED' => ['Yêu cầu đã hủy', 'dark', 'fa-ban'],
    ];
@endphp

@push('styles')
<style>
.rr-page{background:#f5f7fb;min-height:100vh;padding:24px;color:#111827}
.rr-topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}
.rr-title h4{font-size:24px;font-weight:900;line-height:1.2;margin:0;color:#101828}
.rr-title p{color:#667085;font-size:13px;margin:7px 0 0}
.rr-actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
.rr-btn{align-items:center;border:1px solid transparent;border-radius:7px;cursor:pointer;display:inline-flex;font-size:13px;font-weight:800;gap:7px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}
.rr-btn.primary{background:#111827;color:#fff}.rr-btn.light{background:#fff;border-color:#d0d5dd;color:#344054}.rr-btn.success{background:#0f766e;color:#fff}.rr-btn.danger{background:#fff1f2;border-color:#fecdd3;color:#be123c}
.rr-hero{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);display:grid;gap:14px;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:18px;padding:16px}
.rr-info{border:1px solid #eef2f6;border-radius:8px;padding:13px;background:#fbfcfd;min-height:88px}
.rr-info span{color:#667085;display:block;font-size:12px;font-weight:900;margin-bottom:7px;text-transform:uppercase}
.rr-info strong{color:#101828;display:block;font-size:16px;font-weight:900;line-height:1.35;word-break:break-word}.rr-info small{color:#667085;display:block;font-size:12px;margin-top:4px}
.rr-badge,.rr-type{align-items:center;border:1px solid transparent;border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;gap:6px;min-height:29px;padding:0 10px;white-space:nowrap}
.rr-badge.warning{background:#fffbeb;border-color:#fde68a;color:#92400e}.rr-badge.info{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}.rr-badge.moving{background:#f5f3ff;border-color:#ddd6fe;color:#6d28d9}.rr-badge.success{background:#ecfdf5;border-color:#a7f3d0;color:#047857}.rr-badge.danger{background:#fef2f2;border-color:#fecaca;color:#b91c1c}.rr-badge.dark{background:#f3f4f6;border-color:#d1d5db;color:#374151}
.rr-type.return{background:#e0f2fe;border-color:#bae6fd;color:#075985}.rr-type.exchange{background:#f5f3ff;border-color:#ddd6fe;color:#6d28d9}
.rr-flow-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);margin-bottom:18px;padding:18px}
.rr-flow{display:grid;gap:8px;grid-template-columns:repeat(4,minmax(0,1fr));position:relative}
.rr-flow:before{background:#e5e7eb;content:"";height:2px;left:7%;position:absolute;right:7%;top:18px}
.rr-flow-step{--step-color:#9ca3af;position:relative;text-align:center;z-index:1;color:#98a2b3;font-size:12px;font-weight:900;line-height:1.3}
.rr-flow-step.pending{--step-color:#92400e}.rr-flow-step.info{--step-color:#1d4ed8}.rr-flow-step.moving{--step-color:#6d28d9}.rr-flow-step.success{--step-color:#047857}
.rr-flow-step-icon{align-items:center;background:#fff;border:2px solid #d1d5db;border-radius:50%;color:#9ca3af;display:flex;height:36px;justify-content:center;margin:0 auto 7px;width:36px}
.rr-flow-step.active{color:var(--step-color)}.rr-flow-step.active .rr-flow-step-icon{background:var(--step-color);border-color:var(--step-color);color:#fff}
.rr-flow-end{align-items:center;border-radius:8px;display:flex;font-size:13px;font-weight:900;gap:10px;justify-content:center;min-height:52px}.rr-flow-end.danger{background:#fef2f2;color:#b91c1c}.rr-flow-end.dark{background:#f3f4f6;color:#374151}
.rr-layout{align-items:start;display:grid;gap:18px;grid-template-columns:minmax(0,1fr) 420px}.rr-stack{display:grid;gap:18px}
.rr-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}
.rr-card-head{align-items:center;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:15px 18px}.rr-card-head h6{color:#101828;font-size:16px;font-weight:900;margin:0}.rr-card-head span{color:#667085;font-size:12px;font-weight:800}
.rr-card-body{padding:18px}.rr-note{background:#fbfcfd;border:1px solid #eef2f6;border-radius:8px;color:#344054;font-size:14px;line-height:1.65;min-height:64px;padding:13px;white-space:pre-line}
.rr-product-list{display:grid;gap:12px}.rr-product{align-items:center;border:1px solid #eef2f6;border-radius:8px;display:grid;gap:14px;grid-template-columns:74px minmax(0,1fr) auto;padding:12px}.rr-product img{background:#f8fafc;border:1px solid #e4e7ec;border-radius:8px;height:74px;object-fit:cover;width:74px}.rr-product strong{color:#101828;display:block;font-size:15px;font-weight:900;line-height:1.35}.rr-product p{color:#667085;font-size:12px;margin:5px 0 0}.rr-product .rr-product-money{font-weight:900;text-align:right;white-space:nowrap}.rr-product .rr-product-note{grid-column:2 / 4;color:#475467;font-size:13px;line-height:1.55;margin-top:-3px}
.rr-image-grid{display:grid;gap:12px;grid-template-columns:repeat(auto-fill,minmax(132px,1fr))}.rr-image-grid a{border:1px solid #e4e7ec;border-radius:8px;display:block;overflow:hidden;background:#f8fafc}.rr-image-grid img{aspect-ratio:1/1;display:block;height:auto;object-fit:cover;width:100%;transition:.15s ease}.rr-image-grid a:hover img{transform:scale(1.03)}
.rr-side{position:sticky;top:92px}.rr-form-row{display:grid;gap:7px;margin-bottom:14px}.rr-form-row label{color:#344054;font-size:13px;font-weight:900}.rr-select,.rr-textarea,.rr-input{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#101828;font-size:14px;font-weight:700;padding:9px 11px;width:100%}.rr-select,.rr-input{min-height:42px}.rr-textarea{line-height:1.55;min-height:118px;resize:vertical}
.rr-quick{display:grid;gap:8px;grid-template-columns:repeat(2,minmax(0,1fr));margin:0 0 14px}.rr-status-btn{background:#fff;border:1px solid #d0d5dd;border-radius:8px;color:#344054;cursor:pointer;font-size:12px;font-weight:900;min-height:38px;padding:8px}.rr-status-btn.active{background:#111827;border-color:#111827;color:#fff}
.rr-process-card{display:flex;flex-direction:column;max-height:calc(100vh - 112px)}
.rr-process-card .rr-card-head{flex:0 0 auto}.rr-process-body{display:flex;flex:1 1 auto;min-height:0;padding:0}.rr-form{display:flex;flex:1 1 auto;flex-direction:column;min-height:0;width:100%}.rr-form-scroll{flex:1 1 auto;min-height:0;overflow:auto;padding:18px 18px 8px;scrollbar-width:thin}.rr-form-actions{background:#fff;border-top:1px solid #eef2f6;box-shadow:0 -10px 20px rgba(16,24,40,.06);display:grid;flex:0 0 auto;padding:12px 18px}.rr-form-actions .rr-btn{min-height:46px}
.rr-damage-grid{display:grid;gap:10px}.rr-damage{border:1px solid #e4e7ec;border-radius:8px;padding:12px;background:#fff}.rr-damage-top{align-items:center;display:flex;gap:10px;justify-content:space-between;margin-bottom:9px}.rr-damage-title{color:#101828;font-size:13px;font-weight:900}.rr-damage-level{border-radius:999px;font-size:11px;font-weight:900;padding:5px 8px}.rr-damage-level.none{background:#f3f4f6;color:#475467}.rr-damage-level.light{background:#ecfdf5;color:#047857}.rr-damage-level.medium{background:#fffbeb;color:#92400e}.rr-damage-level.heavy{background:#fff7ed;color:#c2410c}.rr-damage-level.severe{background:#fef2f2;color:#b91c1c}.rr-damage-fields{display:grid;gap:8px;grid-template-columns:98px minmax(0,1fr)}
.rr-history{display:grid;gap:10px}.rr-history-item{align-items:flex-start;border:1px solid #eef2f6;border-radius:8px;display:flex;gap:10px;padding:11px}.rr-history-icon{align-items:center;background:#eff6ff;border-radius:999px;color:#1d4ed8;display:inline-flex;flex:0 0 34px;height:34px;justify-content:center;width:34px}.rr-history strong{color:#101828;display:block;font-size:13px}.rr-history span{color:#667085;display:block;font-size:12px;margin-top:3px}
.rr-empty{align-items:center;color:#667085;display:flex;font-size:14px;justify-content:center;min-height:92px;text-align:center}
@media(max-width:1180px){.rr-hero{grid-template-columns:repeat(2,minmax(0,1fr))}.rr-layout{grid-template-columns:1fr}.rr-side{position:static}.rr-process-card{max-height:none}.rr-process-body,.rr-form{display:block}.rr-form-scroll{overflow:visible}.rr-form-actions{bottom:0;position:sticky;z-index:5}}@media(max-width:680px){.rr-page{padding:14px}.rr-topbar{align-items:flex-start;flex-direction:column}.rr-actions,.rr-btn{width:100%}.rr-hero{grid-template-columns:1fr}.rr-flow{gap:4px}.rr-flow-step{font-size:11px}.rr-product{grid-template-columns:62px minmax(0,1fr)}.rr-product img{height:62px;width:62px}.rr-product .rr-product-money{grid-column:2;text-align:left}.rr-product .rr-product-note{grid-column:1 / 3}.rr-quick{grid-template-columns:1fr}.rr-damage-fields{grid-template-columns:1fr}.rr-form-scroll{padding:14px 14px 6px}.rr-form-actions{padding:10px 14px}}
</style>
@endpush

@section('content')
<div class="rr-page">
    <div class="rr-topbar">
        <div class="rr-title">
            <h4>Chi tiết yêu cầu {{ $returnRequest->return_code }}</h4>
            <p>Theo dõi lý do, sản phẩm gửi về, ảnh minh chứng và kết luận xử lý cho khách.</p>
        </div>
        <div class="rr-actions">
            <span class="rr-type {{ $typeClass }}"><i class="fas {{ $typeIcon }}"></i>{{ $typeText }}</span>
            <span class="rr-badge {{ $statusClass }}"><i class="fas {{ $statusIcon }}"></i>{{ $statusText }}</span>
            <a href="{{ route('admin.returns.index') }}" class="rr-btn light"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>
    </div>

    <div class="rr-hero">
        <div class="rr-info">
            <span>Khách hàng</span>
            <strong>{{ $customer->full_name ?? $order->recipient_name ?? '-' }}</strong>
            <small>{{ $customer->email ?? $order->recipient_phone ?? '' }}</small>
        </div>
        <div class="rr-info">
            <span>Đơn hàng</span>
            <strong>{{ $order->order_code ?? '-' }}</strong>
            <small>{{ $order?->created_at?->format('d/m/Y H:i') ?: 'Không có ngày đặt' }}</small>
        </div>
        <div class="rr-info">
            <span>Ngày gửi yêu cầu</span>
            <strong>{{ $returnRequest->requested_at?->format('d/m/Y H:i') ?: '-' }}</strong>
            <small>{{ $returnRequest->reason->name ?? 'Chưa chọn lý do' }}</small>
        </div>
        <div class="rr-info">
            <span>Giá trị đơn</span>
            <strong>{{ $money($order->total_amount ?? 0) }}</strong>
            <small>{{ $returnRequest->items->sum('quantity') }} sản phẩm trong yêu cầu</small>
        </div>
    </div>

    <div class="rr-flow-card">
        @if (isset($returnEndMeta[$returnRequest->status]))
            @php
                [$endText, $endClass, $endIcon] = $returnEndMeta[$returnRequest->status];
            @endphp
            <div class="rr-flow-end {{ $endClass }}">
                <i class="fas {{ $endIcon }}"></i>
                {{ $endText }}
            </div>
        @else
            <div class="rr-flow">
                @foreach ($returnProgressSteps as $stepStatus => [$stepLabel, $stepIcon, $stepClass])
                    <div class="rr-flow-step {{ $stepClass }} {{ $returnProgressIndex !== false && $returnProgressIndex >= $loop->index ? 'active' : '' }}">
                        <div class="rr-flow-step-icon"><i class="fas {{ $stepIcon }}"></i></div>
                        {{ $stepLabel }}
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="rr-layout">
        <div class="rr-stack">
            <div class="rr-card">
                <div class="rr-card-head">
                    <h6>Lý do và mô tả của khách</h6>
                    <span>{{ $returnRequest->reason->name ?? 'Không có lý do' }}</span>
                </div>
                <div class="rr-card-body">
                    <div class="rr-note">{{ $returnRequest->reason_detail ?: 'Khách chưa nhập mô tả chi tiết.' }}</div>
                </div>
            </div>

            <div class="rr-card">
                <div class="rr-card-head">
                    <h6>Sản phẩm cần xử lý</h6>
                    <span>{{ $returnRequest->items->count() }} dòng sản phẩm</span>
                </div>
                <div class="rr-card-body">
                    <div class="rr-product-list">
                        @forelse ($returnRequest->items as $item)
                            @php
                                $orderItem = $item->orderItem;
                                $product = $orderItem?->product;
                                $image = $product?->image_url ?? asset('upload/no-image.jpg');
                                $variantText = collect([$orderItem?->color_name, $orderItem?->lens_size_name, $orderItem?->sku])->filter()->implode(' / ');
                            @endphp
                            <div class="rr-product">
                                <img src="{{ $image }}" alt="{{ $orderItem?->product_name ?? 'Sản phẩm' }}">
                                <div>
                                    <strong>{{ $orderItem?->product_name ?? 'Sản phẩm không còn tồn tại' }}</strong>
                                    <div style="font-size: 11px; font-family: var(--font-mono, monospace); color: #5a636b; margin: 2px 0;">
                                        @if (!empty($product?->frame_size))
                                            <span>{{ str_replace([' ', '□', '-'], [' ', '▭', '-'], $product->frame_size) }}</span>
                                        @else
                                            <span>52▭18-145</span>
                                        @endif
                                    </div>
                                    <p>{{ $variantText ?: 'Không có biến thể' }}</p>
                                </div>
                                <div class="rr-product-money">
                                    <div>{{ number_format($item->quantity) }} cái</div>
                                    <div>{{ $money($orderItem?->unit_price ?? 0) }}</div>
                                </div>
                                <div class="rr-product-note">
                                    <strong>Tình trạng khách ghi:</strong>
                                    {{ $item->condition_note ?: 'Chưa có ghi chú tình trạng.' }}
                                </div>
                            </div>
                        @empty
                            <div class="rr-empty">Yêu cầu này chưa có sản phẩm.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            @if ($returnRequest->images->isNotEmpty())
                <div class="rr-card">
                    <div class="rr-card-head">
                        <h6>Ảnh minh chứng</h6>
                        <span>{{ $returnRequest->images->count() }} ảnh</span>
                    </div>
                    <div class="rr-card-body">
                        <div class="rr-image-grid">
                            @foreach ($returnRequest->images as $image)
                                @php
                                    $imageUrl = asset('upload/' . ltrim($image->image_url, '/'));
                                @endphp
                                <a href="{{ $imageUrl }}" target="_blank" title="Mở ảnh lớn">
                                    <img src="{{ $imageUrl }}" alt="Ảnh minh chứng {{ $loop->iteration }}">
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="rr-card">
                <div class="rr-card-head">
                    <h6>Đánh giá đã lưu</h6>
                    <span>{{ $returnRequest->damageAssessments->count() }} bộ phận</span>
                </div>
                <div class="rr-card-body">
                    @if ($returnRequest->damageAssessments->isNotEmpty())
                        <div class="rr-damage-grid">
                            @foreach ($returnRequest->damageAssessments as $damage)
                                @php
                                    [$levelText, $levelClass] = $damageLevel($damage->damage_percent);
                                @endphp
                                <div class="rr-damage">
                                    <div class="rr-damage-top">
                                        <span class="rr-damage-title">{{ $damage->part_name }}</span>
                                        <span class="rr-damage-level {{ $levelClass }}">{{ $levelText }} - {{ (int) $damage->damage_percent }}%</span>
                                    </div>
                                    <div class="rr-note">{{ $damage->description ?: 'Không có mô tả.' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rr-empty">Chưa có đánh giá tình trạng kính.</div>
                    @endif
                </div>
            </div>
        </div>

        <aside class="rr-side">
            <div class="rr-card rr-process-card">
                <div class="rr-card-head">
                    <h6>Xử lý yêu cầu</h6>
                    <span>Cập nhật cho khách</span>
                </div>
                <div class="rr-card-body rr-process-body">
                    <form method="post" action="{{ route('admin.returns.update', $returnRequest) }}" class="rr-form" id="return-process-form">
                        @csrf
                        @method('PUT')

                        <div class="rr-form-scroll">
                            <div class="rr-form-row">
                                <label>Chọn nhanh trạng thái</label>
                                <div class="rr-quick" data-status-buttons>
                                    @foreach (['APPROVED' => 'Duyệt', 'RECEIVED' => 'Đã nhận hàng', 'COMPLETED' => 'Hoàn tất', 'REJECTED' => 'Từ chối'] as $value => $label)
                                        <button class="rr-status-btn {{ $returnRequest->status === $value ? 'active' : '' }}" type="button" data-status="{{ $value }}">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rr-form-row">
                                <label for="return-status">Trạng thái xử lý</label>
                                <select class="rr-select" name="status" id="return-status">
                                    @foreach (['PENDING' => 'Chờ xét duyệt', 'APPROVED' => 'Đã duyệt', 'REJECTED' => 'Từ chối', 'RECEIVED' => 'Đã nhận hàng', 'COMPLETED' => 'Đã xử lý', 'CANCELLED' => 'Đã hủy'] as $value => $label)
                                        <option value="{{ $value }}" @selected($returnRequest->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="text-danger small fw-bold mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="rr-form-row">
                                <label for="admin-note">Kết luận / ghi chú phản hồi</label>
                                <textarea class="rr-textarea" name="admin_note" id="admin-note" maxlength="1000" placeholder="Ghi rõ lý do duyệt, từ chối, hướng xử lý hoặc ghi chú hoàn tiền/đổi hàng.">{{ old('admin_note', $returnRequest->admin_note) }}</textarea>
                            </div>

                            <div class="rr-form-row">
                                <label>Đánh giá từng bộ phận kính</label>
                                <div class="rr-damage-grid">
                                    @foreach ($damageParts as $partCode => $partName)
                                        @php
                                            $currentDamage = $damageMap->get($partCode);
                                            $currentPercent = old('damage.' . $partCode . '.percent', $currentDamage?->damage_percent);
                                            [$levelText, $levelClass] = $damageLevel($currentPercent);
                                        @endphp
                                        <div class="rr-damage">
                                            <div class="rr-damage-top">
                                                <span class="rr-damage-title">{{ $partName }}</span>
                                                <span class="rr-damage-level {{ $levelClass }}">{{ $levelText }}</span>
                                            </div>
                                            <div class="rr-damage-fields">
                                                <input class="rr-input" type="number" name="damage[{{ $partCode }}][percent]" min="0" max="100" step="1" value="{{ $currentPercent }}" placeholder="% hư">
                                                <input class="rr-input" type="text" name="damage[{{ $partCode }}][description]" maxlength="1000" value="{{ old('damage.' . $partCode . '.description', $currentDamage?->description) }}" placeholder="Mô tả ngắn">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="rr-form-actions">
                            <button class="rr-btn success w-100" type="submit" onclick="return confirm('Cập nhật yêu cầu hoàn/đổi này?')">
                                <i class="fas fa-save"></i> Lưu xử lý
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="rr-card mt-3">
                <div class="rr-card-head">
                    <h6>Lịch xử lý</h6>
                </div>
                <div class="rr-card-body">
                    <div class="rr-history">
                        <div class="rr-history-item">
                            <span class="rr-history-icon"><i class="fas fa-calendar-alt"></i></span>
                            <div><strong>Khách gửi yêu cầu</strong><span>{{ $returnRequest->requested_at?->format('d/m/Y H:i') ?: '-' }}</span></div>
                        </div>
                        <div class="rr-history-item">
                            <span class="rr-history-icon"><i class="fas fa-user-check"></i></span>
                            <div><strong>Admin xem xét</strong><span>{{ $returnRequest->reviewed_at?->format('d/m/Y H:i') ?: 'Chưa xem xét' }}</span></div>
                        </div>
                        <div class="rr-history-item">
                            <span class="rr-history-icon"><i class="fas fa-check"></i></span>
                            <div><strong>Hoàn tất xử lý</strong><span>{{ $returnRequest->completed_at?->format('d/m/Y H:i') ?: 'Chưa hoàn tất' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-status]').forEach(function (button) {
    button.addEventListener('click', function () {
        var select = document.getElementById('return-status');
        select.value = button.dataset.status;
        document.querySelectorAll('[data-status]').forEach(function (item) {
            item.classList.toggle('active', item === button);
        });
    });
});
</script>
@endpush
