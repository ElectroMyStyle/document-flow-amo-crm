<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json(['message' => 'Webhook handled successfully']);
    }
}
