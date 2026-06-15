<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — ACADEXXA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    @vite(['resources/css/app.css'])
</head>
<body style="background:var(--primary);min-height:100vh;display:flex;align-items:center;justify-content:center;">
    <div style="width:100%;max-width:420px;padding:1rem;">
        <div class="text-center mb-4">
            <h1 style="font-family:'Poppins',sans-serif;font-size:2rem;font-weight:800;color:#fff;">
                ACADE<span style="color:var(--secondary);">XA</span>
            </h1>
            <p style="color:rgba(255,255,255,.7);">Control Panel — Admin Access</p>
        </div>
        <div class="card border-0 shadow-lg rounded-xl">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4" style="font-family:'Poppins',sans-serif;color:var(--primary);">
                    <i class="bi bi-shield-lock me-2"></i>Admin Login
                </h5>

                @include('partials.flash')

                <form method="POST" action="{{ route('admin.login.post') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" required autofocus placeholder="admin@acadexxa.com">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="password" class="form-control"
                               required placeholder="••••••••">
                    </div>
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>
                <p class="text-center mt-3 mb-0">
                    <a href="{{ route('home') }}" class="small text-muted">← Back to ACADEXXA</a>
                </p>
            </div>
        </div>
        <p class="text-center mt-3" style="color:rgba(255,255,255,.5);font-size:.8rem;">
            Secured admin access — ZTF University Institute
        </p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/app.js'])
</body>
</html>
