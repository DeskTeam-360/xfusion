<?php

return [

    /*
    | If set, public company API routes require header:
    | Authorization: Bearer {this value}
    | Leave empty to allow unauthenticated reads (use only behind firewall / staging).
    */
    'token' => env('FUSION_API_TOKEN'),

    /*
    | Shared secret for scheduled jobs that call maintenance URLs (refresh-all-users,
    | export-password-to-keap). Send it as header X-Cron-Token or ?token=. Unset = only a
    | signed-in WordPress administrator may call them.
    */
    'cron_token' => env('CRON_TOKEN'),

];
