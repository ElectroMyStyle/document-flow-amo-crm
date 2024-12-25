<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessingLeadNoteJob;
use App\Services\AmoCrmLeadNotesFilterService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

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
                    $filteredNote['account_subdomain'] = $account['subdomain'] . '.amocrm.ru';

                    # Log::info("Запускаем задачу с LeadId: {$filteredNote['element_id']} NoteId: {$filteredNote['id']}");

                    ProcessingLeadNoteJob::dispatch($debug, $amoCrmLongLivedAccessToken, $filteredNote, $googleAppsScriptWebhookUri)
                        ->onQueue('processing-lead-note');
                } catch (Exception $ex) {
                    Log::error('Не удалось запусти задачу ProcessingLeadNoteJob, ошибка: ' . $ex->getMessage(), [ 'note' => $filteredNote ]);
                }
            } // foreach ($filteredNotes)

        }

        return response()->json(['message' => 'Webhook handled successfully']);
    }
}
