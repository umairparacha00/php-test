<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProductsController extends Controller
{
    protected string $databaseFile = 'products.json';

    public function index()
    {
        $products = $this->formatProducts($this->getProducts());

        return view('products', compact('products'));
    }

    public function store(StoreProductRequest $request)
    {
        $products = $this->getProducts();

        $products[] = [
            'name' => $request->input('name'),
            'quantity_in_stock' => (int)$request->input('quantity_in_stock'),
            'price_per_item' => (float)$request->input('price_per_item'),
            'datetime_submitted' => Carbon::now()->toDateTimeString()
        ];

        $this->saveProduct($products);

        return response()->json(['status' => 'success', 'data' => $this->formatProducts($products)]);
    }

    public function update(UpdateProductRequest $request)
    {
        $products = $this->formatProducts($this->getProducts());

        $index = $request->input('index');

        if (!isset($products[$index])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid index'], 400);
        }

        $products[$index]['name'] = $request->input('name');
        $products[$index]['quantity_in_stock'] = (int)$request->input('quantity_in_stock');
        $products[$index]['price_per_item'] = (float)$request->input('price_per_item');

        $this->saveProduct($products);

        return response()->json(['status' => 'success', 'data' => $products]);
    }

    private function getProducts()
    {
        if (!Storage::exists($this->databaseFile)) {
            return [];
        }

        $content = Storage::get($this->databaseFile);
        $products = json_decode($content, true);
        return is_array($products) ? $products : [];
    }

    private function saveProduct(array $products)
    {
        Storage::put($this->databaseFile, json_encode($products, JSON_PRETTY_PRINT));
    }

    private function formatProducts(array $products)
    {
        return collect($products)
            ->sortByDesc('datetime_submitted')
            ->values()
            ->all();
    }

    public function destroy($index)
    {
        $products = $this->formatProducts($this->getProducts());

        if (!isset($products[$index])) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }
        array_splice($products, $index, 1);

        $this->saveProduct($products);
        return response()->json(['status' => 'success', 'data' => $products]);
    }
}
