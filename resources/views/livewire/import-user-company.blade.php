<div>
    <a href="{{ route('template-download') }}" class="btn btn-success">Download template</a>
    <br><br>
    <div class="flex justify-between gap-3">

        <div>
            <label for="" class="form-label mb-3">File CSV</label>
            <div style="position: relative;">
                <input type="file" wire:model="file" class="form-control mt-2" accept="text/csv">
                <!-- <div wire:loading wire:target="file" style="position:absolute;left:0;top:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;z-index:10;background:rgba(255,255,255,0.5);">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div> -->
            </div>
            <!-- <button wire:click="updatedFile()" class="btn btn-primary">Check CSV</button> -->
        </div>
    </div>

    @error('file') <span class="text-red-500">{{ $message }}</span> @enderror

    @if($errorMessage)
        <div class="alert alert-danger mt-3" role="alert">
            <strong>Error:</strong> {{ $errorMessage }}
        </div>
    @endif

    @if($successMessage)
        <div class="alert alert-success mt-3" role="alert">
            <strong>Success:</strong> {{ $successMessage }}
        </div>
    @endif

    @if(!empty($importErrors))
        <div class="alert alert-warning mt-3" role="alert">
            <strong>Import Errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($importErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!empty($importSuccess))
        <div class="alert alert-info mt-3" role="alert">
            <strong>Successful Imports:</strong>
            <ul class="mb-0 mt-2">
                @foreach($importSuccess as $success)
                    <li>{{ $success }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
