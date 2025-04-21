<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'reference_id',
        'name',
        'description',
        'value',
        'type',
        'country',
    ];
    public function reference()
    {
        return $this->belongsTo(Reference::class, 'reference_id');
    }
}
