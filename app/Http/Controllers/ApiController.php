<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;

// Public (no login) read-only API. Only safe fields are returned.
class ApiController extends Controller
{
    protected $perPage = 100;

    // GET /api/users?page=1
    public function users()
    {
        $users = User::orderBy('id')
            ->paginate($this->perPage, ['id', 'name', 'email', 'created_at']);

        return response()->json($users);
    }

    // GET /api/orders?page=1&status=1
    public function orders()
    {
        $query = Order::with(['items.product:id,name,sku', 'creator:id,name']);

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        $orders = $query->orderBy('id', 'desc')
            ->paginate($this->perPage)
            ->through(fn ($order) => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'name' => $order->name,
                'party_name' => $order->party_name,
                'total_amount' => $order->total_amount,
                'status' => $order->statusLabel(),
                'added_by' => $order->creator->name ?? null,
                'created_at' => $order->created_at,
                'items' => $order->items->map(fn ($item) => [
                    'product' => $item->product->name,
                    'sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => number_format($item->lineTotal(), 2, '.', ''),
                ]),
            ]);

        return response()->json($orders);
    }
}
