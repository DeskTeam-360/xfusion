<?php

use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use KeapGeek\Keap\Facades\Keap;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/keap-gform/', function (Request $request) {
    $data = $request->all();
    $tag = Tag::where('name','=',$data['tag'])->first();
    $user = User::find($data['user_id']);
    $keapId = $user->meta->where('meta_key','keap_contact_id')->first();
    if ($keapId!=null){
        Keap::contact()->tag($keapId->meta_value, [$tag->id]);
    }

});
