<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToPartPartDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('part_part_details', function (Blueprint $table) {
            $table->decimal('total_stock', 15, 2)->default(0)->comment('Stock total');
            $table->decimal('replacement_cost', 15, 2)->default(0)->comment('Costo de reposición');
            $table->decimal('min_price_factor', 8, 2)->default(0)->comment('Factor de precio mínimo');
            $table->decimal('max_price_factor', 8, 2)->default(0)->comment('Factor de precio máximo');
            $table->decimal('min_profit_rate', 8, 2)->default(0)->comment('Porcentaje de utilidad mínima');
            $table->decimal('max_profit_rate', 8, 2)->default(0)->comment('Porcentaje de utilidad máxima');

            $table->index('total_stock');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('part_part_detail', function (Blueprint $table) {
            //
        });
    }
}
