<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'image',
    ];

    public function category(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories', 'category_id', 'product_id');
    }

    public function menutag(): BelongsToMany
    {
        return $this->belongsToMany(Menutag::class, 'product_menutag', 'product_id', 'menutag_id');
    }
}
