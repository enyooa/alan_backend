<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialOrder extends Model
{
    use HasFactory;

    protected $table = 'financial_orders';

    protected $fillable = [
        'type',
        'admin_cash_id',
        'product_subcard_id',
        'user_id',
        'provider_id',
        'financial_element_id',
        'summary_cash',
        'date_of_check',
        'photo_of_check',
    ];

    // Define relationships if needed
    public function adminCash()
    {
        return $this->belongsTo(AdminCashes::class, 'admin_cash_id');
    }

    public function productSubcard()
    {
        return $this->belongsTo(ProductSubCard::class, 'product_subcard_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function financialElement()
    {
        return $this->belongsTo(FinancialElement::class, 'financial_element_id');
    }

    // app/Models/FinancialOrder.php
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

}
