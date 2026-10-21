<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lead transfer: every website enquiry is also POSTed to lead.webhook_url, signed with lead.webhook_secret
 * (stored encrypted, never shown in the admin). Also switches the blog automation to English.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('settings')->updateOrInsert(['key' => 'lead.webhook_url'], [
            'value' => 'https://nexusai.phpcodedemo.com/webhooks/website/nexus-demo-company', 'is_secret' => false, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('settings')->updateOrInsert(['key' => 'lead.webhook_secret'], ['is_secret' => true, 'created_at' => $now, 'updated_at' => $now]);

        DB::table('settings')->updateOrInsert(['key' => 'news.language'], ['value' => 'English', 'is_secret' => false, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('settings')->where('key', 'news.audience')->where('value', 'like', 'Pymes%')->update([
            'value' => 'Business owners and marketers in India looking for practical advice on media, advertising, branding and digital marketing.', 'updated_at' => $now,
        ]);
        \Illuminate\Support\Facades\Cache::forget('settings.all');
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['lead.webhook_url', 'lead.webhook_secret'])->delete();
        \Illuminate\Support\Facades\Cache::forget('settings.all');
    }
};
