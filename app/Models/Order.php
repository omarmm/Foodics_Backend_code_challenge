<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'quantity'];

     /**
     * The products that belong to the order.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class)
                    ->withPivot('quantity');
    }
}
