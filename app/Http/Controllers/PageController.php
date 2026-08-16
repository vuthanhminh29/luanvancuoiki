<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Hiển thị trang liên hệ.
     */
    public function contact(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('pages.contact');
    }

    /**
     * Hiển thị trang hỗ trợ và tìm kiếm nội dung hỗ trợ.
     */
    public function support(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $query.
        $query = trim((string) $request->query('q', ''));
        // Luong: Gan ket qua xu ly vao bien $items.
        $items = collect($this->supportItems());

        // Luong: Gan ket qua xu ly vao bien $results.
        $results = $query === ''
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ? collect()
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            : $items->filter(function (array $item) use ($query) {
                // Luong: Gan ket qua xu ly vao bien $keyword.
                $keyword = $this->normalizeSearchText($query);
                // Luong: Gan ket qua xu ly vao bien $content.
                $content = $this->normalizeSearchText(implode(' ', [
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    $item['title'],
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    $item['category'],
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    $item['description'],
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    implode(' ', $item['keywords']),
                ]));

                // Luong: Tra ve ket qua cuoi cung cua ham.
                return str_contains($content, $keyword);
            })->values();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('pages.support', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'query' => $query,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'supportResults' => $results,
        ]);
    }

    /**
     * Chuẩn hóa chữ để tìm kiếm dễ hơn.
     */
    private function normalizeSearchText(string $text): string
    {
        return Str::of($text)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]+/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    /**
     * Trả về các mục hỗ trợ có sẵn.
     */
    private function supportItems(): array
    {
        return [
            [
                'category' => 'Đơn hàng',
                'title' => 'Kiểm tra trạng thái đơn hàng',
                'description' => 'Xem danh sách đơn mua, trạng thái xác nhận, đang giao, đã giao hoặc đã hủy.',
                'url' => route('account.orders.index'),
                'keywords' => ['đơn mua', 'trạng thái', 'theo dõi đơn hàng', 'don hang', 'order'],
            ],
            [
                'category' => 'Giao hàng',
                'title' => 'Phương thức và thời gian giao hàng',
                'description' => 'Thông tin thời gian giao dự kiến, phí vận chuyển và khu vực hỗ trợ giao hàng.',
                'url' => route('pages.support', ['q' => 'giao hàng']),
                'keywords' => ['giao hàng', 'vận chuyển', 'ship', 'phí vận chuyển', 'thời gian giao'],
            ],
            [
                'category' => 'Thanh toán',
                'title' => 'Thanh toán khi nhận hàng',
                'description' => 'Hỗ trợ phương thức thanh toán và kiểm tra thông tin khi đặt hàng.',
                'url' => route('checkout.index'),
                'keywords' => ['thanh toán', 'cod', 'tiền mặt', 'payment'],
            ],
            [
                'category' => 'Hoàn/Đổi',
                'title' => 'Gửi yêu cầu hoàn hoặc đổi sản phẩm',
                'description' => 'Tạo yêu cầu hoàn/đổi sau khi đơn hàng giao thành công và còn trong thời hạn hỗ trợ.',
                'url' => route('returns.index'),
                'keywords' => ['hoàn đổi', 'đổi trả', 'trả hàng', 'return', 'exchange'],
            ],
            [
                'category' => 'Sản phẩm',
                'title' => 'Tìm kính và thử kính AI',
                'description' => 'Tìm sản phẩm kính mắt, xem chi tiết kính và dùng chức năng thử kính trực tuyến.',
                'url' => route('products.index'),
                'keywords' => ['sản phẩm', 'kính', 'thử kính', 'try on', 'cửa hàng'],
            ],
            [
                'category' => 'Liên hệ',
                'title' => 'Liên hệ chăm sóc khách hàng',
                'description' => 'Gửi thông tin cần hỗ trợ về đơn hàng, sản phẩm, giao hàng hoặc yêu cầu sau bán hàng.',
                'url' => route('pages.contact'),
                'keywords' => ['liên hệ', 'hỗ trợ', 'chat', 'customer service'],
            ],
        ];
    }
}
