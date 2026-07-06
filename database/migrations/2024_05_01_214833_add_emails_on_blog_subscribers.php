<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use App\Models\license_notification;
use App\Models\client;
use App\Models\blog_email_subscriber;

class AddEmailsOnBlogSubscribers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $license_notifications = license_notification::pluck('email')->toArray();
        $client_notifications = client::pluck('email')->toArray();
        /*merge the two arrays and remove duplicates*/
        $emails = array_unique(array_merge($license_notifications, $client_notifications));
        foreach($emails as $email){
            $blog_email_subscriber = new blog_email_subscriber();
            $blog_email_subscriber->email = $email;
            $blog_email_subscriber->unique_id = Str::uuid()->toString();
            $blog_email_subscriber->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        blog_email_subscriber::truncate();
    }
}
