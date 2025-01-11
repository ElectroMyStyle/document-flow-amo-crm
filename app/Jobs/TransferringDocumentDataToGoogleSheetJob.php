<?php

namespace App\Jobs;

use App\Exceptions\InvalidCacheParamException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class TransferringDocumentDataToGoogleSheetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * True, если включен дебаг режим приложения.
     * @var bool
     */
    protected bool $debug;

    /**
     * Ссылка для выполнения запросов для записи данных в таблицу.
     *
     * @var string|null
     */
    protected ?string $google_apps_script_webhook_uri;

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
     * @param bool $debug - Debug mode.
     * @param ?string $leadId - Id лида.
     * @param ?string $noteId - Id заметки.
     * @param ?string $googleAppsScriptWebhookUri - Ссылка для выполнения запросов для записи данных в таблицу.
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(bool $debug, ?string $leadId, ?string $noteId, ?string $googleAppsScriptWebhookUri)
    {
        if (empty($leadId))
            throw new InvalidArgumentException("Не задан параметр - идентификатор лида");
        if (empty($noteId))
            throw new InvalidArgumentException("Не задан параметр - идентификатор заметки");
        if (empty($googleAppsScriptWebhookUri))
            throw new InvalidArgumentException("Не задан параметр - webhook ссылка Google Apps Script");

        $this->debug = $debug;
        $this->google_apps_script_webhook_uri = $googleAppsScriptWebhookUri;
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

        $documentDateAct = $payloadLeadNote['document_date_act'] ?? null;
        $documentDatePeriodAct = $payloadLeadNote['document_date_period_act'] ?? null;
        $documentNumber = $payloadLeadNote['document_number'] ?? null;
        $documentPaymentAmount = $payloadLeadNote['document_payment_amount'] ?? null;
        $documentStaffAct = $payloadLeadNote['document_staff_act'] ?? null;
        $documentTypeId = $payloadLeadNote['document_type_id'] ?? null;
        $leadCompanyName = $payloadLeadNote['lead_company_name'] ?? null;

        if (is_null($documentNumber))
            throw new InvalidCacheParamException("Не задан 'Номер созданного документа' в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($documentDateAct))
            throw new InvalidCacheParamException("Не задана 'Дата акта' в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($documentDatePeriodAct))
            throw new InvalidCacheParamException("Не задан период 'Акт/Период' в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($documentPaymentAmount))
            throw new InvalidCacheParamException("Не удалось получить 'Бюджет сделки' в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($documentStaffAct))
            throw new InvalidCacheParamException("Не удалось получить 'Штат/Акт' в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($documentTypeId))
            throw new InvalidCacheParamException("Не удалось получить идентификатор типа документа в объекте кэша", 0, null, $payloadLeadNote);

        if (is_null($leadCompanyName))
            throw new InvalidCacheParamException("Не удалось получить название компании лида в объекте кэша", 0, null, $payloadLeadNote);

        $purposeOfPayment = sprintf("Аутсорсинг охраны труда (%s) Штат до %s чел.", $documentDatePeriodAct, $documentStaffAct);
        $payloadLeadNote['purpose_of_payment'] = $purposeOfPayment;
        try {
            $httpClient = new Client([
                'debug' => $this->debug
            ]);

            $requestOptions = [
                'form_params' => [
                    # Назначение платежа
                    'purpose_of_payment' => $purposeOfPayment,
                    # Номер платежа (документа)
                    'document_number' => $documentNumber,
                    # Дата платежа
                    'document_date_act' => $documentDateAct,
                    # Сумма сделки (платежа)
                    'document_payment_amount' => $documentPaymentAmount,
                    # Название компании
                    'company_name' => $leadCompanyName,
                    # Тип документа (внутренний идентификатор в Laravel)
                    'document_type_id' => $documentTypeId
                ]
            ];

            $attemptCount = 1;
            do {
                if ($attemptCount > 1)
                    sleep(5);

                $attemptCount++;

                try {
                    // TODO: Сделать защиту от дурака через кэш исключить повторный запрос?
                    $resp = $httpClient->request('POST', $this->google_apps_script_webhook_uri, $requestOptions);

                    # Если вернулся код 200, значит данные успешно отправлены в Google Apps Script
                    if ($resp->getStatusCode() == 200) {
                        Log::info("[TransferringDocumentDataToGoogleSheetJob]: Данные успешно отправлены в Google Apps Script по LeadId: '$this->lead_id' & NoteId: '$this->note_id'");
                        break;
                    } else {
                        Log::error("[TransferringDocumentDataToGoogleSheetJob]: Не удалось отправить данные в Google Apps Script по LeadId: '$this->lead_id' & NoteId: '$this->note_id'. Data: ", [
                            $requestOptions
                        ]);
                    }
                } catch (Exception $ex) {
                    Log::error("[TransferringDocumentDataToGoogleSheetJob]: Не удалось отправить запрос записи данных в Google Sheet по LeadId: '$this->lead_id' & NoteId: '$this->note_id', попытка #$attemptCount, ошибка: " . $ex->getMessage());
                }
            } while ($attemptCount < 4); # 3 Попытки

        } catch (GuzzleException $ex) {
            Log::error("[TransferringDocumentDataToGoogleSheetJob]: Не удалось записать данные в Google Sheet по LeadId: '$this->lead_id' & NoteId: '$this->note_id', произошла сетевая ошибка: " . $ex->getMessage());
        } catch (Exception $ex) {
            Log::error("[TransferringDocumentDataToGoogleSheetJob]: Не удалось записать данные в Google Sheet по LeadId: '$this->lead_id' & NoteId: '$this->note_id', ошибка: " . $ex->getMessage());
        }

        Cache::put($this->payload_lead_note_cache_key, $payloadLeadNote, now()->addMinutes(20));
    }
}
