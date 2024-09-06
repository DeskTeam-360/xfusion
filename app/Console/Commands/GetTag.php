<?php

namespace App\Console\Commands;

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
        $users = User::whereHas('meta', function ($q) {
            $q->where('meta_key', '=', 'keap_contact_id');
        })->get();
        foreach ($users as $user) {
            $wpUserMeta = WpUserMeta::where('user_id', '=', $user->ID)->where('meta_key', '=', 'keap_tags')->first();
            $keapId = WpUserMeta::where('user_id', '=', $user->ID)->where('meta_key', '=', 'keap_contact_id')->first()->meta_value;
            $tagKeaps = Keap::contact()->tags($keapId);
            foreach ($tagKeaps as $tk) {
                $tag[] = $tk['tag']['id'];
            }
            $tag = implode(';', $tag);
            if ($wpUserMeta != null) {
                WpUserMeta::find($wpUserMeta->umeta_id)->update(['meta_value' => $tag]);
            } else {
                WpUserMeta::create([
                    'user_id' => $user->ID,
                    'meta_key' => 'keap_tags',
                    'meta_value' => $tag
                ]);
            }
        }
    }
}
