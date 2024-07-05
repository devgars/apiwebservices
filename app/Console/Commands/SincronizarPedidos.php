<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\MMTrack\ServicesController;
use Illuminate\Support\Facades\Log;

class SincronizarPedidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Sincronizar:Pedidos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command. 
     * 
     * @return int
     */
    public function handle()
    {

        $servicios = new ServicesController;

        Log::info('SINCRONIZAR PEDIDOS');
        //SINCRONIZAR PEDIDOS
        $servicios->SincronizarPedidosAs400();

        Log::info('SINCRONIZAR TRACKING PEDIDOS');
        //SINCRONIZAR TRACKING DE PEDIDOS
        $servicios->SincronizarPedidosTackingAs400();

    }
}
