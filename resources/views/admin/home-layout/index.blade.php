@extends('admin.layouts.app')

@section('title', 'Bố cục trang')

@php
    $num = fn ($value) => number_format((float) $value, 0, ',', '.');
    $activeCount = $sections->where('status', 1)->count();
@endphp

@push('styles')
<style>
.hl-page{background:#f5f7fb;color:#172033;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.hl-inner{max-width:1200px;margin:0 auto}.hl-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.hl-title small{color:#0f8a7a;font-size:13px;font-weight:900;text-transform:uppercase}.hl-title h4{color:#111827;font-size:27px;font-weight:900;line-height:1.2;margin:6px 0}.hl-title p{color:#667085;font-size:14px;margin:0}.hl-actions{display:flex;gap:9px;justify-content:flex-end}.hl-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;cursor:pointer;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.hl-btn.primary{background:#0f8a7a;border-color:#0f8a7a;color:#fff}.hl-btn:hover{filter:brightness(.98);color:inherit}.hl-btn.primary:hover{color:#fff}
.hl-summary{display:grid;gap:12px;grid-template-columns:repeat(3,minmax(0,1fr));margin-bottom:14px}.hl-stat{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);min-height:96px;padding:15px}.hl-stat i{align-items:center;background:#eefcf8;border-radius:8px;color:#0f8a7a;display:inline-flex;height:34px;justify-content:center;width:34px}.hl-stat span{color:#667085;display:block;font-size:12px;font-weight:900;margin-top:11px}.hl-stat strong{color:#111827;display:block;font-size:24px;font-weight:900;line-height:1;margin-top:5px}
.hl-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.hl-note{background:#eff6ff;border-bottom:1px solid #dbeafe;color:#1d4ed8;font-size:13px;font-weight:800;line-height:1.5;padding:12px 14px}.hl-list{display:grid;padding:12px}.hl-section{align-items:center;background:#fff;border:1px solid #e4e7ec;border-radius:8px;display:grid;gap:12px;grid-template-columns:44px minmax(0,1fr) 128px 112px 92px;margin-bottom:10px;min-height:78px;padding:12px}.hl-section:hover{background:#fbfcfd}.hl-icon{align-items:center;background:#f0fdfa;border-radius:8px;color:#0f766e;display:inline-flex;height:44px;justify-content:center;width:44px}.hl-name{color:#111827;font-size:14px;font-weight:900;line-height:1.35}.hl-sub{color:#667085;font-size:12px;line-height:1.45;margin-top:3px}.hl-order label,.hl-switch-label{color:#667085;display:block;font-size:11px;font-weight:900;margin-bottom:5px;text-transform:uppercase}.hl-order input{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:900;height:36px;padding:0 8px;text-align:center;width:100%}.hl-switch{align-items:center;display:flex;gap:8px}.hl-switch input{height:18px;width:18px}.hl-switch span{color:#111827;font-size:13px;font-weight:900}.hl-move{display:flex;gap:6px;justify-content:flex-end}.hl-icon-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#344054;cursor:pointer;display:inline-flex;height:34px;justify-content:center;width:36px}.hl-icon-btn:hover{background:#f8fafc}.hl-empty{color:#667085;padding:30px 12px;text-align:center}.hl-footer{align-items:center;background:#fbfcfd;border-top:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 14px}.hl-footer span{color:#667085;font-size:12px;font-weight:800}
@media(max-width:900px){.hl-summary{grid-template-columns:1fr}.hl-section{grid-template-columns:44px minmax(0,1fr) 92px}.hl-order,.hl-toggle,.hl-move{grid-column:2/-1}.hl-move{justify-content:flex-start}.hl-head,.hl-footer{align-items:flex-start;flex-direction:column}.hl-actions,.hl-btn{width:100%}}@media(max-width:760px){.hl-page{margin:-24px -12px 0;padding:16px 12px}.hl-section{grid-template-columns:1fr}.hl-icon{grid-row:auto}.hl-order,.hl-toggle,.hl-move{grid-column:1}}
</style>
@endpush

@section('content')
<div class="hl-page">
    <div class="hl-inner">
        <div class="hl-head">
            <div class="hl-title">
                <small>Mắt kính admin</small>
                <h4>Quản lý bố cục trang chủ</h4>
                <p>Bật/tắt từng khối và sắp xếp thứ tự hiển thị trên trang chủ.</p>
            </div>
            <div class="hl-actions">
                <a class="hl-btn" href="{{ route('home') }}" target="_blank"><i class="fa fa-external-link-alt"></i> Xem website</a>
                <button class="hl-btn primary" form="home-layout-form" type="submit"><i class="fa fa-save"></i> Lưu bố cục</button>
            </div>
        </div>

        <div class="hl-summary">
            <div class="hl-stat"><i class="fa fa-layer-group"></i><span>Tổng khối</span><strong>{{ $num($sections->count()) }}</strong></div>
            <div class="hl-stat"><i class="fa fa-eye"></i><span>Đang hiển thị</span><strong>{{ $num($activeCount) }}</strong></div>
            <div class="hl-stat"><i class="fa fa-eye-slash"></i><span>Đang tắt</span><strong>{{ $num($sections->count() - $activeCount) }}</strong></div>
        </div>

        <form id="home-layout-form" class="hl-card" method="post" action="{{ route('admin.home-layout.update') }}">
            @csrf
            <div class="hl-note">
                <i class="fa fa-info-circle"></i>
                Chỉnh số thứ tự hoặc dùng nút lên/xuống rồi bấm lưu. Khối tắt sẽ không xuất hiện ngoài trang chủ.
            </div>

            <div id="home-layout-list" class="hl-list">
                @forelse ($sections as $index => $section)
                    @php
                        $itemMeta = $meta[$section->section_key] ?? ['icon' => 'fa-cube', 'note' => 'Khối nội dung trang chủ.'];
                    @endphp
                    <div class="hl-section" data-layout-row>
                        <div class="hl-icon"><i class="fa {{ $itemMeta['icon'] }}"></i></div>

                        <div>
                            <div class="hl-name">{{ $section->section_name }}</div>
                            <div class="hl-sub">Key: {{ $section->section_key }} · {{ $itemMeta['note'] }}</div>
                            <input type="hidden" name="sections[{{ $index }}][section_key]" value="{{ $section->section_key }}" data-section-key>
                        </div>

                        <div class="hl-order">
                            <label>Thứ tự</label>
                            <input name="sections[{{ $index }}][sort_order]" value="{{ $section->sort_order }}" type="number" min="1" max="99" data-sort-order>
                        </div>

                        <div class="hl-toggle">
                            <label class="hl-switch-label">Trạng thái</label>
                            <label class="hl-switch">
                                <input type="checkbox" name="sections[{{ $index }}][status]" value="1" @checked((int) $section->status === 1)>
                                <span>Hiển thị</span>
                            </label>
                        </div>

                        <div class="hl-move">
                            <button class="hl-icon-btn" type="button" data-move="up" title="Đưa lên"><i class="fa fa-chevron-up"></i></button>
                            <button class="hl-icon-btn" type="button" data-move="down" title="Đưa xuống"><i class="fa fa-chevron-down"></i></button>
                        </div>
                    </div>
                @empty
                    <div class="hl-empty">Chưa có dữ liệu bố cục trang chủ.</div>
                @endforelse
            </div>

            <div class="hl-footer">
                <span>Thứ tự sẽ được lưu theo các ô số đang hiển thị.</span>
                <button class="hl-btn primary" type="submit"><i class="fa fa-save"></i> Lưu bố cục</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('home-layout-list');
    if (!list) return;

    const refresh = () => {
        list.querySelectorAll('[data-layout-row]').forEach((row, index) => {
            const order = row.querySelector('[data-sort-order]');
            const key = row.querySelector('[data-section-key]');
            if (order) order.value = index + 1;
            if (key) {
                const base = `sections[${index}]`;
                key.name = `${base}[section_key]`;
                const checkbox = row.querySelector('input[type="checkbox"]');
                if (order) order.name = `${base}[sort_order]`;
                if (checkbox) checkbox.name = `${base}[status]`;
            }
        });
    };

    list.addEventListener('click', function (event) {
        const button = event.target.closest('[data-move]');
        if (!button) return;

        const row = button.closest('[data-layout-row]');
        if (!row) return;

        if (button.dataset.move === 'up' && row.previousElementSibling) {
            list.insertBefore(row, row.previousElementSibling);
            refresh();
        }

        if (button.dataset.move === 'down' && row.nextElementSibling) {
            list.insertBefore(row.nextElementSibling, row);
            refresh();
        }
    });
});
</script>
@endpush
