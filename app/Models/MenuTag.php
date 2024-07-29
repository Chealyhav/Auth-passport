<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuTag extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
        'name',
        'des',
        'created_at',
        'updated_at',
    ];

    public function product(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_menutag', 'product_id', 'menutag_id');
    }
}
