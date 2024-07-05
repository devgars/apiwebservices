<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnTechnicalSpecToPartPartDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('part_part_details', function (Blueprint $table) {
            $table->string('technical_spec', 100)->nullable()->comment('Especificaciones tecnicas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('part_part_details', function (Blueprint $table) {
            //
        });
    }
}
