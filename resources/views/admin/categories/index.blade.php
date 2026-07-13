@extends('admin.layouts.app')

@section('title', 'Danh mục')

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
.category-img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb; }
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
    <div class="categories-card">
        <div class="categories-header">
            <h6>Danh mục</h6>
            <a href="{{ route('admin.categories.create') }}" class="btn-add-category">
                <i class="fa fa-plus"></i>
                <span>Thêm danh mục</span>
            </a>
        </div>

        <div class="categories-table-section">
            <table class="categories-table" id="categories-list">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên</th>
                        <th>Ảnh</th>
                        <th>Sản phẩm</th>
                        <th>Trạng thái</th>
                        <th>Chỉnh sửa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        @php
                            $image = $category->image_url ?? $category->image ?? null;
                            $statusClass = $category->status === 'ACTIVE' || $category->status == 1 ? 'category-status-active' : 'category-status-inactive';
                            $statusText = $category->status === 'ACTIVE' || $category->status == 1 ? 'Hiển thị' : 'Tạm ẩn';
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                            <td class="category-name-cell">{{ $category->name }}</td>
                            <td>
                                <img src="{{ $image ? asset('upload/' . ltrim($image, '/')) : asset('upload/no-image.jpg') }}" alt="{{ $category->name }}" class="category-img">
                            </td>
                            <td>{{ $category->products_count }}</td>
                            <td><span class="category-status-badge {{ $statusClass }}">{{ $statusText }}</span></td>
                            <td>
                                <div class="category-actions-dropdown">
                                    <button class="category-dropdown-btn" type="button"><i class="bi bi-three-dots-vertical"></i></button>
                                    <div class="category-dropdown-menu">
                                        <a class="category-dropdown-item" href="{{ route('admin.categories.edit', $category) }}">Sửa</a>
                                        <form method="post" action="{{ route('admin.categories.hidden', $category) }}" onsubmit="return confirm('Ẩn danh mục này?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="category-dropdown-item text-danger" type="submit">Ẩn</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $categories->links() }}
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
