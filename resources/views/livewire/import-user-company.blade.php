<div>
    <a href="{{ route('template-download') }}" class="btn btn-success">Download template</a>
    <br><br>
    <div class="flex justify-between gap-3">

        <div>
            <label for="" class="form-label mb-3">File CSV</label>
            <input type="file" wire:model="file" class="form-control mt-2"  accept="text/csv">
            <button wire:click="updatedFile()" class="btn btn-primary">Check CSV</button>
        </div>
    </div>

    @error('file') <span class="text-red-500">{{ $message }}</span> @enderror

    @if($rows)
        <table class="table-auto mt-4 border-collapse border border-gray-300 w-full">
            <thead>
            <tr>
                @foreach($rows[0] as $header)
                    <th class="border px-4 py-2 bg-gray-100">{{ $header }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach(array_slice($rows, 1) as $row)
                <tr>
                    @foreach($row as $cell)
                        <td class="border px-4 py-2">{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="mb-2 mt-2">
            <label for="mail-send" class="flex align-items-center">
                <input type="checkbox" id="mail-send" style="margin-right: 10px;" wire:model="keapMailSend">
                Send email and password access ?
            </label>
        </div>
        <br>
        <button wire:click="import" class="btn">Import all users to company</button>
    @endif

</div>
