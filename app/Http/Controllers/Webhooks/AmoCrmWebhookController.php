<?php

namespace App\Http\Controllers\Webhooks;

use App\Exceptions\InvalidCacheParamException;
use App\Http\Controllers\Controller;
use App\Jobs\GettingDataFromAmoCrmJob;
use App\Jobs\SavingDocumentDataToDBJob;
use App\Jobs\TransferringDocumentDataToGoogleSheetJob;
use App\Services\AmoCrmLeadNotesFilterService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Контроллер с обработчиками вебхуков.
 */
class AmoCrmWebhookController extends Controller
{
    /**
     * Обработчик вебхука событий и примечаний сделок в АМО CRM.
     *
     * @param Request $request - Объект HTTP запроса.
     * @return JsonResponse
     */
    public function handlePipelineOfEventsAndNotesForLeads(Request $request): JsonResponse
    {
        $account = $request->input('account');
        $leads = $request->input('leads');

        if (empty($account) || !is_array($account) || !isset($account['subdomain'])) {
            return response()->json(['message' => 'Invalid account request data']);
        }

        if (empty($leads) || !is_array($leads)) {
            return response()->json(['message' => 'Invalid leads request data']);
        }

        # Фильтруем заметки
        $filterLeadNotesService = new AmoCrmLeadNotesFilterService();
        $filteredNotes = $filterLeadNotesService->filterNotesInLeads($leads);

        # Если список отфильтрованных заметок не пустой
        if (!empty($filteredNotes)) {

            $debug = Config::get('app.debug');
            $amoCrmLongLivedAccessToken = Config::get('amocrm.long_lived_access_token');
            if (empty($amoCrmLongLivedAccessToken)) {
                return response()->json(['message' => 'An internal error. Incorrect settings for the AMO CRM token']);
            }
            $googleAppsScriptWebhookUri = Config::get('google.apps_script_webhook_uri');
            if (empty($googleAppsScriptWebhookUri)) {
                return response()->json(['message' => 'An internal error. Google Apps Script Webhook URI settings are not set']);
            }

            # Постановка задач в очередь по каждой заметке отдельно
            foreach ($filteredNotes as $filteredNote) {
                try {
                    $amoCrmAccountSubdomain = $account['subdomain'] . '.amocrm.ru';
                    $filteredNote['account_subdomain'] = $amoCrmAccountSubdomain;

                    # Log::info("Запускаем задачу с LeadId: {$filteredNote['element_id']} NoteId: {$filteredNote['id']}");

                    Bus::chain([
                        new GettingDataFromAmoCrmJob($amoCrmAccountSubdomain, $amoCrmLongLivedAccessToken, $filteredNote['element_id'], $filteredNote),
                        new TransferringDocumentDataToGoogleSheetJob($debug, $filteredNote['element_id'], $filteredNote['id'], $googleAppsScriptWebhookUri),
                        new SavingDocumentDataToDBJob($filteredNote['element_id'], $filteredNote['id'])
                    ])->catch(function (Throwable $e) {
                        if ($e instanceof InvalidCacheParamException) {
                            Log::error("Не удалось обработать цепочку задач, ошибка: " . $e->getMessage(), [ $e->getPayloadLeadNote() ]);
                        } else {
                            Log::error("Не удалось обработать цепочку задач, ошибка: " . $e->getMessage());
                        }
                    })->onQueue('processing-lead-note')->dispatch();
                } catch (Exception $ex) {
                    Log::error('Не удалось запустить цепочку задач, ошибка: ' . $ex->getMessage(), [ 'note' => $filteredNote ]);
                }
            } // foreach ($filteredNotes)

        }

        return response()->json(['message' => 'Webhook handled successfully']);
    }
}
