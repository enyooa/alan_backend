<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentType extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType   = 'string';
    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }
        });
    }
    protected $table = 'document_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'organization_id'
    ];


    // Если хотите, можно добавить связь на Document:
    public function documents()
{
    // Правильно: 'document_type_id' как foreign key в таблице documents
    return $this->hasMany(Document::class, 'document_type_id');
}


}
