@php
    $money = fn ($value) => $value === null ? '' : number_format((float) $value, 0, ',', '.');
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : '';
    $plain = fn ($value) => trim(strip_tags(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $productStatus = fn ($status) => match ($status) {
        'ACTIVE' => 'Đang bán',
        'DRAFT' => 'Bản nháp',
        'INACTIVE' => 'Tạm ẩn',
        'DISCONTINUED' => 'Ngừng bán',
        default => $status ?: '',
    };
    $variantStatus = fn ($status) => match ($status) {
        'ACTIVE' => 'Đang bán',
        'OUT_OF_STOCK' => 'Hết hàng',
        'DISCONTINUED' => 'Ngừng bán',
        default => $status ?: '',
    };
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px; }
        th { background: #0f766e; color: #fff; font-weight: 700; text-align: center; }
        th, td { border: 1px solid #d9e2ec; padding: 7px 9px; vertical-align: top; }
        .number { text-align: right; }
        .center { text-align: center; }
        .title { color: #0f172a; font-size: 18px; font-weight: 700; }
        .meta { color: #475569; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="title" colspan="22">Danh sách sản phẩm</td>
        </tr>
        <tr>
            <td class="meta" colspan="22">Ngày xuất: {{ $date($generatedAt) }}</td>
        </tr>
        <tr></tr>
        <tr>
            <th>STT</th>
            <th>Tên sản phẩm</th>
            <th>Danh mục</th>
            <th>Thương hiệu</th>
            <th>Dáng gọng</th>
            <th>Chất liệu gọng</th>
            <th>Chống UV</th>
            <th>Màu</th>
            <th>Size tròng</th>
            <th>Giá nhập</th>
            <th>Giá gốc</th>
            <th>Giá khuyến mãi</th>
            <th>Giá biến thể</th>
            <th>Giá đang bán</th>
            <th>Tồn kho</th>
            <th>Đã bán</th>
            <th>Lượt xem</th>
            <th>Trạng thái sản phẩm</th>
            <th>Trạng thái biến thể</th>
            <th>Ngày tạo</th>
            <th>Ngày cập nhật</th>
        </tr>
        @foreach ($rows as $index => $row)
            @php
                $displayPrice = $row->variant_price ?? $row->sale_price ?? $row->base_price;
            @endphp
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $row->name }}</td>
                <td>{{ $row->category_name ?: 'Chưa phân loại' }}</td>
                <td>{{ $row->brand_name ?: 'Chưa có thương hiệu' }}</td>
                <td>{{ $row->frame_shape_name ?: 'Chưa có dáng gọng' }}</td>
                <td>{{ $row->frame_material_name ?: 'Chưa có chất liệu' }}</td>
                <td>{{ $row->uv_protection ?: '' }}</td>
                <td>{{ $row->color_name ?: '' }}</td>
                <td>{{ $row->lens_size_name ?: '' }}</td>
                <td class="number">{{ $money($row->import_price) }}</td>
                <td class="number">{{ $money($row->base_price) }}</td>
                <td class="number">{{ $money($row->sale_price) }}</td>
                <td class="number">{{ $money($row->variant_price) }}</td>
                <td class="number">{{ $money($displayPrice) }}</td>
                <td class="number">{{ number_format((float) $row->stock_quantity, 0, ',', '.') }}</td>
                <td class="number">{{ number_format((float) $row->sold_quantity, 0, ',', '.') }}</td>
                <td class="number">{{ number_format((float) $row->view_count, 0, ',', '.') }}</td>
                <td>{{ $productStatus($row->status) }}</td>
                <td>{{ $variantStatus($row->variant_status) }}</td>
                <td>{{ $date($row->created_at) }}</td>
                <td>{{ $date($row->updated_at) }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
