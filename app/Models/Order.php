<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    // Order status: a new order is Active, then it is Completed or Cancelled (both final)
    const ACTIVE = 1;
    const COMPLETED = 2;
    const CANCELLED = 3;

    const STATUSES = [
        self::ACTIVE => 'Active',
        self::COMPLETED => 'Completed',
        self::CANCELLED => 'Cancelled',
    ];

    // bootstrap color per status (badges, dashboard dots)
    const STATUS_COLORS = [
        self::ACTIVE => 'primary',
        self::COMPLETED => 'success',
        self::CANCELLED => 'danger',
    ];

    protected $fillable = [
        'order_no',
        'name',
        'party_name',
        'total_amount',
        'status',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'status' => 'integer',
    ];

    public function statusLabel()
    {
        return self::STATUSES[$this->status] ?? '-';
    }

    public function statusBadge()
    {
        return 'text-bg-' . (self::STATUS_COLORS[$this->status] ?? 'secondary');
    }

    // only an Active order can be completed or cancelled
    public function isActive()
    {
        return $this->status == self::ACTIVE;
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
