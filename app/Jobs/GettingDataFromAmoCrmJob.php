<?php

namespace App\Jobs;

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\LeadModel;
use App\Services\AmoCrmApiService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class GettingDataFromAmoCrmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Базовый домен аккаунта AMO CRM.
     *
     * @var string|null
     */
    protected ?string $account_subdomain;

    /**
     * Долгосрочный токен интеграции AMO CRM.
     *
     * @var string|null
     */
    protected ?string $long_lived_access_token;

    /**
     * Идентификатор обрабатываемого лида.
     *
     * @var string|null
     */
    protected ?string $lead_id;

    /**
     * Данные заметки.
     *
     * @var array|null
     */
    protected ?array $note;

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
     * @param ?string $amoCrmAccountSubdomain - Базовый домен аккаунта AMO CRM.
     * @param ?string $amoCrmLongLivedAccessToken - Долгосрочный токен интеграции AMO CRM.
     * @param ?string $leadId - Id лида.
     * @param ?array $noteData - Данные заметки.
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(?string $amoCrmAccountSubdomain, ?string $amoCrmLongLivedAccessToken, ?string $leadId, ?array $noteData)
    {
        if (!isset($amoCrmAccountSubdomain))
            throw new InvalidArgumentException("Не задан параметр - домен аккаунта AMO CRM");
        if (empty($amoCrmLongLivedAccessToken))
            throw new InvalidArgumentException("Не задан параметр - долгосрочный токен");
        if (empty($leadId))
            throw new InvalidArgumentException("Не задан параметр - идентификатор лида");
        if (empty($noteData))
            throw new InvalidArgumentException("Не задан параметр - данные заметки");
        if (!isset($noteData['id']))
            throw new InvalidArgumentException("Не задан идентификатор заметки в заметке AMO CRM");

        $this->account_subdomain = $amoCrmAccountSubdomain;
        $this->long_lived_access_token = $amoCrmLongLivedAccessToken;
        $this->lead_id = $leadId;
        $this->note = $noteData;
        $this->note_id = $this->note['id'] ?? null;
        $this->payload_lead_note_cache_key = "payload_lead_{$this->lead_id}_note_{$this->note_id}";
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        $payloadLeadNote = array(
            'account_id' => $this->note['account_id'] ?? null,
            'lead' => null,
            'lead_id' => $this->lead_id,
            'lead_company' => null,
            'lead_company_id' => null,
            'lead_company_name' => '',
            'document_type_id' => $this->note['doc_type_id'],
            'document_number' => $this->note['doc_num'] ?? null,
            'document_date_act' => null,
            'document_date_period_act' => null,
            'document_payment_amount' => 0,
            'document_staff_act' => null,
            'purpose_payment' => '',
            'note' => $this->note,
            'note_id' => $this->note_id
        );

        $amoCrm = new AmoCrmApiService($this->account_subdomain, $this->long_lived_access_token);

        $leadInfo = $amoCrm->GetLeadInfo($this->lead_id);

        if (!($leadInfo instanceof LeadModel)) {
            Log::error("[GettingDataFromAmoCrmJob]: Не удалось получить объект лида по идентификатору: '$this->lead_id' & NoteId: '$this->note_id'");
            return;
        }

        $payloadLeadNote['lead'] = $leadInfo->toArray();
        $payloadLeadNote['lead_company_id'] = $leadInfo->getCompany()->getId();
        $companyInfo = $amoCrm->GetCompanyInfo($payloadLeadNote['lead_company_id']);
        if ($companyInfo instanceof CompanyModel) {
            $companyInfo->toArray();
            # Название компании
            $payloadLeadNote['lead_company_name'] = $companyInfo->getName();
            $payloadLeadNote['lead_company'] = $companyInfo->toArray();
        }

        # Бюджет сделки
        $payloadLeadNote['document_payment_amount'] = $leadInfo->getPrice();

        # Если по каким-то причинам не удалось получить бюджет
        if (is_null($payloadLeadNote['document_payment_amount']))
            $payloadLeadNote['document_payment_amount'] = 0;

        $fieldValues = $leadInfo->getCustomFieldsValues();
        if ($fieldValues instanceof CustomFieldsValuesCollection) {
            /**
             * @var SelectCustomFieldValuesModel $fieldValue
             */
            foreach ($fieldValues->getIterator() as $fieldValue) {
                switch ($fieldValue->getFieldId()) {
                    case 578632: # Дата Акта
                        $payloadLeadNote['document_date_act'] = $fieldValue->getValues()->first()->getValue();
                        break;
                    case 578634: # Сделка Акт/Период
                        $payloadLeadNote['document_date_period_act'] = $fieldValue->getValues()->first()->getValue();
                        break;
                    case 584218: # Штат (кол-во сотрудников) Акт
                        $payloadLeadNote['document_staff_act'] = $fieldValue->getValues()->first()->getValue();
                        break;
                }
            }
        }

        Cache::put($this->payload_lead_note_cache_key, $payloadLeadNote, now()->addMinutes(20));
    }
}
