<?php

use App\Services\ChunkedVideoUpload;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('acadexa:purge-uploads', function (ChunkedVideoUpload $uploads) {
    $this->info('Abandoned uploads removed: ' . $uploads->purgeStale(24));
})->purpose('Delete chunk folders of video uploads abandoned for more than 24 hours');

// Production needs ONE cron entry (cPanel/hPanel):  * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
// It sends queued e-mails/notifications and runs the maintenance tasks below.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')->everyMinute()->withoutOverlapping();
Schedule::command('acadexa:trial-reminders')->dailyAt('09:00');
Schedule::command('acadexa:reassessment-reminders')->dailyAt('10:00');
Schedule::command('acadexa:purge-uploads')->dailyAt('03:00');
Schedule::command('acadexa:backup --keep=14')->dailyAt('02:00')->withoutOverlapping();
Schedule::call(function () {
    // Read notifications older than 6 months are no longer useful.
    DB::table('notifications')->whereNotNull('read_at')->where('created_at', '<', now()->subMonths(6))->delete();
})->weekly()->name('prune-notifications');
