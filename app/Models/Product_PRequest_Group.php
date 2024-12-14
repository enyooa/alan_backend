<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product_PRequest_Group extends Model
{
    use HasFactory;
    protected $fillable = [
        'basic_product_id',
        'price_request_id'
    ];
}
