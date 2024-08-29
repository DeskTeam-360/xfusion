<div>
    @foreach($entries as $entry)
        {{ \App\Models\User::find($entry->created_by)->user_nicename??'No User' }} <br>
        {{ "$entry->source_url?dataId=$entry->id" }}
    @endforeach
</div>


