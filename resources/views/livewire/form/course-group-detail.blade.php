<div>
    <table class="table" style="width: 100%">
        <tr>
            <td>No.</td>
            <td>Title</td>
            <td>Order</td>
        </tr>
        @foreach($courseGroup->courseGroupDetails as $index=>$cgd)
            <tr>
                <td>{{ $index+1 }}</td>
                <td>{{ $cgd->courseList->course_title.' '.$cgd->courseList->page_title }}</td>
                <td>
                    <input type="number" class="py-2.5 px-4 form-control" wire:model.live="orders.{{$cgd->id}}" wire:change="setChangeOrder({{$cgd->id}})">
                </td>
            </tr>
        @endforeach

    </table>
</div>
