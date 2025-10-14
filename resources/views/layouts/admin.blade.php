<!DOCTYPE html>
<html lang="en" dir="ltr" data-color-theme="Blue_Theme" class="light selected" data-layout="vertical"
    data-boxed-layout="boxed" data-card="shadow">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-ZCN2fU2XFD8wYg5Q9w77p8xJZXsU23Z4A1x02vQs7v3/6aiyA1BcIqXSy3EfrK1kQ2EJRo7ar4+o2pxZlYrElw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/logos/favicon.png') }}" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap"
        rel="stylesheet" />
    {{--    <link rel="stylesheet" href="{{asset('assets/fonts/icons/tabler-icons/tabler-icons.css')}}"> --}}
    <link rel="stylesheet" href="{{ asset('assets/icons-webfont/tabler-icons.min.css') }}">
    <!-- Core Css -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
{{--    <link rel="stylesheet" href="{{ asset('build/assets/app-DdqVIPGz.css') }}">--}}
{{--    <script src="{{ asset('build/assets/app-CI1Bgkaz.js') }}"></script>--}}
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v=2">


    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/carousel/carousel.min.css') }}">
    @livewireStyles

    <style>
        .logo-img img {
            max-width: 174px;
        }
    </style>

</head>

<body class="DEFAULT_THEME bg-white dark:bg-dark">

    <main>
        <!--start the project-->
        <div id="main-wrapper" class="flex">
            @include('layouts.admin-component.sidebar-vertical')

            <!-- </aside> -->
            <div class="page-wrapper w-full" role="main">
                <!--  Header Start -->
                @include('layouts.admin-component.header')
                <!--  Header End -->

                <!-- Horizontal Header Menu -->
                @include('layouts.admin-component.sidebar-horizontal')
                <!-- Horizontal Header Menu End -->

                <!-- Main Content -->
                <div class=" max-w-full pt-6">
                    {{ $slot }}
                </div>
                <!-- Main Content End -->
            </div>
        </div>
        <!--end of project-->

    </main>
    @include('layouts.admin-component.toast')
    <!-- Menu Canvas-->
    @include('layouts.admin-component.navbar-md')




    <!------- Customizer button--------->
    <button type="button"
        class="btn overflow-hidden  sm:h-14 sm:w-14 h-10 w-10 rounded-full fixed sm:bottom-8 bottom-5 right-8 flex justify-center items-center rtl:left-8 rtl:right-auto z-10"
        data-hs-overlay="#hs-overlay-right">
        <i class="ti ti-settings sm:text-2xl text-lg text-white"></i>
    </button>

    <!------- Customizer Options--------->
    @include('layouts.admin-component.settings')

    <script>
        function handleColorTheme(e) {
            console.log(e)
            document.documentElement.setAttribute("data-color-theme", e);
            localStorage.setItem('ColorTheme', e)
            console.log(localStorage.getItem('ColorTheme'))
        }
    </script>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="{{ asset('assets/js/theme.js') }}"></script>
    <script src="{{ asset('assets/js/theme/app.init.js') }}"></script>
    <script src="{{ asset('assets/js/theme/app.min.js') }}"></script>

    <script src="{{ asset('assets/libs/simplebar/dist/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/dist/preline.js') }}"></script>
    <script src="{{ asset('assets/libs/@preline/input-number/index.js') }}"></script>
    <script src="{{ asset('assets/libs/@preline/tooltip/index.js') }}"></script>
    <script src="{{ asset('assets/libs/@preline/stepper/index.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/dist/components/hs-accordion/hs-accordion.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/dist/components/hs-collapse/hs-collapse.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/dist/components/hs-dropdown/hs-dropdown.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/dist/components/hs-overlay/hs-overlay.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/dist/components/hs-remove-element/hs-remove-element.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/dist/components/hs-scrollspy/hs-scrollspy.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/dist/components/hs-tabs/hs-tabs.js') }}"></script>
    <script src="{{ asset('assets/libs/preline/dist/components/hs-tooltip/hs-tooltip.js') }}"></script>
    <script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script> --}}
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="//cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css"></script>
    <script src="https://cdn.jsdelivr.net/gh/fcmam5/nightly.js@v1.0/dist/nightly.min.js"></script>

    @if(session('swal'))
        <script>
            Swal.fire({
                title: '{{ session('swal.title') }}',
                text: '{{ session('swal.message') ?? "" }}',
                icon: '{{ session('swal.icon') ?? "success" }}',
                timer: {{ session('swal.timeout') ?? 3000 }},
                showConfirmButton: false,
                timerProgressBar: true,
            });
        </script>
    @endif

    @livewireScripts
    <script>
        const SwalModal = (icon, title, html) => {
            Swal.fire({
                icon,
                title,
                html
            })
        }

        const SwalConfirm = (icon, title, html, confirmButtonText, method, params, callback) => {
            Swal.fire({
                icon,
                title,
                html,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText,
                reverseButtons: true,
            }).then(result => {
                if (result.value) {
                    // $wire.dispatch('post-created', { refreshPosts: true });
                    return Livewire.dispatch(method, params)
                }

                if (callback) {
                    return Livewire.dispatch(callback)
                }
            })
        }

        const SwalAlert = (icon, title, timeout = 2000) => {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: timeout,
                onOpen: toast => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })

            Toast.fire({
                icon,
                title
            })
        }

        document.querySelectorAll("#dark-layout").forEach((element) => {
            element.addEventListener("click", () => {
                var ss = document.createElement('link');
                ss.rel = "stylesheet";
                ss.href = "//cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css";
                ss.id = "dark-alert"
                document.head.appendChild(ss);
                document.getElementById("light-alert").remove();
            });
        });

        document.querySelectorAll("#light-layout").forEach((element) => {
            element.addEventListener("click", () => {
                document.getElementById("dark-alert").remove();
            });
        });

        // Function to show password in alert with copy functionality
        function showPassword(password) {
            Swal.fire({
                title: 'User Password',
                html: `
                    <div style="text-align: center;">
                        <p style="font-size: 18px; margin-bottom: 20px;">Password:</p>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 20px;">
                            <code id="passwordText" style="font-size: 16px; font-weight: bold; color: #495057;">${password}</code>
                        </div>
                        <button onclick="copyPassword('${password}')" class="btn btn-primary" style="margin-right: 10px;">
                            <i class="ti ti-copy"></i> Copy Password
                        </button>
                    </div>
                `,
                showConfirmButton: true,
                confirmButtonText: 'Close',
                confirmButtonColor: '#6c757d',
                width: '400px'
            });
        }

        // Function to copy password to clipboard
        function copyPassword(password) {
            navigator.clipboard.writeText(password).then(function() {
                Swal.fire({
                    title: 'Copied!',
                    text: 'Password copied to clipboard',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }).catch(function(err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = password;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                Swal.fire({
                    title: 'Copied!',
                    text: 'Password copied to clipboard',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            var ss = document.createElement('link');
            ss.rel = "stylesheet";
            if (localStorage.getItem("Theme") == "dark") {
                ss.id = "dark-alert"
                ss.href = "//cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css";
            } else {
                ss.id = "light-alert"
                // ss.href = "//cdn.jsdelivr.net/npm/@sweetalert2/theme-minimal/minimal.css";
            }
            document.head.appendChild(ss);

            window.addEventListener('swal:modal', function(d) {
                const data = d.__livewire.params.data;
                SwalModal(data.icon, data.title, data.text)
            })

            window.addEventListener('swal:confirm', function(d) {
                const data = d.__livewire.params.data;
                SwalConfirm(data.icon, data.title, data.text, data.confirmText, data.method, data.params,
                    data.callback)
            })

            window.addEventListener('swal:alert', function(d) {
                const data = d.__livewire.params.data;
                SwalAlert(data.icon, data.title, data.timeout)
            })

            window.addEventListener('swal:redirect:new-tab', function(d) {
                const data = d.__livewire.params.data;
                window.open(data.url, '_blank');
            })

            window.addEventListener('refreshPage', function(d) {
                location.reload();
            })
        })
    </script>
@stack('script')
</body>

</html>
