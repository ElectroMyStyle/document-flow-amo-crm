<?php

namespace App\Jobs;

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\LeadModel;
use App\Services\AmoCrmApiService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProcessingLeadNoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Базовый домен аккаунта AMO CRM.
     *
     * @var string|null
     */
    protected ?string $account_subdomain;

    /**
     * True, если включен дебаг режим приложения.
     * @var bool|null
     */
    protected ?bool $debug;

    /**
     * Ссылка для выполнения запросов для записи данных в таблицу.
     *
     * @var string|null
     */
    protected ?string $google_apps_script_webhook_uri;

    /**
     * Долгосрочный токен интеграции AMO CRM.
     *
     * @var string|null
     */
    protected ?string $long_lived_access_token;

    /**
     * Данные заметки.
     *
     * @var array|null
     */
    protected ?array $note;

    /**
     * Create a new job instance.
     *
     * @param ?bool $debug - Debug mode.
     * @param ?string $amoCrmLongLivedAccessToken - Долгосрочный токен интеграции AMO CRM.
     * @param ?array $noteData - Данные заметки.
     * @param ?string $googleAppsScriptWebhookUri - Ссылка для выполнения запросов для записи данных в таблицу.
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(?bool $debug, ?string $amoCrmLongLivedAccessToken, ?array $noteData, ?string $googleAppsScriptWebhookUri)
    {
        if (empty($amoCrmLongLivedAccessToken))
            throw new InvalidArgumentException("Не задан параметр - долгосрочный токен");
        if (empty($noteData))
            throw new InvalidArgumentException("Не задан параметр - данные заметки");
        if (!isset($noteData['account_subdomain']))
            throw new InvalidArgumentException("Не задан домен аккаунта AMO CRM");
        if (!isset($noteData['element_id']))
            throw new InvalidArgumentException("Не задан идентификатор лида в заметке AMO CRM");
        if (!isset($noteData['doc_num']))
            throw new InvalidArgumentException("Не задан номер созданного документа в заметке AMO CRM");
        if (empty($googleAppsScriptWebhookUri))
            throw new InvalidArgumentException("Не задан параметр - webhook ссылка Google Apps Script");

        $this->account_subdomain = $noteData['account_subdomain'];
        $this->debug = $debug;
        $this->long_lived_access_token = $amoCrmLongLivedAccessToken;
        $this->note = $noteData;
        $this->google_apps_script_webhook_uri = $googleAppsScriptWebhookUri;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $hasException = false;
        $leadInfo = null;
        $leadId = $this->note['element_id'] ?? null;
        $leadCompanyName = null;
        $leadDocPrice = null;
        $leadDocDateAct = null;
        $leadDocDatePeriodAct = null;
        $leadDocStaffAct = null;
        $noteId = $this->note['id'] ?? null;

        try {
            $amoCrm = new AmoCrmApiService($this->account_subdomain, $this->long_lived_access_token);

            $leadInfo = $amoCrm->GetLeadInfo($leadId);

            if ($leadInfo instanceof LeadModel) {
                $companyId = $leadInfo->getCompany()->getId();
                $companyInfo = $amoCrm->GetCompanyInfo($companyId);
                if ($companyInfo instanceof CompanyModel) {
                    # Название компании
                    $leadCompanyName = $companyInfo->getName();
                }

                # Бюджет сделки
                $leadDocPrice = $leadInfo->getPrice();

                $fieldValues = $leadInfo->getCustomFieldsValues();
                if ($fieldValues instanceof CustomFieldsValuesCollection) {
                    /**
                     * @var SelectCustomFieldValuesModel $fieldValue
                     */
                    foreach ($fieldValues->getIterator() as $fieldValue) {
                        switch ($fieldValue->getFieldId()) {
                            case 578632: # Дата Акта
                                $leadDocDateAct = $fieldValue->getValues()->first()->getValue();
                                break;
                            case 578634: # Сделка Акт/Период
                                $leadDocDatePeriodAct = $fieldValue->getValues()->first()->getValue();
                                break;
                            case 584218: # Штат (кол-во сотрудников) Акт
                                $leadDocStaffAct = $fieldValue->getValues()->first()->getValue();
                                break;
                        }
                    }
                }
            }
        } catch (\AmoCRM\Exceptions\InvalidArgumentException $ex) {
            $hasException = true;
            Log::error("[ProcessingLeadNoteJob]: Не удалось создать AmoCrmApiService с LeadId: '$leadId' & NoteId: '$noteId', ошибка: " . $ex->getMessage());
        } catch (Exception $ex) {
            $hasException = true;
            Log::error("[ProcessingLeadNoteJob]: Не удалось получить данные по LeadId: '$leadId' & NoteId: '$noteId', ошибка: " . $ex->getMessage());
        }

        # Если произошло исключение, завершаем работу
        if ($hasException || empty($leadInfo))
            return;

        if (is_null($leadDocPrice)) {
            Log::error("[ProcessingLeadNoteJob]: Не удалось получить параметр 'Бюджет сделки' по LeadId: '$leadId' & NoteId: '$noteId'");
            return;
        }

        if (is_null($leadDocDateAct)) {
            Log::error("[ProcessingLeadNoteJob]: Не удалось получить 'Дату акта' по LeadId: '$leadId' & NoteId: '$noteId'");
            return;
        }

        if (is_null($leadDocDatePeriodAct)) {
            Log::error("[ProcessingLeadNoteJob]: Не удалось получить 'Акт/Период' по LeadId: '$leadId' & NoteId: '$noteId'");
            return;
        }

        if (is_null($leadCompanyName)) {
            Log::error("[ProcessingLeadNoteJob]: Не удалось получить название компании по LeadId: '$leadId' & NoteId: '$noteId'");
            return;
        }

        if (is_null($leadDocStaffAct)) {
            Log::error("[ProcessingLeadNoteJob]: Не удалось получить 'Штат/Акт' по LeadId: '$leadId' & NoteId: '$noteId'");
            return;
        }

        try {
            $purposePayment = sprintf("Аутсорсинг охраны труда (%s) Штат до %s чел.", $leadDocDatePeriodAct, $leadDocStaffAct);

            $httpClient = new Client([
                'debug' => $this->debug
            ]);

            $resp = $httpClient->request('POST', $this->google_apps_script_webhook_uri, [
                # Назначение платежа
                'purpose_of_payment' => $purposePayment,
                # Номер платежа (документа)
                'payment_number' => $this->note['doc_num'],
                # Дата платежа
                'payment_date' => $leadDocDateAct,
                # Сумма сделки (платежа)
                'payment_amount' => $leadDocPrice,
                # Название компании
                'company_name' => $leadCompanyName,
            ]);

            // Если вернулся код 200, значит данные успешно отправлены в Google Apps Script
            if ($resp->getStatusCode() == 200) {
                // TODO: Сделать обновление об успешной передачи данных и работе воркера ?
            }

        } catch (GuzzleException $ex) {
            Log::error("[ProcessingLeadNoteJob]: Не удалось записать данные в Google Sheet по LeadId: '$leadId' & NoteId: '$noteId', произошла сетевая ошибка: " . $ex->getMessage());
        } catch (Exception $ex) {
            Log::error("[ProcessingLeadNoteJob]: Не удалось записать данные в Google Sheet по LeadId: '$leadId' & NoteId: '$noteId', ошибка " . $ex->getMessage());
        }
    }
}
