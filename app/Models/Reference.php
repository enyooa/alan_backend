<?php   // app/Models/Reference.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reference extends Model
{
    protected $fillable = ['title'];   // <— имя карточки

    /** Карточка ➜ много товаров  */
    public function items(): HasMany
    {
        return $this->hasMany(ReferenceItem::class, 'reference_id');
    }

    /* -------- КОРОТКИЕ АЛИАСЫ -------- */
    public function subCards() { return $this->items(); }
    public function products() { return $this->items(); }
}
