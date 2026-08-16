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
    /**
     * Hiển thị danh sách ảnh thử kính.
     */
    public function index(Request $request): View
    {
        // Từ khóa dùng để lọc theo tên khách, email, tên kính hoặc mã model.
        // Luong: Gan ket qua xu ly vao bien $keyword.
        $keyword = trim((string) $request->query('keyword'));

        // Query lấy lịch sử thử kính mới nhất trước, kèm user/product để sau này cần mở rộng vẫn có dữ liệu.
        // Luong: Gan ket qua xu ly vao bien $snapshotsQuery.
        $snapshotsQuery = TryOnSnapshot::query()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with(['product', 'user'])
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest('id');

        // Nếu admin nhập ô tìm kiếm thì gom điều kiện vào một nhóm where.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($keyword !== '') {
            // Luong: Gan ket qua xu ly vao bien $like.
            $like = "%{$keyword}%";

            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $snapshotsQuery->where(function ($query) use ($like) {
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->where('user_name', 'like', $like)
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('user_email', 'like', $like)
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('product_name', 'like', $like)
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('model_sku', 'like', $like);
            });
        }

        // Phân trang để danh sách ảnh không bị quá dài khi có nhiều lượt thử kính.
        // Phân trang 10 hình mỗi trang để admin xem ảnh thử kính gọn hơn.
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $snapshots = $snapshotsQuery->paginate(10)->withQueryString();

        // Các số liệu nhỏ ở đầu trang giúp thầy/admin nhìn nhanh tổng quan.
        // Luong: Gan ket qua xu ly vao bien $summary.
        $summary = [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'total' => TryOnSnapshot::count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'users' => TryOnSnapshot::distinct('user_email')->count('user_email'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'models' => TryOnSnapshot::distinct('model_sku')->count('model_sku'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'today' => TryOnSnapshot::whereDate('created_at', now()->toDateString())->count(),
        ];

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.try-on-snapshots.index', compact('snapshots', 'summary', 'keyword'));
    }
}
