<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') — ACADEXA</title>
   <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    @vite(['resources/css/app.css'])
</head>
<body style="background:var(--light);min-height:100vh;">
    <div class="min-vh-100 d-flex">
        <!-- Left decorative panel (hidden on mobile) -->
        <div class="d-none d-lg-flex col-lg-6 flex-column justify-content-center align-items-center p-5"
             style="background:url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=900&q=80') center/cover no-repeat; position:relative;">
            <div style="position:absolute;inset:0;background:rgba(10,42,94,.82);"></div>
            <div style="position:relative;z-index:2;text-align:center;color:#fff;">
                <h1 style="font-family:'Poppins',sans-serif;font-size:2.5rem;font-weight:800;">ACADE<span style="color:#C1440E;">XA</span></h1>
                <p style="font-size:1.1rem;opacity:.9;max-width:400px;margin:1rem auto 0;">
                    Empowering World Innovators and Leaders for Global Impact — Now Online.
                </p>
                <div style="margin-top:2rem;font-size:.85rem;opacity:.7;">
                    Operated by ZTF University Institute<br>Bertoua, East Region, Cameroon
                </div>
            </div>
        </div>

        <!-- Right auth form -->
        <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center p-4">
            <div style="width:100%;max-width:420px;">
                <div class="d-lg-none text-center mb-4">
                    <h2 style="font-family:'Poppins',sans-serif;color:var(--primary);font-weight:800;">ACADE<span style="color:var(--secondary);">XA</span></h2>
                </div>
                @include('partials.flash')
                @yield('content')
                <div class="text-center mt-4">
                    <a href="{{ route('home') }}" class="text-muted small">
                        <i class="bi bi-arrow-left"></i> Back to ACADEXA
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/app.js'])
</body>
</html>
