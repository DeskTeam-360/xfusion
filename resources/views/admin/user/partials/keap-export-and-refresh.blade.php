@once
    @push('script')
        <script>
            function refreshAllUsers() {
                Swal.fire({
                    title: 'Refresh All Users Progress',
                    text: 'This will refresh the course progress for all users. This may take a while. Continue?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#f39c12',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Refresh All!',
                    cancelButtonText: 'Cancel',
                    width: '500px',
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    Swal.fire({
                        title: 'Refreshing All Users...',
                        text: 'Please wait while we refresh progress for all users',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => Swal.showLoading(),
                    });

                    fetch('{{ route('refresh-all-users') }}', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    })
                        .then(response => response.json())
                        .then(data => {
                            Swal.fire({
                                title: 'Refresh Completed!',
                                html: `
                                    <div style="text-align: left;">
                                        <p><strong>Total Users Processed:</strong> ${data.data?.total_users ?? 0}</p>
                                        <p><strong>Users Updated:</strong> ${data.data?.users_updated ?? 0}</p>
                                        <p><strong>Total Entries Processed:</strong> ${data.data?.total_entries ?? 0}</p>
                                        <p><strong>Progress Entries Updated:</strong> ${data.data?.progress_updated ?? 0}</p>
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                width: '500px',
                            }).then(() => window.location.reload());
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Refresh Failed!',
                                text: 'There was an error refreshing all users. Please try again.',
                                icon: 'error',
                            });
                            console.error('Refresh all users error:', error);
                        });
                });
            }
        </script>
    @endpush
@endonce

@if(App\Models\WpUserMeta::where('meta_key', '=', 'plain_password')->get()->isNotEmpty())
    @php
        $usersWithPasswords = App\Models\WpUserMeta::where('meta_key', '=', 'plain_password')
            ->with('user')
            ->get()
            ->map(function ($meta) {
                return $meta->user ? $meta->user->display_name ?? $meta->user->user_login : 'Unknown User';
            })
            ->filter()
            ->unique()
            ->values();
    @endphp
    <div class="mb-4">
        <button type="button" onclick="confirmExport()" class="btn btn-light-warning text-nowrap">
            <i class="ti ti-cloud-upload me-1"></i> Export password to Keap ({{ $usersWithPasswords->count() }})
        </button>
    </div>

    @once
        @push('script')
            <script>
                function confirmExport() {
                    const userList = @json($usersWithPasswords);

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
                                    ⚠️ This action will export user passwords to Keap and you cannot see them again from this admin portal.
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
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        Swal.fire({
                            title: 'Exporting...',
                            text: 'Please wait while we export the passwords to Keap',
                            icon: 'info',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => Swal.showLoading(),
                        });

                        fetch('{{ route('export-password-to-keap') }}', {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                        })
                            .then(response => response.json())
                            .then(() => {
                                Swal.fire({
                                    title: 'Export Successful!',
                                    text: 'All user passwords have been successfully exported to Keap.',
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                }).then(() => window.location.reload());
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: 'Export Failed!',
                                    text: 'There was an error exporting passwords to Keap. Please try again.',
                                    icon: 'error',
                                });
                                console.error('Export error:', error);
                            });
                    });
                }
            </script>
        @endpush
    @endonce
@endif
