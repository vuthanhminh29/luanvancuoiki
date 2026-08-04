@extends('admin.layouts.app')

@section('title', $order->order_code ?: 'Đơn hàng #' . $order->id)

@php
    $statusLabels = $statusLabels ?? [
        'PENDING' => ['Chờ xác nhận', 'pending', 'fa-clock'],
        'AWAITING_PAYMENT' => ['Chờ thanh toán', 'payment', 'fa-credit-card'],
        'CONFIRMED' => ['Đã xác nhận', 'confirmed', 'fa-clipboard-check'],
        'DELIVERING' => ['Đang giao', 'shipping', 'fa-truck'],
        'DELIVERED' => ['Giao thành công', 'success', 'fa-check-circle'],
        'CANCELLED' => ['Đã hủy', 'danger', 'fa-times-circle'],
        'RETURN_PENDING' => ['Chờ hoàn/đổi', 'return', 'fa-undo-alt'],
        'RETURNED' => ['Đã hoàn trả', 'dark', 'fa-undo'],
        'EXCHANGED' => ['Đã đổi hàng', 'success', 'fa-exchange-alt'],
    ];
    $paymentMap = ['COD' => 'Thanh toán khi nhận hàng', 'VNPAY' => 'VNPay'];
    $paymentStatusMap = [
        'UNPAID' => 'Chưa thanh toán',
        'PAID' => 'Đã thanh toán',
        'FAILED' => 'Thanh toán lỗi',
        'REFUNDED' => 'Đã hoàn tiền',
    ];
    $currentStatus = $statusLabels[$order->status] ?? [$order->status, 'dark', 'fa-question-circle'];
    $nextStatusOptions = $statusOptions ?? [];
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $progressSteps = [
        'AWAITING_PAYMENT' => ['Chờ thanh toán', 'fa-credit-card', 'payment'],
        'PENDING' => ['Chờ xác nhận', 'fa-clock', 'pending'],
        'CONFIRMED' => ['Đã xác nhận', 'fa-clipboard-check', 'confirmed'],
        'DELIVERING' => ['Đang giao', 'fa-truck', 'shipping'],
        'DELIVERED' => ['Giao thành công', 'fa-check-circle', 'success'],
    ];
    $progressOrder = array_keys($progressSteps);
    $progressCurrent = in_array($order->status, ['RETURN_PENDING', 'RETURNED', 'EXCHANGED'], true) ? 'DELIVERED' : $order->status;
    $progressIndex = array_search($progressCurrent, $progressOrder, true);
@endphp

