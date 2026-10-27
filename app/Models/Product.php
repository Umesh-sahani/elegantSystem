<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'name',
        'sku',
        'price',
        'stock_qty',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_qty' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
