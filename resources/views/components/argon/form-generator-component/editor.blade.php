@props(['repository'])
<div class="mt-3 @isset($repository['class']) {{ $repository['class'] }} @endisset" wire:ignore>
    <label class="block text-sm font-bold dark:text-white" for="data{{ $repository['model'] }}">
        {{ $repository['title'] }}
    </label>
  <textarea name="{{ $repository['model'] }}" id="data{{ $repository['model'] }}" @isset($repository['disabled']) disabled @endisset>
  </textarea>
    <script>
        document.addEventListener('livewire:load', function () {
            const useDarkMode = window.localStorage.getItem('dark') === "true";
            const isSmallScreen = window.matchMedia('(max-width: 1023.5px)').matches;
            tinymce.init({
                selector: 'textarea#data{{ $repository['model'] }}',
                plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                tinycomments_mode: 'embedded',
                tinycomments_author: 'Author name',

                setup: function (editor) {
                    editor.on('init change', function () {
                        editor.save();
                    });
                    editor.on('change', function (e) {
                        @this.
                        set('form.{{$repository['model']}}', editor.getContent());
                    });
                },

                skin: useDarkMode ? 'oxide-dark' : 'oxide',
                content_css: useDarkMode ? '{{ asset('vendor/tinymce/dark.css') }}' : '{{ asset('vendor/tinymce/light.css') }}',
            });
            $("#data{{ $repository['model'] }}").val(@this.get('form.{{ $repository['model'] }}'))
            $(".tox-tinymce").last().addClass("dark:border-primary")
        });
    </script>
</div>
