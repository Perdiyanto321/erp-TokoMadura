<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP Toko Madura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .login-card { max-width: 400px; margin: 80px auto; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card login-card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="card-title text-center fw-bold mb-1">ERP Toko Madura</h4>
                <p class="text-muted text-center small mb-4">Masuk untuk mengelola sistem toko</p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold">Username</label>
                        <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}" placeholder="Masukkan username" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label small" for="remember">Ingat Saya</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Masuk</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>