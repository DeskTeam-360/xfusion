<x-admin-layout>
    <div class="container full-container py-5">
        <div class="w-full">
            <a href="{{ route('user.create') }}" class="btn btn-primary">Create new user</a>
            <a style="margin-left: 5px;" href="{{ route('to-import-user') }}" class="btn btn-secondary">Import CSV</a>
           
            @if(App\Models\WpUserMeta::where('meta_key', '=', 'plain_password')->get()->isNotEmpty())
                @php
                    $usersWithPasswords = App\Models\WpUserMeta::where('meta_key', '=', 'plain_password')
                        ->with('user')
                        ->get()
                        ->map(function($meta) {
                            return $meta->user ? $meta->user->display_name ?? $meta->user->user_login : 'Unknown User';
                        })
                        ->filter()
                        ->unique()
                        ->values();
                @endphp
                <button style="margin-left: 5px;" onclick="confirmExport()" class="btn btn-secondary">Export password to keap ({{ $usersWithPasswords->count() }})</button>
                
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                function confirmExport() {
                    const userList = @json($usersWithPasswords);
                    
                    // Create HTML list for better formatting
                    const userListHtml = userList.map((user, index) => 
                        `<div style="padding: 5px 0; border-bottom: 1px solid #eee;">
                            <strong>${index + 1}.</strong> ${user}
                         </div>`
                    ).join('');
                    
                    Swal.fire({
                        title: 'Export Passwords to Keap',
                        html: `
                            <div style="text-align: left;">
                                <p style="margin-bottom: 15px; color: #666;">
                                    Are you sure you want to export passwords to Keap?
                                </p>
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                                    <h4 style="margin: 0 0 10px 0; color: #333;">Users to be exported (${userList.length}):</h4>
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        ${userListHtml}
                                    </div>
                                </div>
                                <p style="margin-top: 15px; color: #e74c3c; font-weight: bold;">
                                    ⚠️ This action will export user passwords to Keap system. and you cannot see again from this admin portal.
                                </p>
                            </div>
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, Export Now!',
                        cancelButtonText: 'Cancel',
                        width: '600px',
                        customClass: {
                            popup: 'swal2-popup-custom'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'Exporting...',
                                text: 'Please wait while we export the passwords to Keap',
                                icon: 'info',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                willOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            
                            // Make AJAX request to export route
                            fetch('{{ route("export-password-to-keap") }}', {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log(data);
                                // Close loading
                                Swal.close();
                                
                                // Show success message
                                Swal.fire({
                                    title: 'Export Successful!',
                                    text: 'All user passwords have been successfully exported to Keap.',
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#28a745'
                                }).then(() => {
                                    // Reload the page to update the button state
                                    window.location.reload();
                                });
                            })
                            .catch(error => {
                                // Close loading
                                Swal.close();
                                
                                // Show error message
                                Swal.fire({
                                    title: 'Export Failed!',
                                    text: 'There was an error exporting passwords to Keap. Please try again.',
                                    icon: 'error',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#dc3545'
                                });
                                
                                console.error('Export error:', error);
                            });
                        }
                    });
                }
                </script>
                
                <style>
                .swal2-popup-custom {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                </style>
            @endif
           
            <livewire:table.master name="User"/>
        </div>
        <div class="mb-5"></div>
    </div>
</x-admin-layout>
