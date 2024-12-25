<?php

use App\Http\Controllers\Webhooks\AmoCrmWebhookController;
use Illuminate\Support\Facades\Route;

Route::controller(AmoCrmWebhookController::class)->prefix('v1')->group(function() {

    Route::post('pipeline-of-events-and-notes-for-leads', 'handlePipelineOfEventsAndNotesForLeads');

});
