<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses'; // If your table name is "expenses"
    protected $fillable = [
        'name',
        'amount',
        'document_id',
        'date',
    ];

    // (optional) The inverse relationship if you want it:
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}