<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\User;
use App\Models\WpUserMeta;
use Illuminate\Console\Command;
use KeapGeek\Keap\Facades\Keap;

class GetTag extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-tag';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::whereDoesntHave('meta', function ($q) {
            $q->where('meta_key', '=', 'keap_contact_id');
        })->get();
        foreach ($users as $user) {
            $k = Keap::contact()->list([
                'email' => $user->email
            ]);
            if ($k != null) {
                WpUserMeta::create([
                    'meta_key' => 'keap_contact_id',
                    'user_id' => $user->ID,
                    'meta_value' => $k[0]['id']
                ]);
            }
        }

        $users = User::whereHas('meta', function ($q) {
            $q->where('meta_key', '=', 'keap_contact_id');
        })->get();

        foreach ($users as $user) {
            $tag = [];
            $keapId = WpUserMeta::where('user_id', '=', $user->ID)->where('meta_key', '=', 'keap_contact_id')->first()->meta_value;
            $tagKeaps = Keap::contact()->tags($keapId);
            foreach ($tagKeaps as $tk) {
                if ($tk['tag']['category'] == "Xfusion Testing") {
                    $tag[] = $tk['tag']['id'];
                }
            }
            $tag = implode(';', $tag);
            $wpUserMeta = WpUserMeta::where('user_id', '=', $user->ID)->where('meta_key', '=', 'keap_tags')->first();
            if ($wpUserMeta != null) {
                $wpUserMeta->update(['meta_value' => $tag]);
            } else {
                WpUserMeta::create([
                    'user_id' => $user->ID,
                    'meta_key' => 'keap_tags',
                    'meta_value' => $tag
                ]);
            }

            $k = Keap::contact()->list([
                'email' => $user->email
            ])[0];

            $fn = WpUserMeta::where('user_id', $user->ID)->where('meta_key', 'first_name')->first();
            $ln = WpUserMeta::where('user_id', $user->ID)->where('meta_key', 'last_name')->first();

            if ($ln != null) {
                $ln->update([
                    'meta_value' => $k['family_name']
                ]);
            } else {
                WpUserMeta::create([
                    'user_id' => $user->ID,
                    'meta_key' => 'last_name',
                    'meta_value' => $k['family_name']
                ]);
            }
            if ($fn != null) {
                $fn->update([
                    'meta_value' => $k['given_name']
                ]);
            } else {
                WpUserMeta::create([
                    'user_id' => $user->ID,
                    'meta_key' => 'first_name',
                    'meta_value' => $k['given_name']
                ]);
            }
        }
        $tags = Keap::tag()->list([
            'category' => 44
        ]);

        foreach ($tags as $tag) {
            $t = Tag::find($tag['id']);
            $tag['category'] = $tag['category']['id'];
            if ($t != null) {
                $t->update($tag);
            } else {
                Tag::create($tag);
            }
        }
    }
}
