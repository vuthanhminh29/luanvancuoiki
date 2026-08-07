<?php

namespace App\Http\Controllers;

use App\Models\FrameShape;
use App\Models\LensOption;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StyleAdvisorController extends Controller
{
    // Dữ liệu khuôn mặt tĩnh: đặc điểm nhận dạng + mã dáng kính (frame_shapes.code)
    // được khuyến nghị cho từng khuôn mặt. Đây là kiến thức tư vấn cố định của
    // ngành kính mắt, không phụ thuộc dữ liệu sản phẩm nên khai báo thẳng ở đây.
    private const FACE_SHAPES = [
        'OVAL' => [
            'name' => 'Oval',
            'summary' => 'Khuôn mặt cân đối, phù hợp với đa dạng dáng kính.',
            'traits' => [
                'Chiều dài khuôn mặt lớn hơn chiều rộng',
                'Đường nét cân đối, cằm hơi tròn',
                'Gò má là điểm rộng nhất nhưng không góc cạnh',
            ],
            'recommend' => ['ROUND', 'SQUARE', 'CAT_EYE', 'RECTANGLE'],
        ],
        'TRON' => [
            'name' => 'Tròn',
            'summary' => 'Nên chọn gọng có góc cạnh để tạo cảm giác thon gọn hơn.',
            'traits' => [
                'Chiều dài và chiều rộng khuôn mặt gần bằng nhau',
                'Đường viền hàm và cằm bo tròn, ít góc cạnh',
                'Gò má là điểm rộng nhất của khuôn mặt',
            ],
            'recommend' => ['SQUARE', 'RECTANGLE', 'GEOMETRIC', 'BROWLINE'],
        ],
        'VUONG' => [
            'name' => 'Vuông',
            'summary' => 'Nên chọn gọng bo tròn để làm mềm các đường nét góc cạnh.',
            'traits' => [
                'Trán, gò má và hàm có chiều rộng gần bằng nhau',
                'Đường viền hàm mạnh, góc cạnh rõ',
                'Cằm ngắn và vuông',
            ],
            'recommend' => ['ROUND', 'OVAL', 'CAT_EYE', 'AVIATOR'],
        ],
        'TRAI_TIM' => [
            'name' => 'Trái tim',
            'summary' => 'Nên chọn gọng nhẹ phần dưới để cân bằng với trán rộng.',
            'traits' => [
                'Trán rộng, thon dần xuống cằm nhọn',
                'Gò má là điểm rộng nhất',
                'Cằm nhỏ và nhọn',
            ],
            'recommend' => ['ROUND', 'OVAL', 'CAT_EYE', 'PHANTOS'],
        ],
        'KIM_CUONG' => [
            'name' => 'Kim cương',
            'summary' => 'Nên chọn gọng có viền trên nổi bật để làm dịu gò má.',
            'traits' => [
                'Gò má là điểm rộng nhất, góc cạnh',
                'Trán và cằm đều hẹp hơn gò má',
                'Đường viền hàm sắc nét',
            ],
            'recommend' => ['OVAL', 'CAT_EYE', 'ROUND', 'BROWLINE'],
        ],
        'DAI' => [
            'name' => 'Dài',
            'summary' => 'Nên chọn gọng bản to, có chiều sâu để rút ngắn khuôn mặt.',
            'traits' => [
                'Chiều dài khuôn mặt vượt trội hơn hẳn chiều rộng',
                'Trán, má và hàm có độ rộng gần bằng nhau',
                'Cằm dài, đôi khi hơi nhọn',
            ],
            'recommend' => ['SQUARE', 'BUTTERFLY', 'AVIATOR', 'GEOMETRIC'],
        ],
    ];

    // Trang tìm dáng kính phù hợp theo khuôn mặt.
    // Route: GET /tim-dang-kinh.
    public function faceShape(): View
    {
        $frameShapes = FrameShape::pluck('id', 'code');

        $faceShapes = collect(self::FACE_SHAPES)->map(function (array $shape, string $key) use ($frameShapes) {
            $shape['key'] = $key;
            $shape['recommendedShapeIds'] = collect($shape['recommend'])
                ->map(fn ($code) => $frameShapes->get($code))
                ->filter()
                ->values();

            return $shape;
        });

        return view('style.face-shape', [
            'faceShapes' => $faceShapes,
            'frameShapeNames' => FrameShape::pluck('name', 'code'),
        ]);
    }

    // Trang chọn tròng kính phù hợp (bảng giá tư vấn, không phát sinh đơn hàng thật).
    // Route: GET /chon-trong-kinh.
    public function lensSelector(Request $request): View
    {
        // variant_id chỉ có khi vào từ trang chi tiết sản phẩm (nút "Chọn tròng
        // kính"). Cần variant thật để nút "Thêm vào giỏ hàng" ở trang này có gì
        // đó hợp lệ để thêm — kiểm tra còn ACTIVE, tránh id giả từ URL.
        $frameVariantId = ProductVariant::active()->find((int) $request->query('variant_id'))?->id;

        return view('style.lens-selector', [
            'lensOptions' => LensOption::active()->orderBy('sort_order')->orderBy('name')->get(),
            'frameName' => $request->query('frame'),
            'framePrice' => (int) $request->query('frame_price', 0),
            'frameVariantId' => $frameVariantId,
        ]);
    }
}
