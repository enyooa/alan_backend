<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses'; // If your table name is "expenses"
    protected $fillable = [

        'reference_item_id',
        'provider_id',
        'document_id',
    ];

    // (optional) The inverse relationship if you want it:
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

     /* ② строка-справочник, в которой хранится «Бензин», «Дизель» и т.п.  */
     public function referenceItem()  { return $this->belongsTo(ReferenceItem::class); }

     /* ③ поставщик, от кого эта услуга / расход */
     public function provider()       { return $this->belongsTo(Provider::class); }
     public function providerItem()            //  <-- НОВОЕ
     {
         return $this->belongsTo(ReferenceItem::class, 'provider_id');
     }
}
