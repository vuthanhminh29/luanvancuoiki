@extends('admin.layouts.app')

@section('title', 'Tròng kính')

@push('styles')
<style>
.categories-wrapper { padding: 2rem 1.5rem; max-width: 1400px; margin: 0 auto; }
.categories-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); overflow: hidden; }
.categories-header { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem; border-bottom: 1px solid #e5e7eb; }
.categories-header h6 { margin: 0; font-size: 1.125rem; font-weight: 600; color: #111827; }
.btn-add-category { display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1.25rem; background: #3b82f6; color: #fff; text-decoration: none; border-radius: 6px; font-size: .875rem; font-weight: 500; transition: background-color .2s; }
.btn-add-category:hover { background: #2563eb; color: #fff; }
.categories-table-section { padding: 1.5rem; overflow-x: auto; }
.categories-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.categories-table thead { background: #f9fafb; border-bottom: 2px solid #e5e7eb; }
.categories-table th { padding: .875rem 1rem; text-align: left; font-weight: 600; color: #374151; font-size: .8125rem; text-transform: uppercase; letter-spacing: .025em; white-space: nowrap; }
.categories-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background-color .15s; }
.categories-table tbody tr:hover { background: #f9fafb; }
.categories-table td { padding: 1rem; color: #374151; vertical-align: middle; }
.category-name-cell { min-width: 200px; font-weight: 500; color: #111827; }
.lens-group-chip { display: inline-block; padding: .125rem .5rem; margin: 0 .25rem .25rem 0; border-radius: 9999px; background: #eef2ff; color: #4338ca; font-size: .6875rem; font-weight: 600; white-space: nowrap; }
.category-status-badge { display: inline-block; padding: .375rem .75rem; border-radius: 9999px; font-size: .75rem; font-weight: 500; white-space: nowrap; }
.category-status-active { background: #d1fae5; color: #065f46; }
.category-status-inactive { background: #fee2e2; color: #991b1b; }
.category-actions-dropdown { position: relative; display: inline-block; }
.category-dropdown-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: transparent; border: none; border-radius: 6px; cursor: pointer; color: #6b7280; transition: all .2s; }
.category-dropdown-btn:hover { background: #f3f4f6; color: #111827; }
.category-dropdown-menu { position: absolute; right: 0; top: 100%; margin-top: .25rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06); min-width: 120px; z-index: 50; opacity: 0; visibility: hidden; transform: translateY(-8px); transition: all .2s; }
.category-dropdown-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
.category-dropdown-item { display: block; width: 100%; padding: .625rem 1rem; color: #374151; text-decoration: none; font-size: .875rem; transition: background-color .15s; border: none; background: none; text-align: left; cursor: pointer; }
.category-dropdown-item:hover { background: #f9fafb; }
.category-dropdown-item.text-danger { color: #dc2626; }
.category-dropdown-item.text-danger:hover { background: #fef2f2; }
@media (max-width: 768px) { .categories-wrapper { padding: 1rem; } .categories-header { flex-direction: column; gap: 1rem; align-items: flex-start; } .btn-add-category { width: 100%; justify-content: center; } }
</style>
@endpush

@section('content')
<div class="categories-wrapper">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="categories-card">
        <div class="categories-header">
            <h6>Tròng kính</h6>
            <a href="{{ route('admin.lens-options.create') }}" class="btn-add-category">
                <i class="fa fa-plus"></i>
                <span>Thêm tròng kính</span>
            </a>
        </div>

        <div class="categories-table-section">
            <table class="categories-table" id="lens-options-list">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mã</th>
                        <th>Tên</th>
                        <th>Giá</th>
                        <th>Nhóm hiển thị</th>
                        <th>Trạng thái</th>
                        <th>Chỉnh sửa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lensOptions as $lensOption)
                        @php
                            $statusClass = $lensOption->status === 'ACTIVE' ? 'category-status-active' : 'category-status-inactive';
                            $statusText = $lensOption->status === 'ACTIVE' ? 'Hiển thị' : 'Tạm ẩn';
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($lensOptions->currentPage() - 1) * $lensOptions->perPage() }}</td>
                            <td><code>{{ $lensOption->code }}</code></td>
                            <td class="category-name-cell">{{ $lensOption->name }}</td>
                            <td>{{ number_format((float) $lensOption->price, 0, ',', '.') }}đ</td>
                            <td>
                                @foreach ($lensOption->groups ?? [] as $group)
                                    <span class="lens-group-chip">{{ $groupLabels[$group] ?? $group }}</span>
                                @endforeach
                            </td>
                            <td><span class="category-status-badge {{ $statusClass }}">{{ $statusText }}</span></td>
                            <td>
                                <div class="category-actions-dropdown">
                                    <button class="category-dropdown-btn" type="button"><i class="bi bi-three-dots-vertical"></i></button>
                                    <div class="category-dropdown-menu">
                                        <a class="category-dropdown-item" href="{{ route('admin.lens-options.edit', $lensOption) }}">Sửa</a>
                                        @if ($lensOption->status === 'ACTIVE')
                                            <form method="post" action="{{ route('admin.lens-options.hidden', $lensOption) }}" onsubmit="return confirm('Ẩn tròng kính này khỏi trang tư vấn?')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="category-dropdown-item text-danger" type="submit">Ẩn</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Chưa có tròng kính nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $lensOptions->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.category-dropdown-btn')) {
            e.preventDefault();
            e.stopPropagation();
            const button = e.target.closest('.category-dropdown-btn');
            const menu = button.nextElementSibling;
            document.querySelectorAll('.category-dropdown-menu').forEach(function(item) {
                if (item !== menu) item.classList.remove('show');
            });
            menu.classList.toggle('show');
        } else if (!e.target.closest('.category-actions-dropdown')) {
            document.querySelectorAll('.category-dropdown-menu').forEach(function(item) {
                item.classList.remove('show');
            });
        }
    });
});
</script>
@endpush
