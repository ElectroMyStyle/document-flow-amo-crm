<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmoCrmDocumentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amo_crm_document_types', function (Blueprint $table) {
            $table->integerIncrements('id')
                ->comment('Id');

            $table->text('document_type_name')
                ->comment('Название типа документа');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('amo_crm_document_types');
    }
}
