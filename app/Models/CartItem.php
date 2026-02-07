<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Cart;
use App\Models\Ebook;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'ebook_id', 
        // 'title',
        'quantity',
        'price',
        'total_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Cart item belongs to a cart
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    // Cart item belongs to an ebook
    public function ebook()
    {
        return $this->belongsTo(Ebook::class, 'ebook_id');
    }
}
