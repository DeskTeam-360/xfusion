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
                <!-- form -->
                <form method="POST" action="{{ route('password.email') }}">
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

                    <!-- button -->
                    <div class="grid my-6">
                        <button type="submit"
                                class="text-center rounded-lg py-[10px] text-base bg-blue-600 hover:bg-blue-700 text-white font-medium ">
                            Send Password Reset Link
                        </button>
                    </div>

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
