@props(['title'=>'','model'=>'','required'=>false,'options'=>[],'isSelected'=>[],'class'=>'' ])
<div class="form-group col-span-6 sm:col-span-5" wire:ignore>
    <label for="{{$model}}">{{$title}}</label>
    <select id="{{$model}}" class="form-control select2" multiple="">
        @for($i=0;$i<count($options) ;$i++)
            <option value="{{$options[$i]['value']}}" {{ in_array($options[$i]['value'],$isSelected) ? 'selected="selected"' : '' }}>
{{--            <option value="{{$options[$i]['value']}}">--}}
                {{$options[$i]['title']}}
            </option>
        @endfor
    </select>
    @push('script')
        <script>
            $(document).ready(function () {
                let data;
                console.log("asd")
                $('#{{$model}}').select2();
                $('#{{$model}}').on('change', function (e) {
                    data = $('#{{$model}}').select2("val");
                @this.set('{{$model}}', data);
                })
            });
        </script>
    @endpush
</div>
