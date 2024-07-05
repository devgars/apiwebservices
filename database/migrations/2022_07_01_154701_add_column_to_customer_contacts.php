<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToCustomerContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->bigInteger('identification_type_id')->nullable()->comment('Tipo de documento de identificación');
            $table->foreign('identification_type_id')->references('id')->on('gen_resource_details');
            $table->string('identification_number', 15)->nullable()->comment('Número de documento de identificación');
            $table->string('contact_address', 100)->nullable()->comment('Dirección de contacto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            //
        });
    }
}
