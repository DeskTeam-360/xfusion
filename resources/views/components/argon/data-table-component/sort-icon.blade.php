@if ($sortField !== $field)
    <div class="float-right" style="font-size: 16px;opacity: .5">
        <i class="fa-solid fa-sort"></i>
    </div>
@elseif ($sortAsc)
    <div class="float-right" style="font-size: 16px">
        <i class="fa-solid fa-sort-up"></i>
    </div>
@else
    <div class="float-right" style="font-size: 16px">
        <i class="fa-solid fa-sort-down"></i>
    </div>
@endif
