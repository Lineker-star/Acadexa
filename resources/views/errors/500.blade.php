<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error — ACADEXXA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Poppins', sans-serif; }
        .error-code { font-size: 8rem; font-weight: 900; color: #0A2A5E; line-height: 1; }
        .accent { color: #C1440E; }
    </style>
</head>
<body>
<div class="text-center px-4">
    <div class="error-code">5<span class="accent">0</span>0</div>
    <h2 class="fw-bold mt-2" style="color:#0A2A5E;">Something Went Wrong</h2>
    <p class="text-muted mt-2 mb-4" style="max-width:400px;margin:0 auto;">
        We encountered an unexpected error. Our team has been notified.
        Please try again in a few moments.
    </p>
    <div class="d-flex gap-2 justify-content-center">
        <a href="{{ url('/') }}" class="btn btn-primary px-4">Go Home</a>
        <button onclick="history.back()" class="btn btn-outline-secondary px-4">Go Back</button>
    </div>
</div>
</body>
</html>
