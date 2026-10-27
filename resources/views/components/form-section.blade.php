@props(['title'=>'','description'=>''])
{{-- A titled group of fields inside a form, with room above and below. --}}
<div style="margin:1.75rem 0 1rem;padding-top:1.25rem;border-top:1px solid #e5e7eb">
    <h5 style="margin:0 0 .35rem;font-size:1.15rem;font-weight:600">{!! $title !!}</h5>
    @if($description !== '')
        <p style="margin:0;color:#374151;font-size:.9rem;line-height:1.45">{{ $description }}</p>
    @endif
</div>
