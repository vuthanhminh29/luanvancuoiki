<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewAdminController extends Controller
{
    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status'));
        $rating = (int) $request->query('rating', 0);
        $keyword = trim((string) $request->query('keyword'));

        $reviewsQuery = ProductReview::query()
            ->with(['product', 'user'])
            ->latest('id');

        if ($status === 'VISIBLE') {
            $reviewsQuery->whereIn('status', ['VISIBLE', 'PENDING']);
        } elseif ($status === 'HIDDEN') {
            $reviewsQuery->where('status', $status);
        }

        if ($rating >= 1 && $rating <= 5) {
            $reviewsQuery->where('rating', $rating);
        }

        if ($keyword !== '') {
            $reviewsQuery->where(function ($query) use ($keyword) {
                $query->where('content', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('full_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%"))
                    ->orWhereHas('product', fn ($productQuery) => $productQuery
                        ->where('name', 'like', "%{$keyword}%")
                        ->orWhere('product_code', 'like', "%{$keyword}%"));
            });
        }

        $reviews = $reviewsQuery->paginate(15)->withQueryString();

        $summary = [
            'total' => ProductReview::count(),
            'visible' => ProductReview::whereIn('status', ['VISIBLE', 'PENDING'])->count(),
            'hidden' => ProductReview::where('status', 'HIDDEN')->count(),
            'average' => (float) ProductReview::avg('rating'),
        ];

        return view('admin.reviews.index', compact('reviews', 'summary', 'status', 'rating', 'keyword'));
    }

    public function show(ProductReview $review): View
    {
        $review->load(['product', 'user']);

        return view('admin.reviews.show', compact('review'));
    }

    public function update(Request $request, ProductReview $review): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:VISIBLE,HIDDEN']]);
        $review->update($data);

        return back()->with('success', 'Đã cập nhật trạng thái bình luận.');
    }
}
