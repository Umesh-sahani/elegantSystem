@extends('Layouts.app-layout')

@section('title')
    <title>Elegant System — Create Product</title>
@endsection

@section('pageTitle', 'Create Product')

@push('breadcrumb')
    <nav class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <span class="mx-1 text-muted">/</span>
        <a href="{{ route('product.list') }}">Products</a>
        <span class="mx-1 text-muted">/</span>
        <span class="text-muted">Create Product</span>
    </nav>
@endpush

@section('content')
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-header bg-white py-3">
            <div class="form-head">
                <span class="form-head-icon"><i class="bi bi-box-seam"></i></span>
                <div>
                    <div class="form-head-title">New Product</div>
                    <div class="form-head-sub">Add a product with its price and stock</div>
                </div>
            </div>
        </div>
        <form id="submit-form" autocomplete="off" novalidate>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Product Name <span class="text-danger">*</span> <small class="text-muted fw-normal">(must be unique, SKU is made from it)</small></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                            <input autocomplete="off" class="form-control" name="name" type="text" placeholder="e.g. Wireless Mouse" />
                            <div class="invalid-feedback" id="err-name"></div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <label class="form-label">Price <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">₹</span>
                            <input autocomplete="off" class="form-control" name="price" type="number" min="0" step="0.01" placeholder="0.00" />
                            <div class="invalid-feedback" id="err-price"></div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <label class="form-label">Stock Qty <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-boxes"></i></span>
                            <input autocomplete="off" class="form-control" name="stock_qty" type="number" min="0" step="1" placeholder="0" />
                            <div class="invalid-feedback" id="err-stock_qty"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('product.list') }}" class="btn btn-light">Go Back</a>
                <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Create Product</button>
            </div>
        </form>
    </div>
@endsection

@section('customJS')
    <script>
        function showErr(field, msg) {
            // sku comes from name: show its error under name, unless name already has an error
            if (field === 'sku') {
                if ($('[name="name"]').hasClass('is-invalid')) return;
                field = 'name';
            }
            $('[name="' + field + '"]').addClass('is-invalid');
            $('#err-' + field).text(msg);
        }

        function clearAllErrors() {
            $('.is-invalid').removeClass('is-invalid');
        }

        $(document).on('input change', '.form-control, .form-select', function () {
            $(this).removeClass('is-invalid');
        });

        $(document).on("submit", "#submit-form", function (e) {
            e.preventDefault();
            clearAllErrors();

            let $btn = $('button[type="submit"]');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

            $.ajax({
                url: "{{ route('product.store') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Saved', text: res.message })
                        .then(() => window.location.href = "{{ route('product.list') }}");
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Create Product');
                    if (xhr.status === 422) {
                        $.each(xhr.responseJSON.errors, function (field, msgs) {
                            showErr(field, msgs[0]);
                        });
                    } else {
                        Swal.fire('Error', xhr.responseJSON?.message ?? 'Something went wrong.', 'error');
                    }
                }
            });
        });
    </script>
@endsection
