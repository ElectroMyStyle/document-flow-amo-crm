<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Модель созданного УПД документа из AMO CRM.
 *
 * @property int $id
 * @property int $amo_crm_document_types_id
 * @property int $amo_crm_leads_id
 * @property int $amo_crm_account_id
 * @property string $purpose_of_payment
 * @property int $document_number
 * @property Carbon|null $document_date
 * @property int $payment_amount
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read HasOne|AmoCrmDocumentType $amo_crm_document_type
 * @property-read BelongsTo|AmoCrmLead $amo_crm_lead
 * @method static Builder|AmoCrmDocument create(array $value)
 * @method static Builder|AmoCrmDocument insert(array $value)
 * @method static Builder|AmoCrmDocument updateOrCreate(array $attributes, array $values = array())
 */
class AmoCrmDocument extends Model
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
        'amo_crm_document_types_id',
        'amo_crm_leads_id',
        'amo_crm_account_id',
        'purpose_of_payment',
        'document_number',
        'document_date',
        'payment_amount',
    ];

    /**
     * @var string[]
     */
    protected $dates = [ 'document_date' ];

    //endregion


    //region - Public methods

    /**
     * Returns the document type relationship.
     *
     * @return HasOne
     */
    public function amo_crm_document_type(): HasOne
    {
        return $this->hasOne(AmoCrmDocumentType::class, 'id', 'amo_crm_document_types_id');
    }

    /**
     * Returns the lead relationship.
     *
     * @return BelongsTo
     */
    public function amo_crm_lead(): BelongsTo
    {
        return $this->belongsTo(AmoCrmLead::class, 'amo_crm_leads_id', 'id');
    }

    //endregion
}
