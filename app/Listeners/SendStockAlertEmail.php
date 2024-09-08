<?php

namespace App\Listeners;

use App\Events\IngredientStockLow;
use App\Mail\StockAlertMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendStockAlertEmail implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(IngredientStockLow $event): void
    {
        Mail::to(config('email.alert_email'))->send(new StockAlertMail($event->ingredient));

    }
}
