<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClienteSaldosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cliente_saldos', function (Blueprint $table) {
            $table->id();
            $table->string('ABCODCLI', 6)->comment('Código Cliente');
            $table->string('AKTIPIDE', 2)->comment('Tipo Identificación 01 -> DNI, 02 -> RUC');
            $table->string('NUMERO_IDENTIFICACION', 20)->comment('Número de Identificación cliente');
            $table->string('AKRAZSOC', 80)->comment('Nombre o Razón Social');
            $table->string('AKNOMCOM', 20)->nullable()->comment('Nombre Comercial');

            $table->string('ABCODCIA', 2)->comment('Código de Compañia');
            $table->string('ABCODSUC', 2)->comment('Codigo de Sucursal');
            $table->string('ABTIPDOC', 2)->comment('Tipo de comprobante, Factura, Boleta');
            $table->bigInteger('ABNRODOC')->comment('Número de comprobante');
            $table->bigInteger('ABFECTCM')->comment('Fecha de tipo cambio');
            $table->bigInteger('ABFECEMI')->comment('Fecha de emisión');
            $table->bigInteger('ABFECVCT')->comment('Fecha de vencimiento');
            $table->string('ABCODMON', 2)->comment('Código de Moneda');
            $table->decimal('ABIMPCCC', 15, 2)->comment('Total Importe');
            $table->decimal('ABIMPSLD', 15, 2)->comment('Total saldo');
            $table->string('ABFRMPAG', 2)->comment('Forma de pago');
            $table->string('ABMODPAG', 2)->comment('Modalidad de pago');
            $table->smallInteger('ABCNDPAG')->comment('Condición de pago');

            $table->string('CBNROSER', 3)->nullable()->comment('Número Serie');
            $table->string('CBNROCOR', 7)->nullable()->comment('Número Correlativo');
            $table->string('ABSTS', 1)->nullable()->comment('Estado Registro AS400, A -> ACTIVO');
            $table->smallInteger('RUPDATE')->default(0)->comment('Indica si el registro ha sido actualizado');
            $table->boolean("mostrar")->default(TRUE)->comment('Indica si se muestra o no el registro');

            $table->datetime('fecha_hora_actualizacion_db2')->nullable()->comment('Fecha-hora Actualización de registro en DB2');


            $table->datetime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->nullable();

            $table->unique(['ABCODCIA', 'ABCODSUC', 'ABCODCLI', 'ABTIPDOC', 'ABNRODOC']); // FALTA ABFECEMI
            $table->index('ABCODCIA');
            $table->index('ABCODSUC');
            $table->index('ABCODCLI');
            $table->index('ABTIPDOC');
            $table->index('ABNRODOC');
            $table->index('ABFECEMI');
            $table->index('NUMERO_IDENTIFICACION');
            $table->index('ABSTS');
            $table->index('mostrar');
            $table->index('fecha_hora_actualizacion_db2');


            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cliente_saldos');
    }
}
