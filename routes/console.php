<?php

use App\Models\Event;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('events:sync-status', function () {
    $updated = Event::syncAutomaticStatuses();
    $this->info("Sinkronisasi otomatis status kegiatan selesai ({$updated} kegiatan diperbarui).");
})->purpose('Sinkronisasi otomatis status kegiatan berdasarkan tanggal aktual hari ini');

Schedule::command('events:sync-status')->dailyAt('00:01')->timezone('Asia/Jakarta');

