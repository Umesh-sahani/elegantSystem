{{-- Complete / Cancel click: confirm, then call /order-complete/{id} or /order-cancel/{id}. Page must define afterStatusChange() --}}
<script>
    $(document).on('click', '.order-action', function () {
        let id = $(this).data('id');
        let orderNo = $(this).data('no');
        let action = $(this).data('action'); // "complete" or "cancel"
        let isCancel = action === 'cancel';

        Swal.fire({
            icon: isCancel ? 'warning' : 'question',
            titleText: isCancel ? 'Cancel order ' + orderNo + '?' : 'Complete order ' + orderNo + '?',
            text: isCancel ? 'Its stock will be added back. This cannot be undone.' : 'A completed order cannot be cancelled later.',
            showCancelButton: true,
            confirmButtonColor: isCancel ? '#dc3545' : '#198754',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '/order-' + action + '/' + id,
                type: 'POST',
                data: { _method: 'PUT' },
                success: function (res) {
                    Swal.fire({ icon: 'success', titleText: res.message, timer: 1800, showConfirmButton: false });
                    afterStatusChange();
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', titleText: 'Not allowed', text: xhr.responseJSON?.message ?? 'Something went wrong.' });
                    afterStatusChange();
                }
            });
        });
    });
</script>
