@php use App\Models\WpPost; @endphp
<div class="w-full">

    <br><br>
    <div class="col-span-12 grid grid-cols-12 gap-3">

        <div class="lg:col-span-6 md:col-span-6 sm:col-span-6 col-span-12">
            <label for="search" class="form-label  text-md mb-10" style="width: 100%">
                Search topic or course...
            </label>
            <br>
            <input id="search" type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search topic or course..."
                   class="border border-gray-300 p-2 rounded mt-2 w-6/12 py-2.5 px-4 form-control"/>
        </div>
    </div>
    <br>


    @php
        $filteredCourseUser = $this->filteredCourseUser;
    @endphp
    @foreach($filteredCourseUser as $lessonId=>$lessons)
        <h3 class="text-2xl">
            Lesson {{ WpPost::find($lessonId)->post_name }}
        </h3>
        <br>
        <table class="border-collapse border-wishka-400 w-full text-sm text-left rounded table-auto "
               style="width: 100%">
            <thead class="text-md text-uppercase uppercase dark:bg-dark  text-bold">
            <tr class="border-b-[3px] border-gray-200 border-collapse">
                <td style="padding: 10px">TopicId</td>
                <td style="padding: 10px">Course</td>
                <td style="padding: 10px">Topic</td>
                <td style="padding: 10px">Date Submit</td>
                {{--                <td style="padding: 10px">Orders</td>--}}
                {{--                <td style="padding: 10px">Link</td>--}}
                <td class="text-center">Action</td>
            </tr>
            </thead>
            @foreach($lessons['topics'] as $courseId=>$courses)
                @foreach($courses as $topicId=>$topic)
                    @if($filteredCourseUser[$lessonId]['topics'][$courseId][$topicId]['value']==1)
                        <tr class="border-b border-gray-200 text-md" style="height: 50px" wire:key="{{ $topicId }}">
                            <td style="padding: 10px">{{ $topicId }}</td>
                            <td style="padding: 10px">{{ WpPost::find($courseId)->post_title }}</td>
                            <td style="padding: 10px">{{ WpPost::find($topicId)->post_title }}</td>
                            <td style="padding: 10px" wire:ignore>
{{--                                {{ $filteredCourseUser[$lessonId]['topics'][$courseId][$topicId]['date_created'] }}--}}

                                <span  class="note-column"
                                   data-timestamp="{{strtotime($filteredCourseUser[$lessonId]['topics'][$courseId][$topicId]['date_created'])}}">
                                    <span class="localized-time">
                                        {{ $filteredCourseUser[$lessonId]['topics'][$courseId][$topicId]['date_created'] }}
                                    </span>
                                </span>
                            </td>
                            {{--                            <td style="padding: 10px">{{ $filteredCourseUser[$lessonId]['topics'][$courseId][$topicId]['url'] }}</td>--}}
                            <td style="padding: 10px" class="text-center">
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
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".note-column").forEach(function (element) {
                let timestamp = element.getAttribute("data-timestamp");
                if (timestamp) {
                    let date = new Date(timestamp * 1000);
                    let options = {year: "numeric", month: "long", day: "numeric", hour: "2-digit", minute: "2-digit"};
                    let formattedDate = date.toLocaleString(undefined, options);
                    element.querySelector(".localized-time").innerText = formattedDate;
                }
            });
        });

    </script>
</div>
