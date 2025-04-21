<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reference extends Model
{
    protected $fillable = ['title', 'card_id'];

    /**  A reference owns many reference‑items. */
    public function items(): HasMany
    {
        return $this->hasMany(ReferenceItem::class, 'reference_id');
        // ^—— column name must match the foreign key in your migration
    }
}
