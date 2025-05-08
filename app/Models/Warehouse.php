<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // ← import Str

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';

    protected $fillable = [
        'name',
        'manager_id',
        'packer_id',
        'courier_id',
        'organization_id'
    ];
    public $incrementing = false;
    protected $keyType   = 'string';
    protected static function booted()
    {
        static::creating(function ($s) {
            if (empty($role->{$s->getKeyName()})) {
                $s->{$s->getKeyName()} = Str::uuid()->toString();
            }
        });
    }
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Один «главный упаковщик»
    public function packer()
    {
        return $this->belongsTo(User::class, 'packer_id');
    }

    // Один «главный курьер»
    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function documentsFromHere()
    {
        return $this->hasMany(Document::class, 'from_warehouse_id');
    }

    public function documentsToHere()
    {
        return $this->hasMany(Document::class, 'to_warehouse_id');
    }
}
