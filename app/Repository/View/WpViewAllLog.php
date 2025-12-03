<?php

namespace App\Repository\View;

use App\Repository\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class WpViewAllLog extends \App\Models\WpViewAllLog implements View
{
    public static function tableSearch($params = null): Builder
    {
        $query = $params['query'];
        $params = $params['param1'];
        
        $searchUser = \App\Models\User::where('user_email', 'like', "%$query%")->get()->pluck('ID')->toArray();

        $searchWpGfEntry = \App\Models\WpGfEntry::whereHas('wpGfForm', function ($q) use ($query) {
            $q->where('title', 'like', "%$query%");
        })->get()->pluck('id')->toArray();

        return empty($query) ? static::query()
            ->orderByDesc('log_time')
            : static::query()
                ->where('note', 'like', "%$query%")
                ->orWhereIn('user_id', $searchUser)
                ->orWhere('source', 'like', "%$query%")
                ->orWhere('reference', 'like', "%$query%")
                ->orWhereIn('reference', $searchWpGfEntry)
                ->orderByDesc('log_time');

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
            ['label' => 'Log Time', 'sort' => 'log_time'],
            ['label' => 'User'],
            ['label' => 'Source'],
            ['label' => 'Note']
           
        ];
    }

    public static function tableData($data = null): array
    {
        if($data->source == "gravity_form"){
            $data->reference = \App\Models\WpGfEntry::find($data->reference)->wpGfForm->title;
        }else{
            $data->reference = $data->reference;
        }
        $user = '';
        if($data->user){
            $user = $data->user->user_email;
        }else{
            $user = 'Deleted User with ID: '.$data->user_id;
        }
        $source = str_replace('_', ' ', $data->source);
        $source = ucwords($source);

        return [
            ['type' => 'string', 'data' => $data->log_time],
            ['type' => 'string', 'data' => $user],
            ['type' => 'string', 'data' => $source],
            ['type' => 'string', 'data' => $data->note. ' '. $data->reference],
            
        ];
    }
}
