<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;

class HomeController extends Controller
{
    public function dashboard()
    {
        $totalProducts = Product::count();
        $lowStock = Product::where('stock_qty', '<=', 5)->count();
        $totalOrders = Order::count();

        // count of orders per status: [1 => 3, 2 => 1, ...]
        $ordersByStatus = Order::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentOrders = Order::latest('id')->limit(5)->get();

        return view('Home.dashboard', compact('totalProducts', 'lowStock', 'totalOrders', 'ordersByStatus', 'recentOrders'));
    }
}
