<?php  // app/Models/ReferenceItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferenceItem extends Model
{
    protected $fillable = [
        'reference_id',   // FK → Reference
        'card_id',        // FK → Другая ReferenceItem   (если это «под‑карточка»)
        'name','description','value','type','country','provider_id','photo',
    ];
    protected $appends = ['photo_url'];

    /* 1‑б. «Карточка», к которой относится под‑карточка */
    public function card()         // Родительская под-карточка, если есть
    {
        return $this->belongsTo(ReferenceItem::class, 'card_id');
    }
    /* 1‑в. Все под‑карточки данного товара */
    public function subCards(): HasMany
    {
        return $this->hasMany(ReferenceItem::class, 'card_id');
    }

    /* Алиас: чтобы $item->productCard продолжал работать */
    public function productCard() { return $this->card(); }

    public function expenses()        // чтобы можно было eager-load'ить “name”
{
    return $this->hasMany(Expense::class, 'reference_item_id');
}
public function reference()
    {
        return $this->belongsTo(Reference::class, 'reference_id');
    }
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo            // если в БД есть путь
            ? asset('storage/'.$this->photo)
            : null;
    }
}
