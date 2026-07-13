<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Đăng nhập Admin - {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('admin_assets/img/favicon.ico') }}" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('admin_assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        *{box-sizing:border-box}body{background:#f3f6fa;color:#111827;font-family:Heebo,Arial,sans-serif;margin:0}.admin-login{align-items:center;display:flex;justify-content:center;min-height:100vh;padding:24px}.admin-login-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 18px 48px rgba(16,24,40,.08);max-width:440px;padding:34px;width:100%}.admin-brand{align-items:center;display:flex;gap:12px;justify-content:center;margin-bottom:22px}.admin-brand-icon{align-items:center;background:#0f172a;border-radius:10px;color:#fff;display:inline-flex;height:46px;justify-content:center;width:46px}.admin-brand h1{color:#111827;font-size:26px;font-weight:900;margin:0}.admin-sub{color:#667085;font-size:14px;margin:-10px 0 24px;text-align:center}.admin-field{margin-bottom:15px}.admin-field label{color:#344054;display:block;font-size:13px;font-weight:900;margin-bottom:7px}.admin-input{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:14px;font-weight:700;min-height:46px;padding:10px 12px;width:100%}.admin-input:focus{border-color:#111827;box-shadow:0 0 0 3px rgba(17,24,39,.12);outline:none}.admin-btn{align-items:center;background:#111827;border:1px solid #111827;border-radius:7px;color:#fff;cursor:pointer;display:inline-flex;font-size:15px;font-weight:900;gap:8px;justify-content:center;min-height:46px;width:100%}.admin-btn:hover{background:#0f172a}.admin-error{background:#fee2e2;border:1px solid #fecaca;border-radius:7px;color:#991b1b;font-size:13px;font-weight:800;line-height:1.45;margin-bottom:14px;padding:10px 12px}.admin-links{align-items:center;display:flex;justify-content:space-between;margin-top:16px}.admin-links a{color:#475467;font-size:13px;font-weight:800;text-decoration:none}.admin-links a:hover{color:#111827}
    </style>
</head>
<body>
    <main class="admin-login">
        <form class="admin-login-card" method="post" action="{{ route('admin.login.store') }}">
            @csrf
            <div class="admin-brand">
                <span class="admin-brand-icon"><i class="fa fa-shield-alt"></i></span>
                <h1>ADMIN PANEL</h1>
            </div>
            <p class="admin-sub">Đăng nhập bằng tài khoản có quyền quản trị.</p>

            @if ($errors->any())
                <div class="admin-error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="admin-field">
                <label for="email">Email admin</label>
                <input class="admin-input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="admin@gmail.com">
            </div>

            <div class="admin-field">
                <label for="password">Mật khẩu</label>
                <input class="admin-input" id="password" name="password" type="password" required placeholder="Nhập mật khẩu">
            </div>

            <button class="admin-btn" type="submit"><i class="fa fa-sign-in-alt"></i> Đăng nhập Admin</button>

            <div class="admin-links">
                <a href="{{ route('home') }}"><i class="fa fa-home"></i> Về website</a>
                <a href="{{ route('login') }}">Đăng nhập khách hàng</a>
            </div>
        </form>
    </main>
</body>
</html>
