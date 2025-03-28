<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'document_type_id',
        'provider_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'client_id',

        'status',
        'worker_user_id',
        'document_date',
        'comments',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
    // Связь с деталями
    public function documentItems()
    {
        return $this->hasMany(DocumentItem::class, 'document_id');
    }

    // Связь с провайдером (если есть модель Provider)
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    // Связь с «от кого» (user)
    public function sourceUser()
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    // Связь с «кому» (user)
    public function destinationUser()
    {
        return $this->belongsTo(User::class, 'destination_user_id');
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_user_id');
    }

    public function expenses()
{
    // If your Expense table has `document_id` as a foreign key
    return $this->hasMany(Expense::class, 'document_id');
}
}
