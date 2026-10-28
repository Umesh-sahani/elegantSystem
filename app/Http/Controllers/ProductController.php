<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request, $perPage = 25)
    {
        $query = Product::with(['creator', 'editor']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('sku', 'like', "%$search%");
            });
        }

        $records = $query->orderBy('id', 'desc')->paginate($perPage);

        if ($request->ajax()) {
            return view('Product._table', compact('records'))->render();
        }

        return view('Product.list', compact('records'));
    }

    public function create()
    {
        return view('Product.create');
    }

    public function store(Request $request)
    {
        // SKU is typed by the user; store it in upper case so "pen-1" and "PEN-1" count as the same
        $sku = strtoupper(trim((string) $request->sku));
        $request->merge(['sku' => $sku]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'sku' => 'required|string|max:50|unique:products,sku',
            'price' => 'required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'stock_qty' => 'required|integer|min:0|max:1000000000',
        ], [
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $product = Product::create([
                'name' => trim($request->name),
                'sku' => $sku,
                'price' => $request->price,
                'stock_qty' => $request->stock_qty,
                'added_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Product created successfully! SKU: {$product->sku}",
            ]);

        } catch (UniqueConstraintViolationException $e) {
            // two requests saved the same SKU at the same moment, the DB unique index blocked the second one
            DB::rollBack();
            return response()->json([
                'status' => false,
                'errors' => ['sku' => ['This SKU already exists.']],
            ], 422);

        } catch (\Throwable $e) {
            // any other error: nothing saved
            DB::rollBack();
            return $this->serverError($e);
        }
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);

        return view('Product.edit', compact('product'));
    }



    public function update(Request $request, $id)
    {
        Product::findOrFail($id); // 404 if the product does not exist

        // SKU is typed by the user; store it in upper case so "pen-1" and "PEN-1" count as the same
        $sku = strtoupper(trim((string) $request->sku));
        $request->merge(['sku' => $sku]);

        // ",$id" at the end means: ignore this product's own row
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'sku' => "required|string|max:50|unique:products,sku,$id",
            'price' => 'required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'stock_qty' => 'required|integer|min:0|max:1000000000',
        ], [
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // lock the row so this edit does not overwrite stock reduced by an order at the same time
            $product = Product::whereKey($id)->lockForUpdate()->firstOrFail();

            $product->update([
                'name' => trim($request->name),
                'sku' => $sku,
                'price' => $request->price,
                'stock_qty' => $request->stock_qty,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Product updated successfully! SKU: {$product->sku}",
            ]);

        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'errors' => ['sku' => ['This SKU already exists.']],
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e);
        }
    }
}