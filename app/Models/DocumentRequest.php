<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $table = 'documents_requests';

    protected $fillable = [
        'client_id',
        'product_subcard_id',
        'price',
        'unit_measurement_id',
        'amount',
        'brutto',
        'netto',
    ];

    public function productSubcard()
{
    return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
}
public function unitMeasurement()
{
    return $this->belongsTo(Unit_measurement::class, 'unit_measurement_id');
}
public function client()
{
    return $this->belongsTo(User::class, 'client_id');
}

}
