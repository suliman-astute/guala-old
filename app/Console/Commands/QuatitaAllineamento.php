<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class QuatitaAllineamento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:quatita-allineamento';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
     public function handle()
    {
        $phpPath = '"C:\\Program Files (x86)\\Plesk\\Additional\\PleskPHP83\\php.exe"';
        $scriptPath = base_path('scripts/assemblaggio_aligner.php');
        $logPath = storage_path('logs/assemblaggio_aligner.log');

        $cmd = "start /B \"\" {$phpPath} {$scriptPath} > {$logPath} 2>&1";
        pclose(popen($cmd, 'r'));

        $this->info("Secondo script avviato in background");
    }
}
