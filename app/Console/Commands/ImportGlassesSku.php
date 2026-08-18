<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportGlassesSku extends Command
{
    protected $signature = 'glasses:import-sku
        {csv=C:\Users\vu minh\Downloads\glassesSKU.csv : Duong dan file CSV SKU}
        {--limit= : Gioi han so dong CSV can import}
        {--stock=12 : So luong ton kho moi bien the}
        {--price-min=350000 : Gia ban thap nhat}
        {--price-max=1800000 : Gia ban cao nhat}
        {--only-demo-brands : Chi them mau demo cua cac hang khac, khong doc CSV}
        {--without-demo-brands : Khong them 7 mau demo ngoai Ray-Ban}
        {--dry-run : Chi xem truoc, khong ghi database}';

    protected $description = 'Import du lieu kinh mat tu file glassesSKU.csv vao products, variants va inventories.';

    private array $imagePool = [];

    private array $columns = [];

    private const DEMO_PRODUCTS = [
        ['sku' => 'oakley_holbrook_black_prizm', 'label' => 'Oakley Holbrook OO9102 Black Ink Prizm Grey Sunglasses 55mm'],
        ['sku' => 'gucci_square_gold_brown', 'label' => 'Gucci GG0061S Square Gold Metal Frame Brown Gradient Lens 56mm'],
        ['sku' => 'prada_symbole_black_grey', 'label' => 'Prada Symbole PR 17WS Black Frame Dark Grey Lens 49mm'],
        ['sku' => 'dior_b30_silver_blue', 'label' => 'Dior B30 S1I Silver Frame Blue Mirror Lens 57mm'],
        ['sku' => 'gentlemonster_lilit_black', 'label' => 'Gentle Monster Lilit 01 Black Acetate Frame Flat Lens 62mm'],
        ['sku' => 'persol_714_havana_green', 'label' => 'Persol PO0714 Folding Havana Frame Green Crystal Lens 54mm'],
        ['sku' => 'mauijim_peahi_tortoise_bronze', 'label' => 'Maui Jim Peahi Tortoise Frame HCL Bronze Polarized Lens 65mm'],
    ];

    public function handle(InventoryService $inventory): int
    {
        $csvPath = (string) $this->argument('csv');
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $stock = max(0, (int) $this->option('stock'));
        $priceMin = max(0, (int) $this->option('price-min'));
        $priceMax = max($priceMin, (int) $this->option('price-max'));
        $dryRun = (bool) $this->option('dry-run');

        $rows = [];

        if (! $this->option('only-demo-brands')) {
            if (! is_file($csvPath)) {
                $this->error("Khong tim thay file CSV: {$csvPath}");

                return self::FAILURE;
            }

            $rows = $this->readCsv($csvPath, $limit);
        }

        if (! $this->option('without-demo-brands')) {
            $rows = array_merge($rows, self::DEMO_PRODUCTS);
        }

        if ($rows === []) {
            $this->warn('Khong co dong nao de import.');

            return self::SUCCESS;
        }

        $this->imagePool = $this->loadLocalImages();

        if ($dryRun) {
            $this->table(['SKU', 'Ten san pham', 'Hang'], array_map(function (array $row): array {
                $data = $this->buildProductData($row['sku'], $row['label'], 350000, 1800000);

                return [$row['sku'], $data['name'], $data['brand']];
            }, array_slice($rows, 0, 20)));

            $this->info('Dry run: chua ghi database.');

            return self::SUCCESS;
        }

        $warehouseId = $inventory->defaultSellableWarehouseId();
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, $stock, $priceMin, $priceMax, $warehouseId, &$created, &$updated): void {
            foreach ($rows as $index => $row) {
                $sku = $this->normalizeSku($row['sku'] ?? '');
                $label = $this->cleanText($row['label'] ?? '');

                if ($sku === '' || $label === '') {
                    continue;
                }

                $existingVariant = DB::table('product_variants')->where('sku', $sku)->first();
                $data = $this->buildProductData($sku, $label, $priceMin, $priceMax);
                $image = $this->imageForIndex($index);
                $now = now();

                $brandId = $this->firstOrCreate('brands', ['name' => $data['brand']], [
                    'slug' => Str::slug($data['brand']),
                    'description' => 'Thuong hieu mat kinh ' . $data['brand'] . '.',
                    'status' => 'ACTIVE',
                ]);

                $categoryId = $this->firstOrCreate('categories', ['slug' => 'kinh-mat'], [
                    'name' => 'Kinh mat',
                    'description' => 'Cac mau kinh ram va kinh thoi trang.',
                    'status' => 'ACTIVE',
                ]);

                $shapeId = $this->firstOrCreate('frame_shapes', ['code' => $data['shape_code']], [
                    'name' => $data['shape_name'],
                ]);

                $materialId = $this->firstOrCreate('frame_materials', ['code' => $data['material_code']], [
                    'name' => $data['material_name'],
                ]);

                $colorId = $this->findOrCreateColor($data);

                $lensSizeWhere = $this->hasColumn('lens_sizes', 'size_label')
                    ? ['size_label' => $data['size_label']]
                    : ['name' => $data['size_label']];

                $lensSizeId = $this->firstOrCreate('lens_sizes', $lensSizeWhere, [
                    'name' => $data['size_label'],
                    'lens_width' => $data['lens_width'],
                    'bridge_width' => 18,
                    'temple_length' => 145,
                ]);

                $productId = $existingVariant?->product_id;
                $productPayload = [
                    'product_code' => $data['product_code'],
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'frame_shape_id' => $shapeId,
                    'frame_material_id' => $materialId,
                    'uv_protection' => 'UV400',
                    'import_price' => (int) round($data['price'] * 0.62, -3),
                    'base_price' => $data['price'],
                    'sale_price' => null,
                    'description' => $data['description'],
                    'thumbnail_url' => $image,
                    'image_url' => $image,
                    'status' => 'ACTIVE',
                    'updated_at' => $now,
                ];

                if ($productId) {
                    $this->updateById('products', (int) $productId, $productPayload);
                    $updated++;
                } else {
                    $productPayload['created_at'] = $now;
                    $productId = $this->insertGetId('products', $productPayload);
                    $created++;
                }

                if (Schema::hasTable('category_product') && $this->hasColumn('category_product', 'category_id') && $this->hasColumn('category_product', 'product_id')) {
                    $this->updateOrInsert('category_product', [
                        'category_id' => $categoryId,
                        'product_id' => $productId,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                if ($existingVariant) {
                    $this->updateById('product_variants', (int) $existingVariant->id, [
                        'product_id' => $productId,
                        'color_id' => $colorId,
                        'lens_size_id' => $lensSizeId,
                        'variant_price' => $data['price'],
                        'image_url' => $image,
                        'status' => 'ACTIVE',
                        'updated_at' => $now,
                    ]);
                    $variantId = (int) $existingVariant->id;
                } else {
                    $variantId = $this->insertGetId('product_variants', [
                        'product_id' => $productId,
                        'sku' => $sku,
                        'color_id' => $colorId,
                        'lens_size_id' => $lensSizeId,
                        'variant_price' => $data['price'],
                        'image_url' => $image,
                        'status' => 'ACTIVE',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $this->updateOrInsert(
                    'product_images',
                    ['product_id' => $productId, 'variant_id' => $variantId, 'sort_order' => 0],
                    [
                        'image_url' => $image ?: 'upload/no-image.jpg',
                        'alt_text' => $data['name'],
                        'is_thumbnail' => true,
                        'created_at' => $now,
                    ]
                );

                $this->updateOrInsert(
                    'inventories',
                    ['warehouse_id' => $warehouseId, 'variant_id' => $variantId],
                    [
                        'quantity' => $stock,
                        'min_stock_level' => 3,
                        'updated_at' => $now,
                    ]
                );
            }
        });

        $this->info("Da import xong: tao moi {$created}, cap nhat {$updated}, tong dong xu ly " . count($rows) . '.');
        $this->info("Kho nhap ton: #{$warehouseId}, moi bien the {$stock} san pham.");

        return self::SUCCESS;
    }

    private function readCsv(string $path, ?int $limit): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) < 2) {
                continue;
            }

            $rows[] = [
                'sku' => (string) $line[0],
                'label' => (string) $line[1],
            ];

            if ($limit !== null && count($rows) >= $limit) {
                break;
            }
        }

        fclose($handle);

        return $rows;
    }

    private function firstOrCreate(string $table, array $where, array $values): int
    {
        $existing = DB::table($table)->where($where)->first();
        $now = now();

        if ($existing) {
            $this->updateById($table, (int) $existing->id, array_merge($values, ['updated_at' => $now]));

            return (int) $existing->id;
        }

        return $this->insertGetId($table, array_merge($where, $values, [
            'created_at' => $now,
            'updated_at' => $now,
        ]));
    }

    private function findOrCreateColor(array $data): int
    {
        $query = DB::table('colors');

        $query->where(function ($query) use ($data): void {
            $hasCondition = false;

            if ($this->hasColumn('colors', 'code')) {
                $query->where('code', $data['color_code']);
                $hasCondition = true;
            }

            if ($this->hasColumn('colors', 'name')) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $query->{$method}('name', $data['color_name']);
                $hasCondition = true;
            }

            if ($this->hasColumn('colors', 'hex_code')) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $query->{$method}('hex_code', $data['hex_code']);
            }
        });

        $existing = $query->first();

        if ($existing) {
            return (int) $existing->id;
        }

        return $this->insertGetId('colors', [
            'name' => $data['color_name'],
            'code' => $data['color_code'],
            'hex_code' => $data['hex_code'],
            'color_code' => $data['hex_code'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertGetId(string $table, array $payload): int
    {
        return (int) DB::table($table)->insertGetId($this->filterColumns($table, $payload));
    }

    private function updateById(string $table, int $id, array $payload): void
    {
        $payload = $this->filterColumns($table, $payload);

        if ($payload === []) {
            return;
        }

        DB::table($table)->where('id', $id)->update($payload);
    }

    private function updateOrInsert(string $table, array $where, array $payload): void
    {
        $where = $this->filterColumns($table, $where);
        $payload = $this->filterColumns($table, $payload);

        if ($where === []) {
            return;
        }

        $existing = DB::table($table)->where($where)->first();

        if ($existing) {
            if ($payload !== []) {
                DB::table($table)->where('id', $existing->id)->update($payload);
            }

            return;
        }

        DB::table($table)->insert(array_merge($where, $payload));
    }

    private function filterColumns(string $table, array $payload): array
    {
        return array_filter(
            $payload,
            fn (string $column): bool => $this->hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (! isset($this->columns[$table])) {
            $this->columns[$table] = DB::getSchemaBuilder()->getColumnListing($table);
        }

        return in_array($column, $this->columns[$table], true);
    }

    private function buildProductData(string $sku, string $label, int $priceMin, int $priceMax): array
    {
        $model = $this->modelFromSku($sku, $label);
        $brand = $this->brandFromSku($sku, $label);
        $color = $this->colorFromSku($sku, $label);
        $shape = $this->shapeFromModel($model);
        $material = $this->materialFromSku($sku, $label, $model);
        $lensWidth = $this->lensWidthFromLabel($label);
        $name = $this->productName($brand, $model, $label, $sku);
        $price = $this->deterministicPrice($sku, $priceMin, $priceMax);

        return [
            'brand' => $brand,
            'name' => $name,
            'slug' => Str::slug($name . '-' . $sku),
            'product_code' => 'SP-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $sku), 0, 32)) . '-' . strtoupper(substr(md5($sku), 0, 6)),
            'price' => $price,
            'shape_code' => $shape['code'],
            'shape_name' => $shape['name'],
            'material_code' => $material['code'],
            'material_name' => $material['name'],
            'color_code' => $color['code'],
            'color_name' => $color['name'],
            'hex_code' => $color['hex'],
            'size_label' => $lensWidth . ' mm',
            'lens_width' => $lensWidth,
            'description' => "Mau {$name} duoc import tu bo SKU demo. Thiet ke {$shape['name']}, chat lieu {$material['name']}, trong kinh {$color['name']}, chong tia UV400.",
        ];
    }

    private function brandFromSku(string $sku, string $label): string
    {
        $source = strtolower($sku . ' ' . $label);

        return match (true) {
            str_contains($source, 'oakley') => 'Oakley',
            str_contains($source, 'gucci') => 'Gucci',
            str_contains($source, 'prada') => 'Prada',
            str_contains($source, 'dior') => 'Dior',
            str_contains($source, 'gentlemonster') || str_contains($source, 'gentle monster') => 'Gentle Monster',
            str_contains($source, 'persol') => 'Persol',
            str_contains($source, 'mauijim') || str_contains($source, 'maui jim') => 'Maui Jim',
            str_contains($source, 'rayban') || str_contains($source, 'ray-ban') || str_contains($source, 'ray ban') => 'Ray-Ban',
            default => 'Khac',
        };
    }

    private function modelFromSku(string $sku, string $label): string
    {
        $source = strtolower($sku . ' ' . $label);
        $models = ['aviator', 'wayfarer', 'round', 'clubround', 'cockpit', 'justin', 'boyfriend', 'erika', 'chris', 'holbrook', 'symbole', 'lilit', 'peahi'];

        foreach ($models as $model) {
            if (str_contains($source, $model)) {
                return Str::title($model);
            }
        }

        if (preg_match('/\b([A-Z]{1,3}\s?\d{3,5}[A-Z]*)\b/', $label, $match)) {
            return strtoupper(str_replace(' ', '', $match[1]));
        }

        return 'Classic';
    }

    private function shapeFromModel(string $model): array
    {
        $key = strtolower($model);

        return match (true) {
            str_contains($key, 'aviator') || str_contains($key, 'cockpit') => ['code' => 'aviator', 'name' => 'Phi cong'],
            str_contains($key, 'round') => ['code' => 'round', 'name' => 'Trong'],
            str_contains($key, 'boyfriend') || str_contains($key, 'peahi') => ['code' => 'oversize', 'name' => 'Oversize'],
            str_contains($key, 'erika') || str_contains($key, 'lilit') => ['code' => 'oval', 'name' => 'Oval'],
            default => ['code' => 'square', 'name' => 'Vuong'],
        };
    }

    private function materialFromSku(string $sku, string $label, string $model): array
    {
        $source = strtolower($sku . ' ' . $label . ' ' . $model);

        if (str_contains($source, 'metal') || str_contains($source, 'aviator') || str_contains($source, 'cockpit') || str_contains($source, 'gold') || str_contains($source, 'silver')) {
            return ['code' => 'metal', 'name' => 'Kim loai'];
        }

        if (str_contains($source, 'rubber')) {
            return ['code' => 'rubber', 'name' => 'Nhua cao su'];
        }

        return ['code' => 'acetate', 'name' => 'Acetate'];
    }

    private function colorFromSku(string $sku, string $label): array
    {
        $source = strtolower($sku . ' ' . $label);
        $colors = [
            'black' => ['code' => 'black', 'name' => 'Den', 'hex' => '#111111'],
            'noir' => ['code' => 'black', 'name' => 'Den', 'hex' => '#111111'],
            'gold' => ['code' => 'gold', 'name' => 'Vang', 'hex' => '#C8A951'],
            '_or_' => ['code' => 'gold', 'name' => 'Vang', 'hex' => '#C8A951'],
            'silver' => ['code' => 'silver', 'name' => 'Bac', 'hex' => '#C0C0C0'],
            'gun' => ['code' => 'gunmetal', 'name' => 'Gunmetal', 'hex' => '#4B5563'],
            'havana' => ['code' => 'havana', 'name' => 'Havana', 'hex' => '#7A4A28'],
            'havane' => ['code' => 'havana', 'name' => 'Havana', 'hex' => '#7A4A28'],
            'tortoise' => ['code' => 'tortoise', 'name' => 'Doi moi', 'hex' => '#6B3F22'],
            'brown' => ['code' => 'brown', 'name' => 'Nau', 'hex' => '#7C4A2D'],
            'marron' => ['code' => 'brown', 'name' => 'Nau', 'hex' => '#7C4A2D'],
            'green' => ['code' => 'green', 'name' => 'Xanh la', 'hex' => '#2F6B3F'],
            'vert' => ['code' => 'green', 'name' => 'Xanh la', 'hex' => '#2F6B3F'],
            'blue' => ['code' => 'blue', 'name' => 'Xanh duong', 'hex' => '#2563EB'],
            'bleu' => ['code' => 'blue', 'name' => 'Xanh duong', 'hex' => '#2563EB'],
            'orange' => ['code' => 'orange', 'name' => 'Cam', 'hex' => '#F97316'],
            'pink' => ['code' => 'pink', 'name' => 'Hong', 'hex' => '#EC4899'],
            'grey' => ['code' => 'grey', 'name' => 'Xam', 'hex' => '#6B7280'],
            'gris' => ['code' => 'grey', 'name' => 'Xam', 'hex' => '#6B7280'],
        ];

        foreach ($colors as $needle => $color) {
            if (str_contains($source, $needle)) {
                return $color;
            }
        }

        return ['code' => 'mixed', 'name' => 'Phoi mau', 'hex' => '#64748B'];
    }

    private function lensWidthFromLabel(string $label): int
    {
        if (preg_match('/\b([4-6][0-9])\s*mm\b/i', $label, $match)) {
            return (int) $match[1];
        }

        return 52;
    }

    private function productName(string $brand, string $model, string $label, string $sku): string
    {
        $label = $this->cleanText($label);
        $suffix = strtoupper(str_replace(['_', '-'], ' ', $sku));

        if ($brand !== 'Khac' && str_contains(strtolower($label), strtolower(str_replace('-', '', $brand))) === false) {
            return Str::limit(trim($brand . ' ' . $model . ' ' . $suffix), 190, '');
        }

        $name = preg_replace('/\s+/', ' ', $label) ?: "{$brand} {$model}";

        return Str::limit($name . ' ' . $suffix, 190, '');
    }

    private function deterministicPrice(string $sku, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        $range = (int) floor(($max - $min) / 10000);
        $offset = crc32($sku) % max(1, $range);

        return $min + ($offset * 10000);
    }

    private function loadLocalImages(): array
    {
        $dir = public_path('upload/anh_san_pham');
        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
        $images = [];

        foreach ($files as $file) {
            $name = basename($file);
            $lower = strtolower($name);

            if (! preg_match('/^(hinh\d+|[a-f0-9-]{20,})\.(jpe?g|png|webp)$/', $lower)) {
                continue;
            }

            $images[] = 'upload/anh_san_pham/' . $name;
        }

        sort($images);

        return $images;
    }

    private function imageForIndex(int $index): ?string
    {
        if ($this->imagePool === []) {
            return null;
        }

        return $this->imagePool[$index % count($this->imagePool)];
    }

    private function normalizeSku(string $sku): string
    {
        return Str::lower(preg_replace('/[^A-Za-z0-9_-]/', '', trim($sku)) ?? '');
    }

    private function cleanText(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    }
}
