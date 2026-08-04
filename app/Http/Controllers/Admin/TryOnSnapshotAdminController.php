<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TryOnSnapshot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TryOnSnapshotAdminController extends Controller
{
    // Hiển thị danh sách kết quả thử kính trong admin.
    // Route: GET /admin/thu-kinh.
    public function index(Request $request): View
    {
        // Từ khóa dùng để lọc theo tên khách, email, tên kính hoặc mã model.
        $keyword = trim((string) $request->query('keyword'));

        // Query lấy lịch sử thử kính mới nhất trước, kèm user/product để sau này cần mở rộng vẫn có dữ liệu.
        $snapshotsQuery = TryOnSnapshot::query()
            ->with(['product', 'user'])
            ->latest('id');

        // Nếu admin nhập ô tìm kiếm thì gom điều kiện vào một nhóm where.
        if ($keyword !== '') {
            $like = "%{$keyword}%";

            $snapshotsQuery->where(function ($query) use ($like) {
                $query->where('user_name', 'like', $like)
                    ->orWhere('user_email', 'like', $like)
                    ->orWhere('product_name', 'like', $like)
                    ->orWhere('model_sku', 'like', $like);
            });
        }

        // Phân trang để danh sách ảnh không bị quá dài khi có nhiều lượt thử kính.
        // Phân trang 10 hình mỗi trang để admin xem ảnh thử kính gọn hơn.
        $snapshots = $snapshotsQuery->paginate(10)->withQueryString();

        // Các số liệu nhỏ ở đầu trang giúp thầy/admin nhìn nhanh tổng quan.
        $summary = [
            'total' => TryOnSnapshot::count(),
            'users' => TryOnSnapshot::distinct('user_email')->count('user_email'),
            'models' => TryOnSnapshot::distinct('model_sku')->count('model_sku'),
            'today' => TryOnSnapshot::whereDate('created_at', now()->toDateString())->count(),
        ];

        return view('admin.try-on-snapshots.index', compact('snapshots', 'summary', 'keyword'));
    }
}
