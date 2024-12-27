<?php

namespace Database\Seeders;

use App\Models\AmoCrmDocumentType;
use Illuminate\Database\Seeder;

class AmoCrmDocumentTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        # Универсальный передаточный документ (УПД).
        $docType = new AmoCrmDocumentType(array(
            'id' => 1,
            'document_type_name' => 'УПД'
        ));
        $docType->save();



        # Счет-фактура.
        $docType = new AmoCrmDocumentType(array(
            'id' => 2,
            'document_type_name' => 'Счёт-фактура'
        ));
        $docType->save();
    }
}
