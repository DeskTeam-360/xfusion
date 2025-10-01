<?php

use App\Models\CourseList;
use App\Models\Tag;
use App\Models\User;
use App\Models\WpGfEntry;
use App\Models\WpUserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use KeapGeek\Keap\Facades\Keap;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::post('/send-mail-result', function (Request $request) {
//     $id = $request->input('id');
//     $pdfResult = $request->file('pdf_result');
//     // $data = $request->all();
//     // $user = User::find($data['user_id']);
//     // $user->update(['name'=>$data['name']]);
//     // return $user;
// });

// Simple PDF Result Save to Storage
Route::post('/save-pdf-result', function (Request $request) {
    try {
        // Validate the request (without mimes validation to avoid finfo dependency)
        $request->validate([
            'pdf_result' => 'required|file|max:10240', // 10MB max
            'user_id' => 'required|integer',
            'comment' => 'nullable|string|max:1000'
        ]);

        $pdfFile = $request->file('pdf_result');
        $userId = $request->input('user_id');
        $comment = $request->input('comment', ''); 
        $tagId = 2097;

        // Use custom file upload helper to avoid finfo dependency
        $originalName = $pdfFile->getClientOriginalName();
        
        // Validate file extension
        if (!\App\Helpers\FileUploadHelper::validateFileExtension($originalName, ['pdf'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only PDF files are allowed'
            ], 422);
        }

        // Generate unique filename and upload
        $filename = \App\Helpers\FileUploadHelper::generateFilename($originalName, $userId);
        $filePath = \App\Helpers\FileUploadHelper::uploadFile($pdfFile, 'pdf-results', $filename);

        $pdfUrl = url('storage/' . $filePath);

        // Log the result (optional - you can save to database later)
        \App\Models\Log::create([
            'log' => json_encode([
                'user_id' => $userId,
                'file_path' => $filePath,
                'original_name' => $originalName,
                // 'file_size' => $pdfFile->getSize(),
                'comment' => $comment,
                'created_at' => now()
            ])
        ]);

        $user = User::find($userId);
        $contact = Keap::contact()->createOrUpdate([
            'email_addresses' => [
                [
                    'email' => $user->email,
                    'field' => 'EMAIL1',
                ],
            ],
            'custom_fields'   => [
                [
                    'id'      => '99',
                    'content' => $pdfUrl,
                ]
            ],
        ]);
        Keap::contact()->tag($contact['id'], [$tagId]);
        return response()->json([
            'success' => true,
            'message' => 'PDF result saved successfully',
            'data' => [
                'file_path' => $filePath,
                'filename' => $filename,
                'original_name' => $originalName,
                // 'file_size' => $pdfFile->getSize(),
                'comment' => $comment,
                'pdf_url' => $pdfUrl
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to save PDF result',
            'error' => $e->getMessage()
        ], 500);
    }
});


Route::post('/keap-gform/', function (Request $request) {
    $data = $request->all();
    \App\Models\Log::create(['log'=>json_encode($data)]);
    $data['user_id'] = WpGfEntry::find($data['user_id'])->created_by;
    $tag = Tag::where('name','=',$data['tag'])->first();
    $user = User::find($data['user_id']);
    $keapId = $user->meta->where('meta_key','keap_contact_id')->first();
    if ($keapId!=null){
        Keap::contact()->tag($keapId->meta_value, [$tag->id]);
    }
    $users = User::whereHas('meta',function ($q){
        $q->where('meta_key', '=', 'keap_contact_id');
    })->get();
    foreach ($users as $user){
        $wpUserMeta = WpUserMeta::where('user_id','=',$user->ID)->where('meta_key','=','keap_tags')->first();
        $keapId = WpUserMeta::where('user_id','=',$user->ID)->where('meta_key','=','keap_contact_id')->first()->meta_value;
        $tagKeaps = Keap::contact()->tags($keapId);
        foreach ($tagKeaps as $tk){
            $tag[]=$tk['tag']['id'];
        }
        $tag = implode(';',$tag);
        if ($wpUserMeta!=null){
            WpUserMeta::find($wpUserMeta->umeta_id)->update(['meta_value'=>$tag]);
        }else{
            WpUserMeta::create([
                'user_id'=>$user->ID,
                'meta_key'=>'keap_tags',
                'meta_value'=>$tag
            ]);
        }
    }
});

Route::post('/next-course/', function (Request $request) {
    $data = $request->all();
    $dataEntry = WpGfEntry::find($data['entry_id']);
    $userId = $dataEntry->created_by;
    $tag = CourseList::where('wp_gf_form_id',$dataEntry->form_id)->first()->keap_tag_next;
    $tag2 = CourseList::where('wp_gf_form_id',$dataEntry->form_id)->first()->keap_tag_next;
    if ($tag2==322){
        $userId = $dataEntry->created_by;
        $user =  \Corcel\Model\Meta\UserMeta::where('user_id','=',$userId)->where('meta_key','user_access')->first();
        $userAccess = $user->meta_value;
        if (str_contains($userAccess, '"sustain"')) {
            $tag2 = 322;
            if ($tag2){
                $tag = tag2;
                $user = User::find($userId);
                $keapId = $user->meta->where('meta_key','keap_contact_id')->first();
                $keapTag = $user->meta->where('meta_key','keap_tags')->first();
                $accessTag = $user->meta->where('meta_key','access_tags')->first();
                if ($keapId!=null){
                    Keap::contact()->tag($keapId->meta_value, [$tag]);
                }
        
                if ($accessTag!=null){
                    $accessTag->update(['meta_value' => $accessTag->meta_value.";$tag"]);
                 }else{
                    WpUserMeta::create([
                        'user_id'=>$user->ID,
                        'meta_key'=>'access_tags',
                        'meta_value'=>$tag
                    ]);
                }
        
                if ($keapTag!=null){
                    $keapTag->update(['meta_value' => $keapTag->meta_value.";$tag"]);
                }else{

                    WpUserMeta::create([
                        'user_id'=>$user->ID,
                        'meta_key'=>'keap_tags',
                        'meta_value'=>$tag
                    ]);
                }
            }
        } 
        
        if (str_contains($userAccess, '"transform"')) {
            $tag2 = 1012;
            if ($tag2){
                $tag = tag2;
                $user = User::find($userId);
                $keapId = $user->meta->where('meta_key','keap_contact_id')->first();
                $keapTag = $user->meta->where('meta_key','keap_tags')->first();
                $accessTag = $user->meta->where('meta_key','access_tags')->first();
                if ($keapId!=null){
                    Keap::contact()->tag($keapId->meta_value, [$tag]);
                }
        
                if ($accessTag!=null){
                    $accessTag->update(['meta_value' => $accessTag->meta_value.";$tag"]);
                 }else{
                    WpUserMeta::create([
                        'user_id'=>$user->ID,
                        'meta_key'=>'access_tags',
                        'meta_value'=>$tag
                    ]);
                }
        
                if ($keapTag!=null){
                    $keapTag->update(['meta_value' => $keapTag->meta_value.";$tag"]);
                }else{
                    WpUserMeta::create([
                        'user_id'=>$user->ID,
                        'meta_key'=>'keap_tags',
                        'meta_value'=>$tag
                    ]);
                }
            }
        }
    }else{
        if ($tag){
            $user = User::find($userId);
            $keapId = $user->meta->where('meta_key','keap_contact_id')->first();
            $keapTag = $user->meta->where('meta_key','keap_tags')->first();
            $accessTag = $user->meta->where('meta_key','access_tags')->first();
            if ($keapId!=null){
                Keap::contact()->tag($keapId->meta_value, [$tag]);
            }
    
            if ($accessTag!=null){
                $accessTag->update(['meta_value' => $accessTag->meta_value.";$tag"]);
             }else{
                WpUserMeta::create([
                    'user_id'=>$user->ID,
                    'meta_key'=>'access_tags',
                    'meta_value'=>$tag
                ]);
            }
    
            if ($keapTag!=null){
                $keapTag->update(['meta_value' => $keapTag->meta_value.";$tag"]);
            }else{
                WpUserMeta::create([
                    'user_id'=>$user->ID,
                    'meta_key'=>'keap_tags',
                    'meta_value'=>$tag
                ]);
            }
        }    
    }
    
});
Route::get('/next-course/', function (Request $request) {
    return [
        'code'=>200,
        'message'=>'Wrong method',
    ];
});

