<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmoCrmDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amo_crm_documents', function (Blueprint $table) {
            $table->id()
                ->comment('Id');

            ;$table->unsignedInteger('amo_crm_document_types_id')
                ->comment('Id типа документа');

            $table->unsignedInteger('amo_crm_leads_id')
                ->comment('Id лида');

            $table->integer('amo_crm_account_id')
                ->comment('Id пользователя в AmoCRM');

            $table->text('purpose_of_payment')
                ->comment('Назначение платежа');

            $table->integer('document_number')
                ->comment('Номер документа');

            $table->date('document_date')
                ->comment('Дата документа');

            $table->integer('payment_amount')
                ->comment('Сумма платежа');

            $table->timestamps();

            $table->foreign('amo_crm_leads_id')
                ->references('id')
                ->on('amo_crm_leads')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unique(['amo_crm_document_types_id', 'amo_crm_leads_id', 'document_number'], 'document_types_id_leads_id_document_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('amo_crm_documents');
    }
}
