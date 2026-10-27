@extends('Layouts.app-layout')

@section('title')
    <title>Elegant System — Order List</title>
@endsection

@section('pageTitle', 'Orders')

@push('breadcrumb')
    <nav class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <span class="mx-1 text-muted">/</span>
        <span class="text-muted">Orders</span>
    </nav>
@endpush

@section('content')
    <div class="card">
        <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <span class="fw-semibold">Orders</span>
            <div class="d-flex gap-2">
                <select autocomplete="off" class="form-select form-select-sm" id="status_filter" style="width: 150px;">
                    <option value="">All Status</option>
                    @foreach (\App\Models\Order::STATUSES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input autocomplete="off" type="text" class="form-control form-control-sm" id="global_search" placeholder="Search order / party..." />
                <a href="{{ route('order.create') }}" class="btn btn-dark btn-sm text-nowrap">
                    <i class="bi bi-plus"></i> New Order
                </a>
            </div>
        </div>

        <!-- TABLE -->
        <div id="table-data">
            @include('Order._table')
        </div>
    </div>
@endsection

@section('customJS')
    @include('Order._status-script')

    <script>
        // LOAD DATA FUNCTION (used by search, filter, pagination and after status change)
        function loadData(url = "{{ route('order.list') }}") {
            $.ajax({
                url: url,
                type: "GET",
                data: {
                    search: $('#global_search').val(),
                    status: $('#status_filter').val()
                },
                success: function (res) {
                    $('#table-data').html(res);
                }
            });
        }

        // called by _status-script after a successful status change
        function afterStatusChange() {
            loadData();
        }

        $(document).ready(function () {
            // SEARCH (DEBOUNCE)
            let timer;
            $('#global_search').on('keyup', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    loadData();
                }, 400);
            });

            $('#status_filter').on('change', function () {
                loadData();
            });

            // PAGINATION CLICK
            $(document).on('click', '#table-data .pagination a', function (e) {
                e.preventDefault();
                loadData($(this).attr('href'));
            });
        });
    </script>
@endsection
