<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
#use Illuminate\Support\Facades\Schema;
use Staudenmeir\LaravelMigrationViews\Facades\Schema;

class CreateVCustomers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $query = 'SELECT c.id AS customer_id,
        c.code AS customer_code,
        tp.code AS company_type_code,
        tp.name AS company_type_name,
        td.code AS document_type_code,
        td.name AS document_type_name,
        c.document_number,
        c.name_social_reason,
        c.economic_group_id,
        c.client_class,
        c.max_credit_limit,
        c.reg_status,
        pais.code AS country_code,
        pais.name AS country_name
       FROM customers c
         JOIN gen_resource_details tp ON c.company_type_id = tp.id
         JOIN gen_resource_details td ON c.document_type_id = td.id
         JOIN ubigeos pais ON c.country_id = pais.id;';

        Schema::createView('v_customers', $query);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropView('v_customers');
    }
}
