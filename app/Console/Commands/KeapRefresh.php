<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use KeapGeek\Keap\Facades\Keap;

class KeapRefresh extends Command
{
    protected $signature = 'keap:refreshasd';
    protected $description = 'Refresh Keap access tokens';

    public function __construct()
    {
//        dd('aaa');
        parent::__construct();
    }

    public function handle()
    {
        $data = Keap::contact()->find(37012, [
            'job_title', 'custom_fields'
        ]);
        dd($data);

        $this->info('Testttttttt Command executed successfully! Refreshing Keap tokens...');
    }
}
