<?php

namespace App\Models;

use App\Enums\UnitEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'stock', 'initial_stock', 'unit'];

    // Cast the unit attribute to use the enum
    protected $casts = [
        'unit' => UnitEnum::class,
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
                    ->withPivot('amount');
    }
}
