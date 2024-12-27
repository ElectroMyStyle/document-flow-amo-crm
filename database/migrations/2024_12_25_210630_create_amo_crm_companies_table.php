<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmoCrmCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amo_crm_companies', function (Blueprint $table) {
            $table->integerIncrements('id')
                ->comment('Id');

            $table->bigInteger('amo_crm_company_id')
                ->unique('amo_crm_company_id_idx')
                ->comment('Id компании в AmoCRM');

            $table->text('amo_crm_company_name')
                ->comment('Название компании в AmoCRM');

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
        Schema::dropIfExists('amo_crm_companies');
    }
}
