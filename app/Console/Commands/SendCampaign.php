<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use KeapGeek\Keap\Facades\Keap;

class SendCampaign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-campaign';

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
//        dd(Carbon::now(),Campaign::where('status','scheduled')->where('time_schedule','>=',Carbon::now())->get());
        foreach (Campaign::where('status','scheduled')->where('time_schedule','<=',Carbon::now())->get() as $campaign) {
            foreach (explode(';',$campaign->tags) as $tag){
                $users = explode(';',$campaign->users);
                $results =Keap::tag()->applyToContacts(
                    $tag,
                    $users
                );
                foreach ($results as $key=>$note){
                    CampaignLog::create([
                        'tag_id'=>$tag,
                        'user_id'=>$key,
                        'status'=>$note
                    ]);
                }
            }
            $c= Campaign::find($campaign->id);
            $c->update([
                'status'=>'send'
            ]);
//            dd($results);
//            var_dump();
        }
    }
}