@push('styles')
<style>
.aod-page{background:#f4f7fb;color:#111827;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.aod-inner{max-width:1500px;margin:0 auto}.aod-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.aod-title small{color:#2563eb;font-size:13px;font-weight:900;text-transform:uppercase}.aod-title h4{font-size:28px;font-weight:900;line-height:1.18;margin:6px 0}.aod-title p{color:#667085;font-size:14px;margin:0}.aod-actions{display:flex;flex-wrap:wrap;gap:9px;justify-content:flex-end}.aod-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.aod-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}.aod-btn:hover{filter:brightness(.98);color:#111827}.aod-btn.primary:hover{color:#fff}.aod-btn:disabled{background:#f3f4f6;color:#98a2b3;cursor:not-allowed}
.aod-grid{align-items:start;display:grid;gap:16px;grid-template-columns:minmax(0,1.45fr) minmax(340px,.85fr)}.aod-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.aod-card+.aod-card{margin-top:16px}.aod-card-head{align-items:center;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:15px 18px}.aod-card-head h6{color:#111827;font-size:16px;font-weight:900;margin:0}.aod-card-body{padding:18px}.aod-badge{align-items:center;border:1px solid transparent;border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;gap:6px;min-height:30px;padding:0 11px;white-space:nowrap}.aod-badge.warning,.aod-badge.pending{background:#fffbeb;border-color:#fde68a;color:#92400e}.aod-badge.payment{background:#fff7ed;border-color:#fed7aa;color:#c2410c}.aod-badge.info,.aod-badge.confirmed{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}.aod-badge.moving,.aod-badge.shipping{background:#f5f3ff;border-color:#ddd6fe;color:#6d28d9}.aod-badge.success{background:#ecfdf5;border-color:#a7f3d0;color:#047857}.aod-badge.danger{background:#fef2f2;border-color:#fecaca;color:#b91c1c}.aod-badge.return{background:#eef2ff;border-color:#c7d2fe;color:#3730a3}.aod-badge.dark{background:#f3f4f6;border-color:#d1d5db;color:#374151}
.aod-meta{display:grid;gap:10px;grid-template-columns:repeat(3,minmax(0,1fr));margin-bottom:16px}.aod-meta-item{background:#fff;border:1px solid #eef0f3;border-radius:8px;padding:12px}.aod-meta-item span{color:#667085;display:block;font-size:12px;font-weight:900;margin-bottom:5px}.aod-meta-item strong{color:#111827;display:block;font-size:14px;line-height:1.35}.aod-progress{display:grid;gap:8px;grid-template-columns:repeat(5,minmax(0,1fr));margin:4px 0 20px;position:relative}.aod-progress:before{background:#e5e7eb;content:"";height:2px;left:7%;position:absolute;right:7%;top:18px}.aod-step{color:#98a2b3;font-size:12px;font-weight:900;position:relative;text-align:center;z-index:1}.aod-step-icon{align-items:center;background:#fff;border:2px solid #d0d5dd;border-radius:50%;color:#98a2b3;display:flex;height:36px;justify-content:center;margin:0 auto 7px;width:36px}.aod-step.active{color:var(--step-color)}.aod-step.active .aod-step-icon{background:var(--step-color);border-color:var(--step-color);color:#fff}.aod-step.payment{--step-color:#c2410c}.aod-step.pending{--step-color:#92400e}.aod-step.confirmed{--step-color:#1d4ed8}.aod-step.shipping{--step-color:#6d28d9}.aod-step.success{--step-color:#047857}
.aod-product{align-items:center;border-bottom:1px solid #eef2f6;display:grid;gap:13px;grid-template-columns:78px minmax(0,1fr) auto;padding:14px 0}.aod-product:last-child{border-bottom:0}.aod-product img{aspect-ratio:1;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;object-fit:cover;width:78px}.aod-product-name{font-size:14px;font-weight:900;line-height:1.35;margin:0 0 7px}.aod-product-meta{color:#667085;font-size:13px;font-weight:700}.aod-product-price{min-width:130px;text-align:right}.aod-product-price strong{color:#111827;display:block;font-size:16px}.aod-product-price small{color:#667085;font-size:12px;font-weight:700}.aod-form label{color:#667085;display:block;font-size:12px;font-weight:900;margin:0 0 6px}.aod-select,.aod-textarea{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;width:100%}.aod-select{min-height:40px;padding:0 11px}.aod-textarea{min-height:84px;padding:9px 11px;resize:vertical}.aod-help{color:#667085;font-size:12px;font-weight:700;line-height:1.45;margin:8px 0 0}.aod-error{color:#b91c1c;font-size:12px;font-weight:800;margin:8px 0 0}.aod-form .aod-btn{margin-top:11px;width:100%}.aod-row{border-bottom:1px solid #eef2f6;display:flex;font-size:14px;gap:16px;justify-content:space-between;padding:11px 0}.aod-row span:first-child{color:#667085}.aod-row strong{color:#111827;line-height:1.45;text-align:right}.aod-total{border-bottom:0;padding-top:14px}.aod-total strong{font-size:22px}.aod-note{background:#f8fafc;border:1px solid #e4e7ec;border-radius:8px;color:#344054;font-size:13px;font-weight:700;line-height:1.5;margin-top:12px;padding:12px;white-space:pre-line}
@media(max-width:1100px){.aod-page{margin:-24px -12px 0;padding:16px 12px}.aod-head{flex-direction:column}.aod-actions,.aod-btn{width:100%}.aod-grid{grid-template-columns:1fr}}@media(max-width:680px){.aod-meta{grid-template-columns:1fr}.aod-progress{gap:4px}.aod-step{font-size:11px}.aod-product{align-items:start;grid-template-columns:64px minmax(0,1fr)}.aod-product img{width:64px}.aod-product-price{grid-column:2;min-width:0;text-align:left}}
</style>
@endpush

@section('content')
<div class="aod-page">
    <div class="aod-inner">
        <div class="aod-head">
            <div class="aod-title">
                <small>Chi tiết đơn hàng</small>
                <h4>{{ $order->order_code ?: 'Đơn hàng #' . $order->id }}</h4>
                <p>Ngày đặt: {{ $order->created_at?->format('d/m/Y H:i') }}</p>
            </div>
            <div class="aod-actions">
                <span class="aod-badge {{ $currentStatus[1] }}"><i class="fas {{ $currentStatus[2] }}"></i>{{ $currentStatus[0] }}</span>
                <a href="{{ route('admin.orders.index') }}" class="aod-btn"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </div>

        <div class="aod-grid">
            <section>
                <div class="aod-card">
                    <div class="aod-card-head">
                        <h6>Tổng quan đơn hàng</h6>
                    </div>
                    <div class="aod-card-body">
                        <div class="aod-meta">
                            <div class="aod-meta-item">
                                <span>Khách hàng</span>
                                <strong>{{ $order->user->full_name ?? $order->recipient_name }}</strong>
                            </div>
                            <div class="aod-meta-item">
                                <span>Số điện thoại</span>
                                <strong>{{ $order->recipient_phone }}</strong>
                            </div>
                            <div class="aod-meta-item">
                                <span>Thanh toán</span>
                                <strong>{{ $paymentMap[$order->payment_method] ?? $order->payment_method }}</strong>
                            </div>
                        </div>

                        <div class="aod-progress">
                            @foreach ($progressSteps as $status => [$label, $icon, $class])
                                @php
                                    $stepIndex = array_search($status, $progressOrder, true);
                                    $isActiveStep = $progressIndex !== false && $stepIndex !== false && $stepIndex <= $progressIndex;
                                @endphp
                                <div class="aod-step {{ $class }} {{ $isActiveStep ? 'active' : '' }}">
                                    <div class="aod-step-icon"><i class="fas {{ $icon }}"></i></div>
                                    <span>{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>

                        @foreach ($order->items as $item)
                            @php
                                $image = $item->product?->image_url ?? asset('upload/no-image.jpg');
                                $variant = trim(implode(' ', array_filter([$item->color_name, $item->lens_size_name])));
                            @endphp
                            <div class="aod-product">
                                <img src="{{ $image }}" alt="{{ $item->product_name }}">
                                <div>
                                    <p class="aod-product-name">{{ $item->product_name }}</p>
                                    <div class="aod-product-meta">
                                        Số lượng: <strong>x{{ $item->quantity }}</strong>
                                        @if ($variant !== '')
                                            · {{ $variant }}
                                        @endif
                                        @if ($item->sku)
                                            · SKU: {{ $item->sku }}
                                        @endif
                                    </div>
                                </div>
                                <div class="aod-product-price">
                                    <strong>{{ number_format($item->total_price, 0, ',', '.') }}đ</strong>
                                    <small>{{ number_format($item->unit_price, 0, ',', '.') }}đ / sản phẩm</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <aside>
                <div class="aod-card">
                    <div class="aod-card-head">
                        <h6>Cập nhật trạng thái</h6>
                    </div>
                    <div class="aod-card-body">
                        @if (session('success'))
                            <div class="alert alert-success p-2">{{ session('success') }}</div>
                        @endif
                        @if ($viewErrors->has('status'))
                            <div class="alert alert-danger p-2">{{ $viewErrors->first('status') }}</div>
                        @endif
                        <form method="post" action="{{ route('admin.orders.status', $order) }}" class="aod-form">
                            @csrf
                            @method('PUT')
                            <label for="status-select">Trạng thái tiếp theo</label>
                            <select name="status" class="aod-select" id="status-select">
                                <option value="">{{ empty($nextStatusOptions) ? 'Không còn trạng thái tiếp theo' : 'Chọn trạng thái tiếp theo' }}</option>
                                @foreach ($nextStatusOptions as $value => $meta)
                                    <option value="{{ $value }}" @selected(old('status') === $value)>{{ $meta[0] }}</option>
                                @endforeach
                            </select>
                            @if ($viewErrors->has('status'))
                                <div class="aod-error">{{ $viewErrors->first('status') }}</div>
                            @endif
                            @if (array_key_exists('CANCELLED', $nextStatusOptions))
                                <label for="cancel-reason" style="margin-top:12px;">Lý do hủy đơn</label>
                                <textarea name="cancel_reason" class="aod-textarea" id="cancel-reason" placeholder="Nhập nếu chọn hủy đơn">{{ old('cancel_reason') }}</textarea>
                                @if ($viewErrors->has('cancel_reason'))
                                    <div class="aod-error">{{ $viewErrors->first('cancel_reason') }}</div>
                                @endif
                            @endif
                            <p class="aod-help">Khi chuyển sang “Đang giao”, hệ thống tự tạo phiếu xuất bán trong kho.</p>
                            <button type="submit" class="aod-btn primary" @disabled(empty($nextStatusOptions))>
                                <i class="fas fa-save"></i> Cập nhật trạng thái
                            </button>
                        </form>
                    </div>
                </div>

                <div class="aod-card">
                    <div class="aod-card-head">
                        <h6>Thông tin giao hàng</h6>
                    </div>
                    <div class="aod-card-body">
                        <div class="aod-row"><span>Người nhận</span><strong>{{ $order->recipient_name }}</strong></div>
                        <div class="aod-row"><span>Số điện thoại</span><strong>{{ $order->recipient_phone }}</strong></div>
                        <div class="aod-row"><span>Địa chỉ</span><strong>{{ $order->shipping_address }}</strong></div>
                        @if (trim((string) $order->note) !== '')
                            <div class="aod-note">{{ $order->note }}</div>
                        @endif
                    </div>
                </div>

                <div class="aod-card">
                    <div class="aod-card-head">
                        <h6>Thanh toán</h6>
                    </div>
                    <div class="aod-card-body">
                        <div class="aod-row"><span>Trạng thái</span><strong>{{ $paymentStatusMap[$order->payment_status] ?? ($order->payment_status ?: '-') }}</strong></div>
                        <div class="aod-row"><span>Tổng tiền hàng</span><strong>{{ number_format($order->subtotal_amount, 0, ',', '.') }}đ</strong></div>
                        <div class="aod-row"><span>Phí vận chuyển</span><strong>{{ (float) $order->shipping_fee > 0 ? number_format($order->shipping_fee, 0, ',', '.') . 'đ' : 'Miễn phí' }}</strong></div>
                        @if ((float) $order->discount_amount > 0)
                            <div class="aod-row"><span>Giảm giá</span><strong>-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</strong></div>
                        @endif
                        <div class="aod-row aod-total"><span>Thành tiền</span><strong>{{ number_format($order->total_amount, 0, ',', '.') }}đ</strong></div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
