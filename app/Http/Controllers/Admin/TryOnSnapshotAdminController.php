<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TryOnSnapshot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TryOnSnapshotAdminController extends Controller
{
    // Hiá»ƒn thá»‹ danh sÃ¡ch káº¿t quáº£ thá»­ kÃ­nh trong admin.
    // Route: GET /admin/thu-kinh.
    public function index(Request $request): View
    {
        // Tá»« khÃ³a dÃ¹ng Ä‘á»ƒ lá»c theo tÃªn khÃ¡ch, email, tÃªn kÃ­nh hoáº·c mÃ£ model.
        $keyword = trim((string) $request->query('keyword'));

        // Query láº¥y lá»‹ch sá»­ thá»­ kÃ­nh má»›i nháº¥t trÆ°á»›c, kÃ¨m user/product Ä‘á»ƒ sau nÃ y cáº§n má»Ÿ rá»™ng váº«n cÃ³ dá»¯ liá»‡u.
        $snapshotsQuery = TryOnSnapshot::query()
            ->with(['product', 'user'])
            ->latest('id');

        // Náº¿u admin nháº­p Ã´ tÃ¬m kiáº¿m thÃ¬ gom Ä‘iá»u kiá»‡n vÃ o má»™t nhÃ³m where.
        if ($keyword !== '') {
            $like = "%{$keyword}%";

            $snapshotsQuery->where(function ($query) use ($like) {
                $query->where('user_name', 'like', $like)
                    ->orWhere('user_email', 'like', $like)
                    ->orWhere('product_name', 'like', $like)
                    ->orWhere('model_sku', 'like', $like);
            });
        }

        // PhÃ¢n trang Ä‘á»ƒ danh sÃ¡ch áº£nh khÃ´ng bá»‹ quÃ¡ dÃ i khi cÃ³ nhiá»u lÆ°á»£t thá»­ kÃ­nh.
        // PhÃ¢n trang 10 hÃ¬nh má»—i trang Ä‘á»ƒ admin xem áº£nh thá»­ kÃ­nh gá»n hÆ¡n.
        $snapshots = $snapshotsQuery->paginate(10)->withQueryString();

        // CÃ¡c sá»‘ liá»‡u nhá» á»Ÿ Ä‘áº§u trang giÃºp tháº§y/admin nhÃ¬n nhanh tá»•ng quan.
        $summary = [
            'total' => TryOnSnapshot::count(),
            'users' => TryOnSnapshot::distinct('user_email')->count('user_email'),
            'models' => TryOnSnapshot::distinct('model_sku')->count('model_sku'),
            'today' => TryOnSnapshot::whereDate('created_at', now()->toDateString())->count(),
        ];

        return view('admin.try-on-snapshots.index', compact('snapshots', 'summary', 'keyword'));
    }
}
