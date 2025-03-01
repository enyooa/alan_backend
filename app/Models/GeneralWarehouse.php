<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralWarehouse extends Model
{
    use HasFactory;

    protected $table = 'general_warehouses';

    protected $fillable = [
        'organization_id',
        'product_subcard_id',
        'user_id',
        'address_id',
        'unit_measurement',
        'quantity',
        'price',
        'total_sum',
        'date',
        'brutto',
        'netto',
        'additional_expenses',
        'cost_price',
        'total_cost',
        'auth_user_id',
    ];

    // Relationship with ProductSubCard
    public function productSubCard()
    {
        return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
    }

    // Relationship with Address
    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
