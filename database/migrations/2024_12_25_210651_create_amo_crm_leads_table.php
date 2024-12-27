<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmoCrmLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amo_crm_leads', function (Blueprint $table) {
            $table->integerIncrements('id')
                ->comment('Id');

            $table->unsignedInteger('amo_crm_companies_id')
                ->comment('Id компании');

            $table->integer('amo_crm_lead_id')
                ->unique('amo_crm_lead_id_idx')
                ->comment('Id лида в AmoCRM');

            $table->timestamps();

            $table->foreign('amo_crm_companies_id')
                ->references('id')
                ->on('amo_crm_companies')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('amo_crm_leads');
    }
}
