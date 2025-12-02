<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Hold;

class ReleaseHoldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $hold;


    public function __construct(Hold $hold)
    {
        $this->hold = $hold;
    }


    public function handle()
    {
        $hold = Hold::find($this->hold->id);
        if (!$hold) return;


        if ($hold->status !== 'active') return;


        DB::transaction(function () use ($hold) {
            $updated = DB::table('holds')
            ->where('id', $hold->id)
            ->where('status', 'active')
            ->update(['status' => 'expired', 'updated_at' => now()]);


            if ($updated === 1) {
                DB::update('UPDATE products SET available_stock = available_stock + ? WHERE id = ?', [$hold->qty, $hold->product_id]);
                Cache::forget("product:{$hold->product_id}");
            }
        });
    }
}
