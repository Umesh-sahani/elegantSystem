@foreach (['success', 'error', 'warning', 'info'] as $type)
    @if (session($type))
        <script>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: '{{ $type }}',
                title: @json(session($type)),
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        </script>
    @endif
@endforeach