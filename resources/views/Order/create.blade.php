@extends('Layouts.app-layout')

@section('title')
    <title>Elegant System — Create Order</title>
@endsection

@section('pageTitle', 'Create Order')

@push('breadcrumb')
    <nav class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <span class="mx-1 text-muted">/</span>
        <a href="{{ route('order.list') }}">Orders</a>
        <span class="mx-1 text-muted">/</span>
        <span class="text-muted">Create Order</span>
    </nav>
@endpush

@section('customCSS')
    <style>
        .items-box { max-height: 360px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; }
        .items-box thead th { position: sticky; top: 0; z-index: 2; background: #f8f9fa; }
        .items-box tfoot td { position: sticky; bottom: 0; z-index: 2; background: #f8f9fa; border-top: 2px solid #e5e7eb; }
    </style>
@endsection

@section('content')
    <div class="card">
        <div class="card-header bg-white py-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="form-head">
                <span class="form-head-icon"><i class="bi bi-receipt"></i></span>
                <div>
                    <div class="form-head-title">New Order</div>
                    <div class="form-head-sub">Add one or more products, stock is checked as you type</div>
                </div>
            </div>
            <span class="badge text-bg-light border fw-normal"><i class="bi bi-hash"></i> Order No: auto-generated on save</span>
        </div>
        <form id="submit-form" autocomplete="off" novalidate>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Order Title <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                            <input autocomplete="off" class="form-control" name="name" type="text" placeholder="e.g. Office supplies - October" />
                            <div class="invalid-feedback" id="err-name"></div>
                        </div>
                        <div class="form-text">A short title to find this order later.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Party / Customer Name <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input autocomplete="off" class="form-control" name="party_name" type="text" placeholder="e.g. ABC Traders" />
                            <div class="invalid-feedback" id="err-party_name"></div>
                        </div>
                        <div class="form-text">Who the order is for.</div>
                    </div>
                </div>

                {{-- stock / item errors from server --}}
                <div class="alert alert-danger d-none" id="items-error"></div>

                {{-- fixed height box: rows scroll, header and total stay visible --}}
                <div class="table-responsive items-box" id="items-box">
                    <table class="table align-middle mb-0" id="items-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px;">Sr. No</th>
                                <th style="min-width: 260px;">Product</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">In Stock</th>
                                <th style="width: 130px;">Qty</th>
                                <th class="text-end">Line Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">
                                    <button type="button" class="btn btn-sm btn-outline-dark px-3" id="add-row">
                                        <i class="bi bi-plus-circle"></i> Add Product
                                    </button>
                                </td>
                                <td colspan="2" class="text-end fw-semibold">Estimated Total</td>
                                <td class="text-end fw-semibold" id="grand-total">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('order.list') }}" class="btn btn-light">Go Back</a>
                <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Create Order</button>
            </div>
        </form>
    </div>

    {{-- row template (cloned by JS) --}}
    <template id="row-template">
        <tr class="item-row">
            <td class="text-center text-muted row-sr"></td>
            <td>
                <select autocomplete="off" class="form-select product-select" data-field="product_id">
                    <option value="">Select product</option>
                    @foreach ($products as $product)
                        {{-- same name can exist twice, the SKU tells them apart --}}
                        <option value="{{ $product->id }}" @disabled($product->stock_qty == 0)>
                            {{ $product->name }} ({{ $product->sku }}) - Stock: {{ $product->stock_qty }}@if ($product->stock_qty == 0) (out of stock)@endif
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback"></div>
            </td>
            <td class="text-end row-price">-</td>
            <td class="text-end row-stock">-</td>
            <td>
                <input autocomplete="off" type="number" class="form-control qty-input" data-field="quantity" min="1" step="1" placeholder="Qty" />
                <div class="invalid-feedback"></div>
                <div class="small text-muted stock-checking d-none"><span class="spinner-border spinner-border-sm"></span> checking...</div>
            </td>
            <td class="text-end row-total">0.00</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    </template>
@endsection

