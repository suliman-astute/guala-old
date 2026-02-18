<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportaDatiEsterni extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:importa-dati-esterni';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    /* public function handle()
    {
        require base_path('scripts/db_aligner.php');
        // Puoi loggare eventuali output...
        $this->info("db_aligner.php eseguito!");
    } */
    public function handle()
    {
        $phpPath = '"C:\\Program Files (x86)\\Plesk\\Additional\\PleskPHP83\\php.exe"';
        $scriptPath = base_path('scripts/db_aligner.php');
        $logPath = storage_path('logs/db_aligner_background.log');

        // Comando corretto per esecuzione in background su Windows
        $cmd = "start /B \"\" {$phpPath} {$scriptPath} > {$logPath} 2>&1";
        
        // Esecuzione senza attesa (non blocca Laravel)
        pclose(popen($cmd, 'r'));

        $this->info("Script avviato in background");
    }

}
