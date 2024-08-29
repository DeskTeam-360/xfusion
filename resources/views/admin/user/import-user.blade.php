<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <form action="{{ route('import-user') }}" method="POST" enctype="multipart/form-data" style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; background-color: #f9f9f9;">
                @csrf
                <div style="margin-bottom: 15px;">
                    <label for="file" style="display: block; margin-bottom: 8px; font-weight: bold;">Upload CSV file:</label>
                    <input type="file" name="file" required style="display: block; width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                </div>
                <button type="submit" style="padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Import Users</button>
            </form>
        </div>
    </div>

</x-admin-layout>
