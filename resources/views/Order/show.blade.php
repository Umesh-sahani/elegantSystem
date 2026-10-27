@extends('Layouts.app-layout')
@use('App\Models\Order')

@section('title')
    <title>Elegant System — Order {{ $order->order_no }}</title>
@endsection

@section('pageTitle', 'Order Details')

@push('breadcrumb')
    <nav class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <span class="mx-1 text-muted">/</span>
        <a href="{{ route('order.list') }}">Orders</a>
        <span class="mx-1 text-muted">/</span>
        <span class="text-muted">{{ $order->order_no }}</span>
    </nav>
@endpush

@section('content')
    <div class="card mb-3">
        <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <span class="fw-semibold">
                Order {{ $order->order_no }}
                <span class="badge {{ $order->statusBadge() }} ms-1">{{ $order->statusLabel() }}</span>
            </span>
            <div class="d-flex gap-2">
                <div class="btn-group" role="group">
                    {{-- only an active order can be completed or cancelled, server checks again --}}
                    <button type="button" class="btn btn-sm btn-outline-success order-action" title="Complete"
                        data-id="{{ $order->id }}" data-no="{{ $order->order_no }}" data-action="complete"
                        {{ $order->isActive() ? '' : 'disabled' }}>
                        <i class="bi bi-check2-circle"></i> Complete
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger order-action" title="Cancel"
                        data-id="{{ $order->id }}" data-no="{{ $order->order_no }}" data-action="cancel"
                        {{ $order->isActive() ? '' : 'disabled' }}>
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                </div>
                <a href="{{ route('order.list') }}" class="btn btn-sm btn-light">Go Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><div class="text-muted small">Name</div>{{ $order->name }}</div>
                <div class="col-md-3"><div class="text-muted small">Party Name</div>{{ $order->party_name }}</div>
                <div class="col-md-3"><div class="text-muted small">Added By</div>{{ $order->creator->name ?? '-' }}</div>
                <div class="col-md-3"><div class="text-muted small">Created At</div>{{ $order->created_at?->format('d-M-Y h:i A') }}</div>
                <div class="col-md-3"><div class="text-muted small">Updated By</div>{{ $order->editor->name ?? '-' }}</div>
                <div class="col-md-3"><div class="text-muted small">Updated At</div>{{ $order->updated_at?->format('d-M-Y h:i A') }}</div>
            </div>

            @if ($order->status == Order::CANCELLED)
                <div class="alert alert-danger mt-3 mb-0 py-2">
                    <i class="bi bi-arrow-counterclockwise"></i> This order is cancelled and its stock has been added back.
                </div>
            @elseif ($order->status == Order::COMPLETED)
                <div class="alert alert-success mt-3 mb-0 py-2">
                    <i class="bi bi-lock"></i> This order is completed and can no longer be cancelled.
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white fw-semibold">Items</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $key => $item)
                        <tr>
                            <td class="text-center">{{ $key + 1 }}</td>
                            <td>{{ $item->product->name }}</td>
                            <td><code>{{ $item->product->sku }}</code></td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">{{ number_format($item->lineTotal(), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Grand Total</th>
                        <th class="text-end">{{ number_format($order->total_amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

@section('customJS')
    @include('Order._status-script')

    <script>
        // reload so badge + buttons show the new status
        function afterStatusChange() {
            setTimeout(() => window.location.reload(), 1500);
        }
    </script>
@endsection
