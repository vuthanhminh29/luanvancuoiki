<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function contact(): View
    {
        return view('pages.contact');
    }

    public function support(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $items = collect($this->supportItems());

        $results = $query === ''
            ? collect()
            : $items->filter(function (array $item) use ($query) {
                $keyword = $this->normalizeSearchText($query);
                $content = $this->normalizeSearchText(implode(' ', [
                    $item['title'],
                    $item['category'],
                    $item['description'],
                    implode(' ', $item['keywords']),
                ]));

                return str_contains($content, $keyword);
            })->values();

        return view('pages.support', [
            'query' => $query,
            'supportResults' => $results,
        ]);
    }

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
