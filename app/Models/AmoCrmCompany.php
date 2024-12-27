<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Модель компании из AMO CRM.
 *
 * @property int $id
 * @property int $amo_crm_company_id
 * @property string $amo_crm_company_name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @method static Builder|AmoCrmCompany create(array $value)
 * @method static Builder|AmoCrmCompany insert(array $value)
 * @method static Builder|AmoCrmCompany updateOrCreate(array $attributes, array $values = array())
 */
class AmoCrmCompany extends Model
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
        'amo_crm_company_id',
        'amo_crm_company_name'
    ];

    //endregion
}
