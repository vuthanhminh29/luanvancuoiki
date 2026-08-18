@extends('admin.layouts.app')

@section('title', 'Chi tiết phiếu kho')

@php
    $num = fn ($value) => number_format((float) $value, 0, ',', '.');
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $typeMap = [
        'IMPORT' => [$transaction->status === 'PENDING' ? 'Đề xuất nhập kho' : 'Nhập kho', 'success', 'fa-arrow-down'],
        'EXPORT' => [$transaction->status === 'PENDING' ? 'Đề xuất xuất kho' : 'Xuất kho', 'danger', 'fa-arrow-up'],
        'RETURN_IN' => ['Nhập hàng hoàn', 'success', 'fa-undo'],
        'EXCHANGE_OUT' => ['Xuất đổi hàng', 'danger', 'fa-exchange-alt'],
        'SALE_OUT' => ['Xuất bán', 'dark', 'fa-shopping-cart'],
    ];
    $statusMap = [
        'DRAFT' => ['Nháp', 'muted'],
        'PENDING' => ['Chờ admin duyệt', 'warning'],
        'COMPLETED' => ['Đã duyệt / hoàn tất', 'success'],
        'CANCELLED' => ['Đã từ chối / hủy', 'danger'],
    ];
    [$typeText, $typeClass, $typeIcon] = $typeMap[$transaction->type] ?? [$transaction->type ?: '-', 'muted', 'fa-circle'];
    [$statusText, $statusClass] = $statusMap[$transaction->status] ?? [$transaction->status ?: '-', 'muted'];
    $totalOrdered = $transaction->items->sum('ordered_quantity');
    $totalActual = $transaction->items->sum('actual_quantity');
@endphp

