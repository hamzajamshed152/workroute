<?php

namespace App\Console\Commands;

use App\Domain\Tradie\Models\Tradie;
use Illuminate\Console\Command;

class ResetMonthlyUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-monthly-usage';

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
        Tradie::chunk(100, function ($tradies) {
            foreach ($tradies as $tradie) {
                $tradie->resetMonthlyUsage();
            }
        });

        $this->info('Monthly AI usage reset complete.');
    }
}


// Schedule::command('usage:reset')->monthlyOn(1, '00:00');
