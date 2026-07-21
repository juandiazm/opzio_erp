<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        #$schedule->command('command:send_queued_mails')->cron('* */6 * * *');
        #$schedule->command('command:create_month_incomes')->cron('0 8 * * *');
        $schedule->command('command:update_licenses_remaining_days')->cron('0 4 * * *');
        #$schedule->command('command:send_pay_remaining')->cron('0 11 * * *');
        #$schedule->command('command:generate_ia_blog')->cron('0 10 * * *');
        //$schedule->command('command:generate_ia_instagram_post')->cron('0 10 * * *');
        //$schedule->command('command:generate_ia_facebook_post')->cron('0 10 * * *');
        //$schedule->command('command:generate_ia_linkedin_post')->cron('0 10 * * *');
        //$schedule->command('command:generate_ia_twitter_post')->cron('0 10 * * *');
        $schedule->command('db:backup')->cron('0 2 * * *');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
