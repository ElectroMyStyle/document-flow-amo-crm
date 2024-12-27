<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель типа документа.
 *
 * @property int $id
 * @property string $document_type_name
 * @method static Builder|AmoCrmDocumentType create(array $value)
 * @method static Builder|AmoCrmDocumentType insert(array $value)
 * @method static Builder|AmoCrmDocumentType updateOrCreate(array $attributes, array $values = array())
 */
class AmoCrmDocumentType extends Model
{
    use HasFactory;

    //region - Fields

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'id',
        'document_type_name',
    ];

    //endregion
}
