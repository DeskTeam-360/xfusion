<form wire:submit="{{ $action }}">
    <x-select title="Course title" model="courseTitle" :options="$optionCourseTitle" required="true"/>
    <x-input title="Page title" model="pageTitle" required="true"/>
    <x-input title="Url" model="url" required="true"/>
    <x-input title="Next Url" model="urlNext"/>


    <x-select title="Gravity Form" model="gfFormId" :options="$optionWpGfForm"/>

    <div class="mt-3">
        <label for="lms-topic-search" class="block text-sm font-bold dark:text-light">
            LMS topic (LearnDash) — opsional
        </label>
        <p class="mt-1 text-xs text-gray-500">Ketik minimal 2 huruf di judul topik untuk mencari <code class="text-xs">wp_posts</code> (<code class="text-xs">sfwd-topic</code>).</p>
        @if ($lmsTopicId)
            <div class="mt-2 flex flex-wrap items-center gap-2 rounded border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-600 dark:bg-dark">
                <span class="text-sm">{{ $lmsTopicSelectedLabel ?: 'ID: '.$lmsTopicId }}</span>
                <button type="button" wire:click="clearLmsTopic" class="text-sm text-red-600 hover:underline">
                    Hapus pilihan
                </button>
            </div>
        @endif
        <input
            id="lms-topic-search"
            type="search"
            wire:model.live.debounce.300ms="lmsTopicSearch"
            placeholder="Cari judul topik…"
            autocomplete="off"
            class="mt-2 w-full rounded border border-gray-300 bg-gray-100 px-3 py-2 text-sm focus:border-primary-light focus:outline-none dark:border-primary-light dark:bg-dark dark:text-light"
        />
        @if (strlen(trim($lmsTopicSearch)) > 0 && strlen(trim($lmsTopicSearch)) < 2)
            <p class="mt-1 text-xs text-gray-500">Masukkan setidaknya 2 karakter.</p>
        @endif
        @if (count($lmsTopicResults) > 0)
            <ul class="mt-1 max-h-48 overflow-auto rounded border border-gray-200 bg-white shadow-sm dark:border-gray-600 dark:bg-dark">
                @foreach ($lmsTopicResults as $row)
                    <li>
                        <button
                            type="button"
                            wire:click="selectLmsTopic({{ $row['value'] }})"
                            class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            {{ $row['title'] }}
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
        @error('lmsTopicId')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-3" wire:ignore>
        <label for="{{'courseTag'}}"
               class="block text-sm font-bold dark:text-light">
            Course tag require
        </label>
        <select id="{{'courseTag'}}"
                class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white select2"
                multiple=""
                name="courseTag"
                style="padding:0  100px" wire:model="{{'courseTag'}}">
            @for($i=0;$i<count($optionCourseTag) ;$i++)
                <option value="{{$optionCourseTag[$i]['value']}}"
                        style="padding: 0 25px"
                    {{ ($optionCourseTag[$i]['value']==$courseTag)?'selected':''}}
                >
                    {{$optionCourseTag[$i]['title']}}
                </option>
            @endfor
        </select>
        <script>
            document.addEventListener('livewire:init', function () {
                let data;
                $('#courseTag').select2({
                    maximumSelectionLength: 1
                })
                $('#courseTag').on('change', function (e) {

                    data = $('#{{'courseTag'}}').select2("val");
                    // console.log(data[0])
                    @this.set('{{'courseTag'}}', data);
                })
            });
        </script>
    </div>

    <div class="mt-3" wire:ignore>
        <label for="{{'courseTagNext'}}"
               class="block text-sm font-bold dark:text-light">
            Course tag next
        </label>
        <select id="{{'courseTagNext'}}"
                class="bg-gray-200 appearance-none border-1 border border-gray-100 rounded w-full text-gray-700 leading-tight focus:outline-none dark:border-primary-light focus:bg-gray-100 dark:bg-dark dark:text-light focus:dark:border-white select2"
                multiple=""
                name="courseTagNext"
                style="padding:0  100px" wire:model="{{'courseTagNext'}}">
            @for($i=0;$i<count($optionCourseTag) ;$i++)
                <option value="{{$optionCourseTag[$i]['value']}}"
                        style="padding: 0 25px"
                    {{ ($optionCourseTag[$i]['value']==$courseTagNext)?'selected':''}}
                >
                    {{$optionCourseTag[$i]['title']}}
                </option>
            @endfor
        </select>
        <script>
            document.addEventListener('livewire:init', function () {
                let data;
                $('#courseTagNext').select2({
                    maximumSelectionLength: 1
                })
                $('#courseTagNext').on('change', function (e) {
                    data = $('#{{'courseTagNext'}}').select2("val");
                    @this.
                    set('{{'courseTagNext'}}', data);
                })
            });
        </script>
    </div>


    <div style="width: 300px">
        <x-input type="checkbox" title="Repeat Entry (tools)?" model="repeatEntry"/>
    </div>

    <div style="width: 300px">
        <x-input type="checkbox" title="Legacy form ?" model="legacy"/>
    </div>
    <br>


    <button type="submit" class="btn" wire:loading.attr="disabled">Submit</button>
</form>
