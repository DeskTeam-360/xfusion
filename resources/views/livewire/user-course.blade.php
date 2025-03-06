@php use App\Models\WpPost; @endphp
<div class="w-full">

    <br><br>
    @foreach($courseUser as $lessonId=>$lessons)
        <h3 class="text-2xl">
            Lesson {{ WpPost::find($lessonId)->post_name }}
        </h3>
        <br>
        {{--    <div style="float: right; margin-bottom: 20px">--}}
        {{--        Search : <input wire:model.live="search.{{$lessonId}}" type="text" class="form-control" style="width: 300px">--}}
        {{--    </div>--}}



        <table class="border-collapse border-wishka-400 w-full text-sm text-left rounded table-auto"
               style="width: 100%">
            <thead class="text-md text-uppercase uppercase dark:bg-dark  text-bold">
            <tr class="border-b-[3px] border-gray-200 border-collapse">
                <td>TopicId</td>
                <td>Course</td>
                <td>Topic</td>
                <td class="text-center">Action</td>
            </tr>
            </thead>
            @foreach($lessons['topics'] as $courseId=>$courses)
                @foreach($courses as $topicId=>$topic)
                    @if($courseUser[$lessonId]['topics'][$courseId][$topicId]==1)
                        <tr class="border-b border-gray-200 text-md" style="height: 40px" wire:key="{{ $topicId }}">
                            <td>{{ $topicId }}</td>
                            <td>{{ WpPost::find($courseId)->post_title }}</td>
                            <td>{{ WpPost::find($topicId)->post_title }}</td>
                            <td class="text-center">
                                <i class="ti ti-trash btn btn-light-error"
                                   wire:click="removeProgress({{$lessonId}},{{$courseId}},{{$topicId}})"></i>
                                <i class="ti ti-eye btn-light-primary btn"
                                   wire:click="redirectToCourse({{$lessonId}},{{$courseId}},{{$topicId}})"></i>
                            </td>
                        </tr>
                    @endif

                @endforeach
            @endforeach
        </table>
        <br><br>
    @endforeach
</div>
