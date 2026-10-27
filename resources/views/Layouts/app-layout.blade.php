<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  @yield('title')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>

  @yield('customCSS')
</head>

<body>
  <div class="sidebar-overlay" id="sbOverlay"></div>
  <div class="app">
    <!-- SIDEBAR -->
    @include('Layouts.Partials._sidebar')

    <!-- MAIN -->
    <div class="main">
      <!-- TOPBAR -->
      @include('Layouts.Partials._navbar')

      <!-- CONTENT -->
      <div class="content">
        @yield('content')
      </div>

      <!-- FOOTER -->
      @include('Layouts.Partials._footer')
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('assets/js/app.js') }}"></script>

  <script>
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
  </script>

  <!-- flash message -->
  @include('components.flash-message')

  {{-- logout via ajax start --}}
  <script type="text/javascript">
    $(document).on('click', '.logoutButton', function (ev) {
      ev.preventDefault();
      Swal.fire({
        title: "Logout?",
        text: "You will be signed out of the system.",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#111827",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, logout"
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            type: "POST",
            url: "{{ route('logout') }}",
            dataType: 'json',
            success: function () {
              window.location.href = "{{ route('login') }}";
            },
            error: function () {
              Swal.fire('Error', 'An error occurred while logging out.', 'error');
            }
          });
        }
      });
    });
  </script>
  {{-- logout via ajax end --}}

  @yield('customJS')
</body>

</html>