@section('customJS')
    <script>
        // Row handling
        function addRow() {
            $('#items-table tbody').append($('#row-template').html());
            reindexRows();
        }

        // new row: scroll the box to the bottom and focus its product dropdown
        function addRowAndScroll() {
            addRow();
            let box = document.getElementById('items-box');
            $('#items-table tbody .item-row:last .product-select').get(0).focus({ preventScroll: true });
            box.scrollTop = box.scrollHeight;
        }

        // keep input names as items[0][product_id], items[1][quantity] ...
        function reindexRows() {
            $('#items-table tbody .item-row').each(function (index) {
                $(this).find('.row-sr').text(index + 1);
                $(this).find('[data-field]').each(function () {
                    $(this).attr('name', 'items[' + index + '][' + $(this).data('field') + ']');
                });
            });
            calculateTotal();
        }

        // sum of line totals returned by the server (display only, store() recalculates)
        function calculateTotal() {
            let total = 0;
            $('#items-table tbody .item-row').each(function () {
                total += parseFloat($(this).data('line')) || 0;
            });
            $('#grand-total').text(total.toFixed(2));
        }

        function resetRow($row) {
            $row.data('line', 0);
            $row.find('.row-price, .row-stock').text('-');
            $row.find('.row-total').text('0.00');
            calculateTotal();
        }

        // ask server for price, stock and line total of one row
        function checkRow($row) {
            let productId = $row.find('.product-select').val();
            let $qty = $row.find('.qty-input');
            let qty = parseInt($qty.val());

            if (!productId) {
                resetRow($row);
                return;
            }
            qty = qty >= 1 ? qty : ''; // empty qty: server only returns price and stock

            $row.find('.stock-checking').removeClass('d-none');

            // cancel previous request of this row so an old answer never overwrites a new one
            if ($row.data('xhr')) $row.data('xhr').abort();

            // same product can be in more than one row: stock is checked on the total of those rows
            let totalQty = 0;
            $('#items-table tbody .item-row').each(function () {
                if ($(this).find('.product-select').val() === productId) {
                    totalQty += parseInt($(this).find('.qty-input').val()) || 0;
                }
            });

            let xhr = $.ajax({
                url: "{{ route('order.check-stock') }}",
                type: "GET",
                data: { product_id: productId, quantity: qty, total_quantity: totalQty || "" },
                success: function (res) {
                    $row.find('.row-price').text(parseFloat(res.price).toFixed(2));
                    $row.find('.row-stock').text(res.stock);

                    if (!res.available) {
                        // not enough stock: red input, clear qty, alert
                        $qty.val('').addClass('is-invalid').next('.invalid-feedback').text(res.message);
                        $row.data('line', 0);
                        $row.find('.row-total').text('0.00');
                        Swal.fire({ toast: true, position: 'top-end', icon: 'warning', timer: 3000, showConfirmButton: false, titleText: res.message });
                    } else {
                        $qty.removeClass('is-invalid');
                        $row.data('line', res.line_total);
                        $row.find('.row-total').text(parseFloat(res.line_total).toFixed(2));
                    }
                    calculateTotal();
                },
                error: function (xhr, status) {
                    if (status !== 'abort') resetRow($row);
                },
                complete: function (xhr, status) {
                    if (status !== 'abort') $row.find('.stock-checking').addClass('d-none');
                }
            });

            $row.data('xhr', xhr);
        }

        // debounce per row: wait until user stops typing for 400ms
        function checkRowDebounced($row) {
            clearTimeout($row.data('timer'));
            $row.data('timer', setTimeout(() => checkRow($row), 400));
        }

        // every row needs a product and a qty of at least 1 (server checks this again)
        function allRowsFilled() {
            let ok = true;
            $('#items-table tbody .item-row').each(function () {
                let $product = $(this).find('.product-select');
                let $qty = $(this).find('.qty-input');

                if (!$product.val()) {
                    $product.addClass('is-invalid').next('.invalid-feedback').text('Select a product.');
                    ok = false;
                }
                if (!(parseInt($qty.val()) >= 1)) {
                    $qty.addClass('is-invalid').next('.invalid-feedback').text('Enter quantity.');
                    ok = false;
                }
            });

            if (!ok) {
                Swal.fire({ icon: 'warning', titleText: 'Fill all rows first', text: 'Every row needs a product and a quantity.' });
            }
            return ok;
        }

        $('#add-row').on('click', function () {
            if (allRowsFilled()) addRowAndScroll();
        });

        $(document).on('click', '.remove-row', function () {
            if ($('#items-table tbody .item-row').length === 1) {
                Swal.fire('Info', 'Order needs at least one product.', 'info');
                return;
            }
            $(this).closest('tr').remove();
            reindexRows();
        });

        $(document).on('input', '.qty-input', function () {
            $(this).removeClass('is-invalid').next('.invalid-feedback').text('');
            checkRowDebounced($(this).closest('tr'));
        });

        // product changed: check price and stock for this row (same product in two rows is allowed,
        // the server adds them into one line when saving)
        $(document).on('change', '.product-select', function () {
            $(this).removeClass('is-invalid');
            checkRowDebounced($(this).closest('tr'));
        });

        $(document).on('input', 'input[name="name"], input[name="party_name"]', function () {
            $(this).removeClass('is-invalid');
        });

        // start with one row
        addRow();

        // Submit
        function clearAllErrors() {
            $('.is-invalid').removeClass('is-invalid');
            $('#items-error').addClass('d-none').html('');
        }

        $(document).on("submit", "#submit-form", function (e) {
            e.preventDefault();
            clearAllErrors();

            if (!allRowsFilled()) return;

            let $btn = $('button[type="submit"]');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

            $.ajax({
                url: "{{ route('order.store') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Order Created', text: res.message })
                        .then(() => window.location.href = res.redirect);
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Create Order');

                    if (xhr.status !== 422) {
                        Swal.fire('Error', xhr.responseJSON?.message ?? 'Something went wrong.', 'error');
                        return;
                    }

                    let errors = xhr.responseJSON.errors || {};
                    let listErrors = [];

                    $.each(errors, function (field, msgs) {
                        if (field === 'items') {
                            // stock errors (array of messages) or "add at least one product"
                            listErrors = listErrors.concat(msgs);
                        } else if (field.startsWith('items.')) {
                            // items.0.quantity -> items[0][quantity]
                            let parts = field.split('.');
                            let $input = $('[name="items[' + parts[1] + '][' + parts[2] + ']"]');
                            $input.addClass('is-invalid').next('.invalid-feedback').text(msgs[0]);
                        } else {
                            $('[name="' + field + '"]').addClass('is-invalid');
                            $('#err-' + field).text(msgs[0]);
                        }
                    });

                    if (listErrors.length) {
                        // text() per line so product names can't inject HTML
                        $('#items-error').removeClass('d-none').empty();
                        listErrors.forEach(msg => $('#items-error').append($('<div>').text(msg)));
                        if (xhr.responseJSON.message) {
                            Swal.fire('Order Rejected', xhr.responseJSON.message, 'error');
                        }
                    }
                }
            });
        });
    </script>
@endsection
