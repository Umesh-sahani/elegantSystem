<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request, $perPage = 25)
    {
        $query = Order::with(['creator', 'editor'])->withCount('items');

        // search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhere('party_name', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->orderBy('id', 'desc')->paginate($perPage);

        // ajax request: return only the table
        if ($request->ajax()) {
            return view('Order._table', compact('records'))->render();
        }

        return view('Order.list', compact('records'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'price', 'stock_qty']);

        return view('Order.create', compact('products'));
    }

    // live check for the order form (debounced AJAX), store() checks again with a lock
    public function checkStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'nullable|integer|min:1',          // qty of this row (empty = only show price and stock)
            'total_quantity' => 'nullable|integer|min:1',    // qty of all rows with this product
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::find($request->product_id);
        $qty = (int) $request->quantity;
        $totalQty = $request->total_quantity ?? $qty;
        $available = $product->stock_qty >= $totalQty;

        $message = '';
        if (!$available) {
            $message = "Only {$product->stock_qty} {$product->name} in stock.";
            if ($totalQty > $qty) {
                $message .= " You already added {$totalQty} in total (all {$product->name} rows).";
            }
        }

        return response()->json([
            'status' => true,
            'available' => $available,
            'stock' => $product->stock_qty,
            'price' => $product->price,
            'line_total' => $available ? round($product->price * $qty, 2) : 0,
            'message' => $message,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'party_name' => 'required|string|max:150',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'items.required' => 'Add at least one product.',
            'items.*.product_id.required' => 'Select a product.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // same product added twice -> one line with total qty, [product_id => qty]
        $qtyByProduct = [];
        foreach ($request->items as $row) {
            $qtyByProduct[$row['product_id']] = ($qtyByProduct[$row['product_id']] ?? 0) + $row['quantity'];
        }

        DB::beginTransaction();
        try {
            // lock rows: another order for the same product waits here until we commit.
            // locked in id order, so two orders never deadlock
            $products = Product::whereIn('id', array_keys($qtyByProduct))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // check stock of ALL products first, reject whole order if any one is short
            $stockErrors = [];
            foreach ($qtyByProduct as $productId => $qty) {
                $product = $products[$productId];
                if ($product->stock_qty < $qty) {
                    $stockErrors[] = "{$product->name}: only {$product->stock_qty} in stock, you asked {$qty}.";
                }
            }

            if (!empty($stockErrors)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Order rejected, not enough stock. Nothing was changed.',
                    'errors' => ['items' => $stockErrors],
                ], 422);
            }

            // total from DB price only, any total sent by the client is ignored
            $total = 0;
            foreach ($qtyByProduct as $productId => $qty) {
                $total += $products[$productId]->price * $qty;
            }

            $order = Order::create([
                'name' => $request->name,
                'party_name' => $request->party_name,
                'total_amount' => round($total, 2),
                'status' => Order::ACTIVE,
                'added_by' => auth()->id(),
            ]);

            $order->update(['order_no' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($qtyByProduct as $productId => $qty) {
                $order->items()->create([
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_price' => $products[$productId]->price,
                ]);

                $products[$productId]->decrement('stock_qty', $qty);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Order {$order->order_no} created successfully!",
                'redirect' => route('order.show', $order->id),
            ]);

        } catch (\Throwable $e) {
            // any error -> nothing saved, no stock changed
            DB::rollBack();
            return $this->serverError($e);
        }
    }

    public function show($id)
    {
        $order = Order::with(['items.product', 'creator', 'editor'])->findOrFail($id);

        return view('Order.show', compact('order'));
    }

    // Complete an active order (no stock change)
    public function complete($id)
    {
        Order::findOrFail($id); // 404 if the order does not exist

        DB::beginTransaction();
        try {
            // lock order row so complete and cancel can't run at the same time
            $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($order->status == Order::COMPLETED) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Order is already completed.'], 422);
            }

            if ($order->status == Order::CANCELLED) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Cancelled order cannot be completed.'], 422);
            }

            $order->update(['status' => Order::COMPLETED, 'updated_by' => auth()->id()]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Order {$order->order_no} marked as Completed.",
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e);
        }
    }

    // Cancel order and put stock back
    public function cancel($id)
    {
        Order::findOrFail($id); // 404 if the order does not exist

        DB::beginTransaction();
        try {
            // lock order row: if cancel is clicked twice, second request waits,
            // then sees status = Cancelled and is rejected -> stock restored only once
            $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($order->status == Order::CANCELLED) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Order is already cancelled.'], 422);
            }

            if ($order->status == Order::COMPLETED) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Completed order cannot be cancelled.'], 422);
            }

            foreach ($order->items()->orderBy('product_id')->get() as $item) {
                Product::whereKey($item->product_id)->increment('stock_qty', $item->quantity);
            }

            $order->update(['status' => Order::CANCELLED, 'updated_by' => auth()->id()]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Order {$order->order_no} cancelled and stock restored.",
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e);
        }
    }
}
