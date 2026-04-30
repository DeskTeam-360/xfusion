<?php

return [

    /*
    | If set, public company API routes require header:
    | Authorization: Bearer {this value}
    | Leave empty to allow unauthenticated reads (use only behind firewall / staging).
    */
    'token' => env('FUSION_API_TOKEN'),

];
