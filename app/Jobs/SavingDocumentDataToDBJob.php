<?php

namespace App\Jobs;

use App\Exceptions\InvalidCacheParamException;
use App\Models\AmoCrmCompany;
use App\Models\AmoCrmDocument;
use App\Models\AmoCrmLead;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SavingDocumentDataToDBJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Идентификатор обрабатываемого лида.
     *
     * @var string|null
     */
    protected ?string $lead_id;

    /**
     * Идентификатор обрабатываемой заметки.
     *
     * @var string|null
     */
    protected ?string $note_id;

    /**
     * Ключ кеширования данных.
     *
     * @var string
     */
    protected string $payload_lead_note_cache_key;

    /**
     * Create a new job instance.
     *
     * @param ?string $leadId - Id лида.
     * @param ?string $noteId - Id заметки.
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(?string $leadId, ?string $noteId)
    {
        if (empty($leadId))
            throw new InvalidArgumentException("Не задан параметр - идентификатор лида");
        if (empty($noteId))
            throw new InvalidArgumentException("Не задан параметр - идентификатор заметки");

        $this->lead_id = $leadId;
        $this->note_id = $noteId;
        $this->payload_lead_note_cache_key = "payload_lead_{$this->lead_id}_note_{$this->note_id}";
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws InvalidCacheParamException
     */
    public function handle()
    {
        $payloadLeadNote = Cache::get($this->payload_lead_note_cache_key);
        if (empty($payloadLeadNote))
            throw new InvalidCacheParamException("Не удалось получить данные заметки по LeadId: '$this->lead_id' & NoteId: '$this->note_id' из кэша");

        $hasException = false;
        $amoCrmAccountId = $payloadLeadNote['account_id'] ?? null;

        $documentTypeId = $payloadLeadNote['document_type_id'] ?? null;
        $documentNumber = $payloadLeadNote['document_number'] ?? null;
        $documentDateAct = $payloadLeadNote['document_date_act'] ?? null;
        $documentPaymentAmount = $payloadLeadNote['document_payment_amount'] ?? null;
        $purposeOfPayment = $payloadLeadNote['purpose_of_payment'] ?? null;

        $leadCompanyId = $payloadLeadNote['lead_company_id'] ?? null;
        $leadCompanyName = $payloadLeadNote['lead_company_name'] ?? '';

        $companyCacheKey = "amo_crm_company_id_$leadCompanyId";
        $leadCacheKey = "amo_crm_lead_id_$this->lead_id";

        if (is_null($amoCrmAccountId))
            throw new InvalidCacheParamException("Не задан идентификатор аккаунта AMO CRM в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($documentTypeId))
            throw new InvalidCacheParamException("Не задан идентификатор типа документа в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($documentNumber))
            throw new InvalidCacheParamException("Не задан номер созданного документа в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($documentDateAct))
            throw new InvalidCacheParamException("Не задана 'Дата акта' в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($documentPaymentAmount))
            throw new InvalidCacheParamException("Не удалось получить 'Бюджет сделки' в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($purposeOfPayment))
            throw new InvalidCacheParamException("Не задано 'Название платежа' в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($leadCompanyId))
            throw new InvalidCacheParamException("Не задан идентификатор компании лида в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($leadCompanyName))
            throw new InvalidCacheParamException("Не удалось получить название компании лида в объекте кэша", 0, null, $payloadLeadNote);


        //region - Создаём запись компании в базе данных

        $companyDbId = null;
        $companyValuesPayload = array(
            'amo_crm_company_id' => $leadCompanyId,
            'amo_crm_company_name' => $leadCompanyName
        );
        try {
            $companyDbId = Cache::get($companyCacheKey);
            if (empty($companyDbId)) {
                $company = AmoCrmCompany::updateOrCreate(array(
                    'amo_crm_company_id' => $leadCompanyId,
                ), $companyValuesPayload);

                if (!empty($company)) {
                    $companyDbId = $company->id;
                    Cache::put($companyCacheKey, $companyDbId, now()->addHours(6));
                }
            }
        } catch (Exception $ex) {
            $hasException = true;
            Log::error("[SavingDocumentDataToDBJob]: Не удалось создать запись компании в базе данных по LeadId: '$this->lead_id' & NoteId: '$this->note_id', ошибка: " . $ex->getMessage(), [
                'payload' => $companyValuesPayload
            ]);
        }

        if ($hasException || empty($companyDbId))
            return;

        //endregion


        //region - Создаём запись сделки в базе данных

        $leadDbId = null;
        $leadValuesPayload = array(
            'amo_crm_lead_id' => $this->lead_id,
            'amo_crm_companies_id' => $companyDbId
        );
        try {
            $leadDbId = Cache::get($leadCacheKey);
            if (empty($leadDbId)) {
                $lead = AmoCrmLead::updateOrCreate(array(
                    'amo_crm_lead_id' => $this->lead_id,
                ), $leadValuesPayload);

                if (!empty($lead)) {
                    $leadDbId = $lead->id;
                    Cache::put($leadCacheKey, $leadDbId, now()->addHours(6));
                }
            }
        } catch (Exception $ex) {
            $hasException = true;
            Log::error("[SavingDocumentDataToDBJob]: Не удалось создать запись лида в базе данных по LeadId: '$this->lead_id' & NoteId: '$this->note_id', ошибка: " . $ex->getMessage(), [
                'payload' => $leadValuesPayload
            ]);
        }

        if ($hasException || empty($leadDbId))
            return;

        //endregion


        //region - Создаём запись документа в базе данных

        $documentValuesPayload = array(
            'amo_crm_document_types_id' => $documentTypeId,
            'amo_crm_leads_id' => $leadDbId,
            'amo_crm_account_id' => $amoCrmAccountId,
            'purpose_of_payment' => $purposeOfPayment,
            'document_number' => $documentNumber,
            'document_date' => $documentDateAct,
            'payment_amount' => $documentPaymentAmount
        );
        try {
            AmoCrmDocument::updateOrCreate(array(
                'amo_crm_document_types_id' => $documentTypeId,
                'amo_crm_leads_id' => $leadDbId,
                'document_number' => $documentNumber
            ), $documentValuesPayload);

        } catch (Exception $ex) {
            Log::error("[SavingDocumentDataToDBJob]: Не удалось создать запись документа в базе данных по LeadId: '$this->lead_id' & NoteId: '$this->note_id', ошибка " . $ex->getMessage(), [
                'payload' => $documentValuesPayload
            ]);
        }

        //endregion
    }
}
