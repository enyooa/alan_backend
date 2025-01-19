<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_subcard_id',
        'source_table', // Add the source_table field
    ];

    // Relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with the ProductSubCard model
    public function productSubcard()
    {
        return $this->belongsTo(ProductSubCard::class);
    }
}
