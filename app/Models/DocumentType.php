<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $table = 'document_types';

    protected $fillable = [
        'code',
        'name',
        'description',
    ];


    // Если хотите, можно добавить связь на Document:
    public function documents()
{
    // Правильно: 'document_type_id' как foreign key в таблице documents
    return $this->hasMany(Document::class, 'document_type_id');
}


}
