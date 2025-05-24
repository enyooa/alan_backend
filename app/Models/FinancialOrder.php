<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FinancialOrder extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $table = 'financial_orders';
    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }
        });
    }
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
        'organization_id',
        'auth_user_id',
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
// app/Models/FinancialOrder.php
public function scopeForOrg($query, $orgId)
{
    return $query->where('organization_id', $orgId);
}

}
