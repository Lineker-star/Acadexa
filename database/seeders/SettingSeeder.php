<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'site_name'          => 'ACADEXA',
            'site_description'   => 'The Official Learning Management System of ZTF University Institute',
            'contact_email'      => 'info@ztfuniversity.com',
            'contact_phone'      => '+237 222 000 000',
            'contact_address'    => "Koumé – Bertoua, East Region\nCameroon",
            'trial_days'         => '30',
            'facebook_url'       => 'https://facebook.com/ztfuniversity',
            'twitter_url'        => '',
            'youtube_url'        => '',
            'linkedin_url'       => '',
            'instagram_url'      => '',
            'cert_institution'   => 'ZTF University Institute',
            'cert_subheading'    => 'ACADEXA Learning Management System',
            'cert_sig_name'      => 'Prof. Emmanuel ZANG',
            'cert_sig_title'     => 'Director, ZTF University Institute',
            'cert_description'   => 'This is to certify that the above-named individual has successfully completed the course with distinction.',
            'cert_bg_color'      => '#0A2A5E',
            'maintenance_mode'   => '0',
            'allow_registration' => '1',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
