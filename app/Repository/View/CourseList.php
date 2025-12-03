<?php

namespace App\Repository\View;

use App\Models\Tag;
use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class CourseList extends \App\Models\CourseList implements View
{

    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        return empty($query) ? static::query()
            : static::query()
            ->where('url', 'like', "%$query%")
            ->orWhere('course_title', 'like', "%$query%")
            ->orWhere('page_title', 'like', "%$query%");
    }

    public static function tableView(): array
    {
        return [
            'searchable' => true,
        ];
    }

    public static function tableField(): array
    {
        return [
            ['label' => '#', 'sort' => 'id', 'width' => '7%'],
            ['label' => 'Course Info', 'sort' => 'course_title'],
            ['label' => 'User finished', 'sort' => 'form_id'],
//            ['label' => 'Link', 'sort' => 'url'],
            ['label' => 'Action'],
        ];
    }

    public static function tableData($data = null): array
    {
        $link = route('course-title.edit',$data->id);
        $tag='';
        $tag2='';
        if ($data->keap_tag!=null){
            $tag = Tag::find($data->keap_tag)->name??"Tag has been deleted";
        }
        if ($data->keap_tag_next!=null){
            $tag2 = Tag::find($data->keap_tag_next)->name??"Tag has been deleted";
        }
        $info = '';
        if($data->wp_gf_form_id){
            $info = '<br><b>Form id</b>: '.$data->wp_gf_form_id;
        }
        if($data->keap_tag){
            $info .= '<br> <b>Tag</b>: '.$tag;
        }
        if($data->keap_tag_next){
            $info .= '<br> <b>Next Tag</b>: '.$tag2;
        }

        $linkUrl = $data->url;
        $path = parse_url($linkUrl, PHP_URL_PATH);
        $query = parse_url($linkUrl, PHP_URL_QUERY);
        $cleanUrl = $query ? $path . '?' . $query : $path;

        $linkUrlNext = $data->url_next;
        $pathNext = parse_url($linkUrlNext, PHP_URL_PATH);
        $queryNext = parse_url($linkUrlNext, PHP_URL_QUERY);
        $cleanUrlNext = $queryNext ? $pathNext . '?' . $queryNext : $pathNext;

        if($cleanUrl){
            $info .= "<br><b>Main link</b> : <a target='_blank' href='$cleanUrl'>$cleanUrl</a>";
        }else{
            $info .= '';
        }

        $linkNextUrl = '';
        if($cleanUrlNext){
            $info .= "<br><b> Next Page Link</b> : <a target='_blank' href='$cleanUrlNext'>$cleanUrlNext</a>";
        }else{
            $info .= '';
        }


        $courseInfo = "<b>Course Title</b>: ".$data->course_title.' <br> <b>Page Title</b>: '.$data->page_title . '<br> <b>Tools</b>: '.($data->repeat_entry==1?'Yes':'No');

        $courseInfo .= $info;


        $userFinished = $data->wp_gf_form_id?\App\Models\WpGfEntry::where('form_id',$data->wp_gf_form_id)->where('status','active')->count():0;

        $totalUser = \App\Models\User::get()->count();
        return [
            ['type' => 'string','data'=>$data->id],
            ['type' => 'raw_html', 'data' => $courseInfo],
            ['type' => 'string', 'data' => $userFinished.'/'.$totalUser],
            ['type' => 'raw_html','text-align'=>'center', 'data' => "
<div class='flex gap-1'>
<button href='#' wire:click='deleteItem($data->id)' class='btn btn-error'>Delete</button>
<a href='$link' class='btn btn-primary'>Edit</a>
</div>"],

        ];
    }
}
