@extends('Layouts.app-layout')

@section('title')
    <title>Elegant System — Product List</title>
@endsection

@section('pageTitle', 'Products')

@push('breadcrumb')
    <nav class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <span class="mx-1 text-muted">/</span>
        <span class="text-muted">Products</span>
    </nav>
@endpush

@section('content')
    <div class="card">
        <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <span class="fw-semibold">Products</span>
            <div class="d-flex gap-2">
                <input autocomplete="off" type="text" class="form-control form-control-sm" id="global_search" placeholder="Search name / SKU..." />
                <a href="{{ route('product.create') }}" class="btn btn-dark btn-sm text-nowrap">
                    <i class="bi bi-plus"></i> Add Product
                </a>
            </div>
        </div>

        <!-- TABLE -->
        <div id="table-data">
            @include('Product._table')
        </div>
    </div>
@endsection

@section('customJS')
    <script>
        $(document).ready(function () {
            // LOAD DATA FUNCTION
            function loadData(search = '', url = "{{ route('product.list') }}") {
                $.ajax({
                    url: url,
                    type: "GET",
                    data: { search: search },
                    success: function (res) {
                        $('#table-data').html(res);
                    }
                });
            }

            // SEARCH (DEBOUNCE)
            let timer;
            $('#global_search').on('keyup', function () {
                clearTimeout(timer);
                let value = $(this).val();
                timer = setTimeout(function () {
                    loadData(value);
                }, 400);
            });

            // PAGINATION CLICK
            $(document).on('click', '#table-data .pagination a', function (e) {
                e.preventDefault();
                loadData($('#global_search').val(), $(this).attr('href'));
            });
        });
    </script>
@endsection
