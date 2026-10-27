@extends('Layouts.auth-layout')

@section('title')
    <title>Elegant System — Login</title>
@endsection

@section('content')
    <div class="auth-wrap">
    <div class="card auth-card">
        <div class="auth-head">
            <div class="auth-logo"><i class="bi bi-box-seam"></i></div>
            <h6 class="mb-0">Elegant System</h6>
            <small class="text-white-50">Products &amp; Orders</small>
        </div>

        <div class="card-body p-3">
            <div class="fw-semibold">Welcome back</div>
            <p class="text-muted small mb-3">Sign in to continue</p>

            <form action="{{ route('login.authenticate') }}" method="post" autocomplete="off" novalidate>
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1" for="email">Email</label>
                    <div class="input-group input-group-sm has-validation">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" id="email"
                            name="email" autocomplete="off" placeholder="you@example.com" value="{{ old('email') }}" autofocus />
                        @if ($errors->has('email'))
                            <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                        @endif
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1" for="password">Password</label>
                    <div class="input-group input-group-sm has-validation">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                            id="password" name="password" autocomplete="new-password" placeholder="Enter your password" />
                        <button type="button" class="input-group-text pw-toggle" id="togglePassword" title="Show / hide password">
                            <i class="bi bi-eye"></i>
                        </button>
                        @if ($errors->has('password'))
                            <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                        @endif
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" />
                    <label class="form-check-label small" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-dark btn-sm w-100 py-2" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>

            {{-- testing only: seeded users from DatabaseSeeder --}}
            <div class="auth-divider"><span>Demo accounts · click to fill</span></div>

            @foreach ($demoUsers as $demo)
                <button type="button" class="demo-user" data-email="{{ $demo['email'] }}" data-password="{{ $demo['password'] }}">
                    <span class="demo-icon"><i class="bi {{ $demo['icon'] }}"></i></span>
                    <span class="flex-grow-1">
                        <span class="d-block fw-semibold">{{ $demo['role'] }}</span>
                        <span class="d-block text-muted"><i class="bi bi-envelope"></i> {{ $demo['email'] }}</span>
                    </span>
                    <span class="demo-pass"><i class="bi bi-lock"></i> {{ $demo['password'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
    </div>
@endsection

@section('customJS')
    <script>
        // show / hide password
        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });

        // demo login: click a user to fill email + password
        document.querySelectorAll('.demo-user').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('email').value = this.dataset.email;
                document.getElementById('password').value = this.dataset.password;
            });
        });

        // prevent double submit
        document.querySelector('form').addEventListener('submit', function () {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Signing in...';
        });
    </script>
@endsection
