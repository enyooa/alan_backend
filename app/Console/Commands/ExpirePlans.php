<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// app/Console/Commands/ExpirePlans.php
class ExpirePlans extends Command
{
    protected $signature = 'plans:expire';
    protected $description = 'Deactivate subscriptions whose ends_at < now';

    public function handle()
    {
        $expired = DB::table('organization_plan')
            ->whereNotNull('ends_at')
            ->where('ends_at','<',now())
            ->delete();                       // or move to history table

        $this->info("Expired rows deleted: ".$expired);
        return 0;
    }
}
