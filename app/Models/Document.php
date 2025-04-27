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

    public function client()            // клиент-покупатель
    {
        /* вытягиваем только нужные поля; добавьте phone/e-mail,
           если они требуются на фронте                           */
        return $this->belongsTo(User::class, 'client_id')
                    ->select('id','first_name','last_name','surname');
    }

    public function getClientInfoAttribute()
    {
        // если связь уже загружена – берём её, иначе подгружаем
        return $this->relationLoaded('client')
               ? $this->client
               : $this->client()->first();
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
public function items()            { return $this->hasMany(DocumentItem::class,'document_id'); }


public function toWarehouse()      { return $this->belongsTo(Warehouse::class,'to_warehouse_id'); }


public function fromWarehouse()    { return $this->belongsTo(Warehouse::class,'from_warehouse_id'); }

public function providerItem()           // вместо старой Provider-модели
{
    /*  provider_id  теперь →  id из reference_items
        у самой строки‐item сразу вытянем “родителя” – Reference (карточку «Поставщик»),
        чтобы на фронте был и заголовок, и сам поставщик-item                    */
    return $this->belongsTo(ReferenceItem::class, 'provider_id')
                ->with('reference:id,title');
}

protected $appends = ['client_info'];

    // чтобы не засорять ответ лишними полями
    protected $hidden  = [
        'client_id',    // raw-id больше не нужен
        'client',       // сама relation-коллекция
        'from_warehouse_id',  // по-желанию
    ];
}
