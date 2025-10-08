{{--@extends('layouts.app')--}}

{{--@section('content')--}}
{{--<div class="container">--}}
{{--    <div class="row justify-content-center">--}}
{{--        <div class="col-md-8">--}}
{{--            <div class="card">--}}
{{--                <div class="card-header">{{ __('Login') }}</div>--}}

{{--                <div class="card-body">--}}
{{--                    <form method="POST" action="{{ route('login') }}">--}}
{{--                        @csrf--}}

{{--                        <div class="row mb-3">--}}
{{--                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>--}}

{{--                            <div class="col-md-6">--}}
{{--                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>--}}

{{--                                @error('email')--}}
{{--                                    <span class="invalid-feedback" role="alert">--}}
{{--                                        <strong>{{ $message }}</strong>--}}
{{--                                    </span>--}}
{{--                                @enderror--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div class="row mb-3">--}}
{{--                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>--}}

{{--                            <div class="col-md-6">--}}
{{--                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">--}}

{{--                                @error('password')--}}
{{--                                    <span class="invalid-feedback" role="alert">--}}
{{--                                        <strong>{{ $message }}</strong>--}}
{{--                                    </span>--}}
{{--                                @enderror--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div class="row mb-3">--}}
{{--                            <div class="col-md-6 offset-md-4">--}}
{{--                                <div class="form-check">--}}
{{--                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>--}}

{{--                                    <label class="form-check-label" for="remember">--}}
{{--                                        {{ __('Remember Me') }}--}}
{{--                                    </label>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div class="row mb-0">--}}
{{--                            <div class="col-md-8 offset-md-4">--}}
{{--                                <button type="submit" class="btn btn-primary">--}}
{{--                                    {{ __('Login') }}--}}
{{--                                </button>--}}

{{--                                @if (Route::has('password.request'))--}}
{{--                                    <a class="btn btn-link" href="{{ route('password.request') }}">--}}
{{--                                        {{ __('Forgot Your Password?') }}--}}
{{--                                    </a>--}}
{{--                                @endif--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </form>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}
{{--@endsection--}}

    <!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
          rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
    <!-- Core Css -->
    {{--    @vite(['resources/css/app.css', 'resources/js/app.js'])--}}
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('build/assets/app-DdqVIPGz.css') }}">
    <script src="{{ asset('build/assets/app-CI1Bgkaz.js') }}"></script>

    {{--    @vite(['resources/css/app.css', 'resources/js/app.js'])--}}
    <title>{{ config('app.name', 'Laravel') }}</title>
</head>

<body class="DEFAULT_THEME bg-white">
<main class=" mt-5 ">
    <!-- Main Content -->
{{--        <div class="col-span-3"></div>--}}
    <div
        class="flex flex-col w-12/12  overflow-hidden relative min-h-screen radial-gradient items-center justify-center g-0 px-4">

        <div class="justify-center items-center w-8/12 card lg:flex max-w-md  ">
            <div class=" w-full card-body shadow-xl rounded-2xl border-2">
                <a href="../" class="py-4 block">
                    <img src="{{ asset('assets/images/logos/dark-logo.webp') }}" alt=""
                         class="mx-auto"/></a>
                <p class="mb-4 text-gray-500 text-sm text-center"><b><i>Level-Up</i> Employee</b>
                    Performance to Drive
                    <b>company <i>Growth</i></b></p>
                <!-- form -->
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- username -->
                    <div class="mb-4">
                        <label for="forUsername"
                               class="block text-sm font-semibold mb-2 text-gray-600">Email</label>
                        <input type="text" id="email"
                               name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                               class="py-3 px-4 block w-full border-gray-200 rounded-md text-sm focus:border-blue-600  "
                               aria-describedby="hs-input-helper-text">
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                                                                <strong>{{ $message }}</strong>
                                                            </span>
                        @enderror
                    </div>
                    <!-- password -->
                    <div class="mb-6 relative">
                        <label for="forPassword" class="block text-sm font-semibold mb-2 text-gray-600">Password</label>
                        <input type="password" id="forPassword" name="password" required autocomplete="current-password"
                               class="py-3 px-4 block w-full border-gray-200 rounded-md text-sm focus:border-blue-600 @error('password') border-error @enderror"
                               aria-describedby="hs-input-helper-text">

                        <!-- Toggle icon -->
                        <button type="button" id="togglePassword"
                                class="absolute right-4 transform -translate-y-1/2 text-gray-600 focus:outline-none" style="top: 52px">
                            <!-- Eye icon (default) -->
{{--                            <i class="fa fa-eye"></i>--}}
                            <i id="eyeIcon" class="ti ti-eye  text-xl flex-shrink-0"></i>
                            <i id="eyeOffIcon" class="ti ti-eye-closed  text-xl flex-shrink-0 hidden"></i>
{{--                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"--}}
{{--                                 viewBox="0 0 24 24" stroke="currentColor">--}}
{{--                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"--}}
{{--                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />--}}
{{--                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"--}}
{{--                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />--}}
{{--                            </svg>--}}

{{--                            <!-- Eye off icon (hidden initially) -->--}}
{{--                            <svg id="eyeOffIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none"--}}
{{--                                 viewBox="0 0 24 24" stroke="currentColor">--}}
{{--                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"--}}
{{--                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a10.052 10.052 0 012.258-3.592M9.88 9.88a3 3 0 104.24 4.24M15 12a3 3 0 00-3-3m0 0L3 3m18 18l-1.5-1.5" />--}}
{{--                            </svg>--}}
                        </button>

                            
                            @php
                                $forgotUrl = 'https://sandbox.xperiencefusion.com/wp-login.php?action=lostpassword';
                                if (app()->environment('PRODUCTION')) {
                                    $forgotUrl = str_replace('sandbox.', '', $forgotUrl);
                                }
                                @endphp
                                <a class="btn btn-link mt-2 block text-sm text-blue-600" href="{{ $forgotUrl }}">
                                {{ __('Forgot Your Password?') }}
                            </a>
                    
                    </div>

                    <script>
                        const togglePassword = document.getElementById('togglePassword');
                        const passwordField = document.getElementById('forPassword');
                        const eyeIcon = document.getElementById('eyeIcon');
                        const eyeOffIcon = document.getElementById('eyeOffIcon');

                        togglePassword.addEventListener('click', function () {
                            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                            passwordField.setAttribute('type', type);
                            eyeIcon.classList.toggle('hidden');
                            eyeOffIcon.classList.toggle('hidden');
                        });
                    </script>

                    <!-- checkbox -->
                    <div class="flex justify-between">

                        <div class="flex">
                            <input type="checkbox" name="remember" id="remember"
                                   class="shrink-0 mt-0.5 border-gray-200 rounded-[4px] text-blue-600 focus:ring-blue-500 " {{ old('remember') ? 'checked' : '' }}>
                            <label for="hs-default-checkbox" class="text-sm text-gray-600 ms-3">Remember this
                                Device</label>
                        </div>
                        
                            @php
                                $forgotUrl = 'https://sandbox.xperiencefusion.com/wp-login.php?action=lostpassword';
                                if (app()->environment('PRODUCTION')) {
                                    $forgotUrl = str_replace('sandbox.', '', $forgotUrl);
                                }
                                @endphp
                                <a href="{{ $forgotUrl }}"
                                   class="text-sm font-medium text-blue-600 hover:text-blue-700">Forgot Password ?</a>

                        


                    </div>
                    <!-- button -->
                    <div class="grid my-6">
                        <button type="submit"
                                class="text-center rounded-lg py-[10px] text-base bg-blue-600 hover:bg-blue-700 text-white font-medium ">
                            Sign
                            In
                        </button>
                    </div>

                    {{--                    <div class="flex justify-center gap-2 items-center">--}}
                    {{--                        <p class="text-base font-medium text-gray-500">New to Modernize?</p>--}}
                    {{--                        <a href="./authentication-register.html" class="text-sm font-medium text-blue-600 hover:text-blue-700">Create an account</a>--}}
                    {{--                    </div>--}}
                </form>
            </div>
        </div>
    </div>

    </div>
    <!--end of project-->
</main>


<script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('assets/libs/simplebar/dist/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/libs/iconify-icon/dist/iconify-icon.min.js') }}"></script>
<script src="{{ asset('assets/libs/@preline/dropdown/index.js') }}"></script>
<script src="{{ asset('assets/libs/@preline/overlay/index.js') }}"></script>
<script src="{{ asset('assets/js/sidebarmenu.js') }}"></script>


</body>

</html>
