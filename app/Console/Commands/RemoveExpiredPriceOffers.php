<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PriceOfferItem;
use Carbon\Carbon;

class RemoveExpiredPriceOffers extends Command
{
    protected $signature = 'priceoffers:remove-expired';
    protected $description = 'Remove expired price offer items where the end date has passed';

    public function handle()
    {
        $now = Carbon::now();
        $expiredCount = PriceOfferItem::where('end_date', '<', $now)->delete();

        $this->info("Removed {$expiredCount} expired price offer items.");
        return 0;
    }
}
