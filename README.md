# Luan Van Mat Kinh

Project Laravel cho website ban kinh mat.

## Cau truc chinh

- `routes/web.php`: khai bao route web Laravel.
- `app/Models`: Eloquent model theo cac bang trong database `luanvan_ban_mat_kinh`.
- `app/Http/Controllers`: controller trang chu, san pham, gio hang, checkout va admin.
- `resources/views`: Blade view, layout va component giao dien.
- `resources/css/app.css`: CSS chinh build qua Vite.
- `public/upload`: anh san pham va anh upload hien co.

## Chay project

```bash
composer install
npm install
npm run build
php artisan serve
```

Database dang cau hinh trong `.env`:

```env
DB_CONNECTION=mysql
DB_DATABASE=luanvan_ban_mat_kinh
DB_USERNAME=root
DB_PASSWORD=
```

## Route hien co

- `/`: trang chu.
- `/san-pham`: danh sach san pham, loc theo danh muc, thuong hieu, tu khoa.
- `/san-pham/{slug}`: chi tiet san pham.
- `/gio-hang`: gio hang session.
- `/thanh-toan`: man hinh xem lai gio hang.
- `/admin`: dashboard quan tri.
