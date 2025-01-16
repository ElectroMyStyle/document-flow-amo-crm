<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnDocumentDateInAmoCrmDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amo_crm_documents', function (Blueprint $table) {
            $table->date('document_date')
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('amo_crm_documents', function (Blueprint $table) {
            $table->date('document_date')
                ->nullable(false)
                ->change();
        });
    }
}
