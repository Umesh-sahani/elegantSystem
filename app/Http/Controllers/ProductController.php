<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request, $perPage = 25)
    {
        $query = Product::with(['creator', 'editor']);

        // search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('sku', 'like', "%$search%");
            });
        }

        $records = $query->orderBy('id', 'desc')->paginate($perPage);

        // ajax request: return only the table
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
        // SKU is made from the name, so same name = same SKU = rejected
        $request->merge(['sku' => $this->makeSku($request->name)]);

        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

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
                'sku' => $request->sku,
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
            // same name saved at the same moment by someone else, unique index blocked it
            DB::rollBack();
            return $this->duplicateResponse();

        } catch (\Throwable $e) {
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

        // SKU follows the name, rename = new SKU
        $request->merge(['sku' => $this->makeSku($request->name)]);

        $validator = Validator::make($request->all(), $this->rules($id), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // lock row so this edit does not overwrite stock reduced by an order at the same time
            $product = Product::whereKey($id)->lockForUpdate()->firstOrFail();

            $product->update([
                'name' => trim($request->name),
                'sku' => $request->sku,
                'price' => $request->price,
                'stock_qty' => $request->stock_qty,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();
            return response()->json(['status' => true, 'message' => "Product updated successfully! SKU: {$product->sku}"]);

        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();
            return $this->duplicateResponse();

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e);
        }
    }

    protected function duplicateResponse()
    {
        return response()->json([
            'status' => false,
            'errors' => ['name' => ['A product with this name already exists.']],
        ], 422);
    }

    // "Wireless Mouse 2" -> WIRELESS-MOUSE-2
    protected function makeSku($name)
    {
        return Str::upper(Str::slug((string) $name));
    }

    // $id is passed on update so the product does not clash with itself
    protected function rules($id = null)
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('products', 'name')->ignore($id)],
            'sku' => ['required', Rule::unique('products', 'sku')->ignore($id)],
            'price' => 'required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'stock_qty' => 'required|integer|min:0|max:1000000000',
        ];
    }

    protected function messages()
    {
        return [
            'name.unique' => 'A product with this name already exists.',
            'sku.required' => 'Name must contain letters or numbers to make the SKU.',
            'sku.unique' => 'SKU :input already exists, a product with a very similar name is already added.',
        ];
    }
}
