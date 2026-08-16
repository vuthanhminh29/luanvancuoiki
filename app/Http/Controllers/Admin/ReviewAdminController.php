<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewAdminController extends Controller
{
    /**
     * Hiển thị danh sách đánh giá sản phẩm.
     */
    public function index(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $status.
        $status = trim((string) $request->query('status'));
        // Luong: Gan ket qua xu ly vao bien $rating.
        $rating = (int) $request->query('rating', 0);
        // Luong: Gan ket qua xu ly vao bien $keyword.
        $keyword = trim((string) $request->query('keyword'));

        // Luong: Gan ket qua xu ly vao bien $reviewsQuery.
        $reviewsQuery = ProductReview::query()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with(['product', 'user'])
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest('id');

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($status === 'VISIBLE') {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $reviewsQuery->whereIn('status', ['VISIBLE', 'PENDING']);
        // Luong: Chuyen sang dieu kien thay the sau khi nhanh truoc khong dat.
        } elseif ($status === 'HIDDEN') {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $reviewsQuery->where('status', $status);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($rating >= 1 && $rating <= 5) {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $reviewsQuery->where('rating', $rating);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($keyword !== '') {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $reviewsQuery->where(function ($query) use ($keyword) {
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->where('content', 'like', "%{$keyword}%")
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        ->where('full_name', 'like', "%{$keyword}%")
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('email', 'like', "%{$keyword}%"))
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhereHas('product', fn ($productQuery) => $productQuery
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        ->where('name', 'like', "%{$keyword}%")
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('product_code', 'like', "%{$keyword}%"));
            });
        }

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $reviews = $reviewsQuery->paginate(15)->withQueryString();

        // Luong: Gan ket qua xu ly vao bien $summary.
        $summary = [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'total' => ProductReview::count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'visible' => ProductReview::whereIn('status', ['VISIBLE', 'PENDING'])->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'hidden' => ProductReview::where('status', 'HIDDEN')->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'average' => (float) ProductReview::avg('rating'),
        ];

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.reviews.index', compact('reviews', 'summary', 'status', 'rating', 'keyword'));
    }

    /**
     * Hiển thị chi tiết đánh giá sản phẩm.
     */
    public function show(ProductReview $review): View
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $review->load(['product', 'user']);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Cập nhật trạng thái đánh giá sản phẩm.
     */
    public function update(Request $request, ProductReview $review): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate(['status' => ['required', 'in:VISIBLE,HIDDEN']]);
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $review->update($data);

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã cập nhật trạng thái bình luận.');
    }
}
