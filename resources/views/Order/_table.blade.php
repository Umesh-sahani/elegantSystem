<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th class="text-center">Sr.No</th>
                <th>Order No</th>
                <th>Name</th>
                <th>Party Name</th>
                <th class="text-center">Items</th>
                <th class="text-end">Total</th>
                <th>Status</th>
                <th>Added By</th>
                <th>Updated By</th>
                <th>Created At</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $key => $row)
                <tr>                   
                    <td class="text-center">{{ $records->firstItem() + $key }}</td>
                    <td><a href="{{ route('order.show', $row->id) }}">{{ $row->order_no }}</a></td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->party_name }}</td>
                    <td class="text-center">{{ $row->items_count }}</td>
                    <td class="text-end">{{ number_format($row->total_amount, 2) }}</td>
                    <td><span class="badge {{ $row->statusBadge() }}">{{ $row->statusLabel() }}</span></td>
                    <td>{{ $row->creator->name ?? '-' }}</td>
                    <td>{{ $row->editor->name ?? '-' }}</td>
                    <td class="text-nowrap">{{ $row->created_at?->format('d-M-Y h:i A') }}</td>
                    <td class="text-center text-nowrap">
                        <div class="btn-group" role="group">
                            <a href="{{ route('order.show', $row->id) }}" class="btn btn-sm btn-outline-secondary" title="View Order">
                                <i class="bi bi-eye"></i>
                            </a>
                            {{-- only an active order can be completed or cancelled, server checks again --}}
                            <button type="button" class="btn btn-sm btn-outline-success order-action" title="Complete"
                                data-id="{{ $row->id }}" data-no="{{ $row->order_no }}" data-action="complete"
                                {{ $row->isActive() ? '' : 'disabled' }}>
                                <i class="bi bi-check2-circle"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger order-action" title="Cancel"
                                data-id="{{ $row->id }}" data-no="{{ $row->order_no }}" data-action="cancel"
                                {{ $row->isActive() ? '' : 'disabled' }}>
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center text-muted py-4">No Data Found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- pagination link --}}
@if ($records->hasPages())
    <div class="p-3">{{ $records->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
@endif
