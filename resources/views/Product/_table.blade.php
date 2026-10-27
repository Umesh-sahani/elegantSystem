<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th class="text-center">Sr.No</th>
                <th>Name</th>
                <th>SKU</th>
                <th class="text-end">Price</th>
                <th class="text-end">Stock</th>
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
                    <td>{{ $row->name }}</td>
                    <td><code>{{ $row->sku }}</code></td>
                    <td class="text-end">{{ number_format($row->price, 2) }}</td>
                    <td class="text-end">
                        {{ $row->stock_qty }}
                        @if ($row->stock_qty == 0)
                            <span class="badge bg-danger ms-1">Out of stock</span>
                        @elseif ($row->stock_qty <= 5)
                            <span class="badge bg-warning text-dark ms-1">Low</span>
                        @endif
                    </td>
                    <td>{{ $row->creator->name ?? '-' }}</td>
                    <td>{{ $row->editor->name ?? '-' }}</td>
                    <td class="text-nowrap">{{ $row->created_at?->format('d-M-Y h:i A') }}</td>
                    <td class="text-center text-nowrap">
                        <div class="btn-group" role="group">
                            <a href="{{ route('product.edit', $row->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Product">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">No Data Found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- pagination link --}}
@if ($records->hasPages())
    <div class="p-3">{{ $records->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
@endif
