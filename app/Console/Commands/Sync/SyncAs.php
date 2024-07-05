<?php

namespace App\Console\Commands\Sync;

use Illuminate\Console\Command;
use App\Http\Controllers\Sync\SyncController;
use Illuminate\Support\Facades\Log;

class SyncAs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:as400';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar AS400 - BD Intermedia';

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
        $sincronizacion = new SyncController;

        Log::info('PROCESO DE SINCRONIZACIÓN - ' . date("Y-m-d H:i:s"));

        $sincronizacion->sync();
    }
}
