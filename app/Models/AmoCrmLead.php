<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Модель сделки из AMO CRM.
 *
 * @property int $id
 * @property int $amo_crm_companies_id
 * @property int $amo_crm_lead_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read AmoCrmCompany $amo_crm_company
 * @method static Builder|AmoCrmLead create(array $value)
 * @method static Builder|AmoCrmLead insert(array $value)
 * @method static Builder|AmoCrmLead updateOrCreate(array $attributes, array $values = array())
 */
class AmoCrmLead extends Model
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
        'amo_crm_companies_id',
        'amo_crm_lead_id'
    ];

    //endregion


    //region - Public methods

    /**
     * Returns the company relationship.
     *
     * @return BelongsTo
     */
    public function amo_crm_company(): BelongsTo
    {
        return $this->belongsTo(AmoCrmCompany::class, 'amo_crm_companies_id', 'id');
    }

    //endregion
}
