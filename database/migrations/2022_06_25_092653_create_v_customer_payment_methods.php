<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
#use Illuminate\Support\Facades\Schema;
use Staudenmeir\LaravelMigrationViews\Facades\Schema;

class CreateVCustomerPaymentMethods extends Migration
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
        c.client_class,
        cpm.days_to_pay,
        fm.code AS way_to_pay_code,
        fm.name AS way_to_pay_name,
        cp.code AS payment_condition_code,
        cp.name AS payment_condition_name,
        mp.code AS payment_modality_code,
        mp.name AS payment_modality_name
       FROM customers c
         JOIN customer_payment_methods cpm ON c.id = cpm.customer_id
         JOIN gen_resource_details fm ON cpm.payment_method_id = fm.id
         JOIN gen_resource_details cp ON cpm.payment_method_id = cp.id
         JOIN gen_resource_details mp ON cpm.payment_method_id = mp.id;';

        Schema::createView('v_customer_payment_methods', $query);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropView('v_customer_payment_methods');
    }
}
