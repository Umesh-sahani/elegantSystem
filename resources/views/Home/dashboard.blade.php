@extends('Layouts.app-layout')

@section('title')
    <title>Elegant System — Dashboard</title>
@endsection

@section('pageTitle', 'Dashboard')

@push('breadcrumb')
    <nav class="breadcrumb">
        <span class="text-muted">Dashboard</span>
    </nav>
@endpush

@section('content')
    <div class="row g-3 mb-4">
        {{-- Products --}}
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Products</span>
                        <i class="bi bi-box text-muted fs-5"></i>
                    </div>
                    <div class="fs-2 fw-semibold">{{ $totalProducts }}</div>
                    <a href="{{ route('product.list') }}" class="small text-decoration-none">View products <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        {{-- Low stock --}}
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Low Stock (5 or less)</span>
                        <i class="bi bi-exclamation-triangle {{ $lowStock ? 'text-danger' : 'text-muted' }} fs-5"></i>
                    </div>
                    <div class="fs-2 fw-semibold {{ $lowStock ? 'text-danger' : '' }}">{{ $lowStock }}</div>
                    <span class="small text-muted">{{ $lowStock ? 'Products need restocking' : 'All products have stock' }}</span>
                </div>
            </div>
        </div>

        {{-- Orders + status count --}}
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Orders</span>
                        <i class="bi bi-receipt text-muted fs-5"></i>
                    </div>
                    <div class="fs-2 fw-semibold">{{ $totalOrders }}</div>
                    <div class="small text-muted d-flex flex-wrap gap-2">
                        @foreach (\App\Models\Order::STATUSES as $status => $label)
                            <span>
                                <i class="bi bi-circle-fill text-{{ \App\Models\Order::STATUS_COLORS[$status] }}" style="font-size:.5rem;vertical-align:middle"></i>
                                {{ $ordersByStatus[$status] ?? 0 }} {{ $label }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Recent Orders</span>
            <a href="{{ route('order.create') }}" class="btn btn-dark btn-sm"><i class="bi bi-plus"></i> New Order</a>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Order No</th>
                        <th>Name</th>
                        <th>Party</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td><a href="{{ route('order.show', $order->id) }}">{{ $order->order_no }}</a></td>
                            <td>{{ $order->name }}</td>
                            <td>{{ $order->party_name }}</td>
                            <td class="text-end">{{ number_format($order->total_amount, 2) }}</td>
                            <td><span class="badge {{ $order->statusBadge() }}">{{ $order->statusLabel() }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No orders yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
