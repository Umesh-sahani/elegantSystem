<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @yield('title')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1f2937 0%, #374151 50%, #4b5563 100%);
        }
        .auth-wrap { width: 100%; max-width: 340px; font-size: .85rem; }
        .auth-card { border: 0; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 2px rgba(0, 0, 0, .25); }
        /* divider between login form and demo accounts */
        .auth-divider {
            display: flex; align-items: center; gap: 8px;
            margin: 14px 0 8px; color: #9ca3af; font-size: .68rem;
        }
        .auth-divider::before, .auth-divider::after { content: ''; flex: 1; border-top: 1px solid #e5e7eb; }

        /* demo account row: same border/radius as inputs, icon same as header logo */
        .demo-user {
            display: flex; align-items: center; gap: 8px; width: 100%;
            padding: 5px 8px; margin-bottom: 6px;
            border: 1px solid #dee2e6; border-radius: .25rem;
            background: #fff; text-align: left; font-size: .72rem; line-height: 1.25;
            transition: background .15s, border-color .15s;
        }
        .demo-user:last-child { margin-bottom: 0; }
        .demo-user:hover { background: #f8f9fa; border-color: #111827; }
        .demo-icon {
            width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: #111827; color: #fff; font-size: .8rem;
        }
        .demo-pass {
            padding: 2px 6px; border-radius: .25rem;
            background: #f8f9fa; border: 1px solid #dee2e6; color: #6c757d;
        }
        .auth-head { background: #111827; color: #fff; padding: 16px 16px 12px; text-align: center; }
        .auth-logo {
            width: 38px; height: 38px; border-radius: 50%; margin: 0 auto 6px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255, 255, 255, .12); font-size: 18px;
        }
        .input-group-text { background: #f8f9fa; }

        /* all icon boxes (email, lock, eye) same size and color */
        .input-group-text { width: 36px; justify-content: center; color: #6c757d; }

        /* show / hide password button: looks like the left icon box */
        .pw-toggle { cursor: pointer; }
        .pw-toggle:hover { color: #111827; background: #e9ecef; }
        .pw-toggle:focus-visible { outline: 0; box-shadow: 0 0 0 2px rgba(17, 24, 39, .25); }
        .input-group-text i { font-size: .9rem; line-height: 1; }

        /* input focus: 2px ring (same as app.css) */
        .form-control:focus, .form-check-input:focus { border-color: #111827; box-shadow: 0 0 0 2px rgba(17, 24, 39, .25); }
        .form-control.is-invalid:focus { border-color: #dc3545; box-shadow: 0 0 0 2px rgba(220, 53, 69, .25); }
    </style>
    @yield('customCSS')
</head>

<body>
    <div class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
        @yield('content')
    </div>

    @include('components.flash-message')

    @yield('customJS')
</body>

</html>