@push('styles')
<style>
.ts-page{background:#f5f7fb;color:#172033;min-height:100vh;padding:20px 24px 70px}.ts-inner{max-width:1420px;margin:0 auto}.ts-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:14px}.ts-title h4{color:#111827;font-size:24px;font-weight:900;margin:0 0 6px}.ts-title p{color:#667085;font-size:14px;margin:0}.ts-actions{display:flex;flex-wrap:wrap;gap:9px;justify-content:flex-end}.ts-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;cursor:pointer;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.ts-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}.ts-btn.success{background:#16a34a;border-color:#16a34a;color:#fff}.ts-btn.danger{background:#dc2626;border-color:#dc2626;color:#fff}.ts-btn.dark{background:#111827;border-color:#111827;color:#fff}.ts-btn:hover{filter:brightness(.98);color:inherit}.ts-btn.primary:hover,.ts-btn.success:hover,.ts-btn.danger:hover,.ts-btn.dark:hover{color:#fff}.ts-grid{align-items:start;display:grid;gap:14px;grid-template-columns:minmax(0,1fr) 360px}.ts-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.ts-card+.ts-card{margin-top:14px}.ts-card-head{align-items:center;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 15px}.ts-card-head h6{color:#111827;font-size:16px;font-weight:900;margin:0}.ts-card-body{padding:15px}.ts-meta{display:grid;gap:10px;grid-template-columns:repeat(3,minmax(0,1fr))}.ts-meta-item{background:#fbfcfd;border:1px solid #eef2f6;border-radius:7px;padding:11px}.ts-meta-item span,.ts-field label{color:#667085;display:block;font-size:11px;font-weight:900;margin-bottom:5px;text-transform:uppercase}.ts-meta-item strong{color:#111827;display:block;font-size:14px;line-height:1.45}.ts-badge{align-items:center;border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;gap:6px;min-height:26px;padding:0 9px;white-space:nowrap}.ts-badge.success{background:#dcfce7;color:#166534}.ts-badge.warning{background:#fef3c7;color:#92400e}.ts-badge.danger{background:#fee2e2;color:#991b1b}.ts-badge.dark{background:#e5e7eb;color:#111827}.ts-badge.muted{background:#f3f4f6;color:#4b5563}.ts-flow{color:#475467;line-height:1.5}.ts-flow strong{color:#111827}.ts-note{background:#f8fafc;border:1px solid #e4e7ec;border-radius:7px;color:#344054;font-size:13px;font-weight:700;line-height:1.55;margin-top:12px;padding:11px;white-space:pre-line}.ts-table-wrap{overflow-x:auto}.ts-table{border-collapse:collapse;min-width:920px;width:100%}.ts-table th{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:11px;font-weight:900;padding:10px 11px;text-align:left;text-transform:uppercase;white-space:nowrap}.ts-table td{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;padding:10px 11px;vertical-align:middle}.ts-product{align-items:center;display:flex;gap:10px;min-width:260px}.ts-thumb{background:#f8fafc;border:1px solid #e4e7ec;border-radius:7px;flex:0 0 48px;height:48px;object-fit:cover;width:48px}.ts-name{color:#111827;font-weight:900;line-height:1.35}.ts-sub{color:#667085;font-size:12px;line-height:1.4;margin-top:3px}.ts-color{border:1px solid rgba(17,24,39,.2);border-radius:50%;display:inline-block;height:14px;margin-right:6px;vertical-align:-2px;width:14px}.ts-number{color:#111827;font-weight:900;white-space:nowrap}.ts-form{display:grid;gap:10px}.ts-input{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:40px;padding:9px 10px;width:100%}.ts-help{color:#667085;font-size:12px;line-height:1.45}.ts-error{background:#fee2e2;border:1px solid #fecaca;border-radius:7px;color:#991b1b;font-size:13px;font-weight:800;margin-bottom:12px;padding:10px}.ts-summary-row{border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:10px 0}.ts-summary-row:last-child{border-bottom:0}.ts-summary-row span{color:#667085}.ts-summary-row strong{color:#111827;text-align:right}@media(max-width:1050px){.ts-grid{grid-template-columns:1fr}.ts-meta{grid-template-columns:1fr 1fr}}@media(max-width:760px){.ts-page{padding:14px}.ts-head,.ts-card-head{align-items:flex-start;flex-direction:column}.ts-actions,.ts-btn{width:100%}.ts-meta{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="ts-page">
    <div class="ts-inner">
        <div class="ts-head">
            <div class="ts-title">
                <h4>{{ $transaction->transaction_code }}</h4>
                <p>{{ $transaction->status === 'PENDING' ? 'Chi tiết đề xuất nhập/xuất kho chờ admin duyệt.' : 'Chi tiết phiếu nhập/xuất kho đã được ghi nhận.' }} Màn hình này chỉ hiển thị dữ liệu, không sửa trực tiếp nội dung phiếu.</p>
            </div>
            <div class="ts-actions">
                <a href="{{ route('admin.warehouses.index', ['warehouse_tab' => 'transactions']) }}" class="ts-btn"><i class="fa fa-arrow-left"></i> Danh sách phiếu</a>
                <a href="{{ route('admin.warehouses.create-transaction') }}" class="ts-btn primary"><i class="fa fa-plus"></i> {{ ($isAdminUser ?? false) ? 'Tạo phiếu kho' : 'Tạo đề xuất' }}</a>
            </div>
        </div>

        <div class="ts-grid">
            <main>
                <div class="ts-card">
                    <div class="ts-card-head">
                        <h6>Thông tin phiếu</h6>
                        <span class="ts-badge {{ $statusClass }}">{{ $statusText }}</span>
                    </div>
                    <div class="ts-card-body">
                        <div class="ts-meta">
                            <div class="ts-meta-item"><span>Loại phiếu</span><strong><span class="ts-badge {{ $typeClass }}"><i class="fa {{ $typeIcon }}"></i>{{ $typeText }}</span></strong></div>
                            <div class="ts-meta-item"><span>Ngày tạo</span><strong>{{ $transaction->created_at?->format('d/m/Y H:i') ?: '-' }}</strong></div>
                            <div class="ts-meta-item"><span>Ngày dự kiến</span><strong>{{ $transaction->expected_date?->format('d/m/Y') ?: '-' }}</strong></div>
                            <div class="ts-meta-item"><span>Người đề xuất</span><strong>{{ $transaction->creator?->full_name ?: $transaction->creator?->email ?: '-' }}</strong></div>
                            <div class="ts-meta-item"><span>Người xử lý</span><strong>{{ $transaction->confirmer?->full_name ?: $transaction->confirmer?->email ?: '-' }}</strong></div>
                            <div class="ts-meta-item"><span>Thời điểm xử lý</span><strong>{{ $transaction->confirmed_at?->format('d/m/Y H:i') ?: '-' }}</strong></div>
                        </div>
                        <div class="ts-note">
                            <div class="ts-flow">
                                <strong>{{ $transaction->sourceWarehouse?->name ?: 'Nguồn ngoài' }}</strong>
                                <span> -> </span>
                                <strong>{{ $transaction->targetWarehouse?->name ?: 'Điểm xuất' }}</strong>
                            </div>
                            @if (trim((string) $transaction->note) !== '')
                                {{ $transaction->note }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="ts-card">
                    <div class="ts-card-head">
                        <h6>Chi tiết {{ $transaction->type === 'EXPORT' ? 'xuất kho' : 'nhập kho' }}</h6>
                        <span class="ts-badge muted">{{ $num($transaction->items->count()) }} dòng</span>
                    </div>
                    <div class="ts-table-wrap">
                        <table class="ts-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Biến thể</th>
                                    <th>Tồn hiện tại</th>
                                    <th>Đề xuất</th>
                                    <th>Đã ghi nhận</th>
                                    <th>Đơn giá nhập</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transaction->items as $item)
                                    @php
                                        $variant = $item->variant;
                                        $product = $variant?->product;
                                        $available = (int) ($availableStockByVariantId->get($item->variant_id) ?? 0);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="ts-product">
                                                <img class="ts-thumb" src="{{ $product?->image_url ?? asset('upload/no-image.jpg') }}" alt="{{ $product?->name ?? 'Sản phẩm' }}">
                                                <div>
                                                    <div class="ts-name">{{ $product?->name ?? '-' }}</div>
                                                    <div class="ts-sub">{{ $product?->product_code ?? '' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                @if ($variant?->color)
                                                    <span class="ts-color" style="background: {{ $variant->color->hex_code }}"></span>
                                                @endif
                                                {{ $variant?->color?->name ?? '-' }} / Size {{ $variant?->lensSize?->name ?? '-' }}
                                            </div>
                                            <div class="ts-sub">{{ $variant?->sku ?? '-' }}</div>
                                        </td>
                                        <td class="ts-number">{{ $num($available) }}</td>
                                        <td class="ts-number">{{ $num($item->ordered_quantity) }}</td>
                                        <td class="ts-number">{{ $num($item->actual_quantity) }}</td>
                                        <td class="ts-number">{{ $item->unit_cost !== null ? $money($item->unit_cost) : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="ts-sub" style="padding:24px;text-align:center">Phiếu chưa có sản phẩm.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside>
                @if ($errors->any())
                    <div class="ts-error">{{ $errors->first() }}</div>
                @endif

                <div class="ts-card">
                    <div class="ts-card-head"><h6>Tổng quan</h6></div>
                    <div class="ts-card-body">
                        <div class="ts-summary-row"><span>Số dòng</span><strong>{{ $num($transaction->items->count()) }}</strong></div>
                        <div class="ts-summary-row"><span>Số lượng đề xuất</span><strong>{{ $num($totalOrdered) }}</strong></div>
                        <div class="ts-summary-row"><span>Số lượng đã ghi nhận</span><strong>{{ $num($totalActual) }}</strong></div>
                        <div class="ts-summary-row"><span>Trạng thái</span><strong>{{ $statusText }}</strong></div>
                    </div>
                </div>

                @if ($isAdminUser && $transaction->status === 'PENDING')
                    <div class="ts-card">
                        <div class="ts-card-head"><h6>Duyệt đề xuất</h6></div>
                        <div class="ts-card-body">
                            <form method="post" action="{{ route('admin.warehouses.approve-transaction', $transaction) }}" class="ts-form" onsubmit="return confirm('Duyệt phiếu này và cập nhật tồn kho?')">
                                @csrf
                                @method('PATCH')
                                <p class="ts-help">Duyệt phiếu sẽ cộng/trừ tồn kho theo số lượng đề xuất ở từng dòng.</p>
                                <button type="submit" class="ts-btn success"><i class="fa fa-check"></i> Duyệt và cập nhật kho</button>
                            </form>
                            <hr>
                            <form method="post" action="{{ route('admin.warehouses.reject-transaction', $transaction) }}" class="ts-form" onsubmit="return confirm('Từ chối đề xuất kho này?')">
                                @csrf
                                @method('PATCH')
                                <div class="ts-field">
                                    <label>Lý do từ chối</label>
                                    <textarea class="ts-input" name="reject_reason" rows="3" placeholder="Có thể bỏ trống"></textarea>
                                </div>
                                <button type="submit" class="ts-btn danger"><i class="fa fa-times"></i> Từ chối</button>
                            </form>
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</div>
@endsection
