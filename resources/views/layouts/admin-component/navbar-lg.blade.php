<div class="lg:p-0 p-5 lg:flex gap-2 items-center">

    @foreach($navbars as $navbar)
        @if($navbar['type'] == 'dropdown')
            <div class="lg:flex items-center">
                <div
                    class="hs-dropdown lg:py-4  [--strategy:static] lg:[--strategy:absolute] [--adaptive:none] sm:[--trigger:hover] relative group/menu">
                    <button type="button"
                            class="header-link-btn group-hover/menu:bg-lightprimary group-hover/menu:text-primary">
                        <i class="ti ti-api-app lg:hidden lg:text-sm text-xl"></i>{{ $navbar['title'] }}
                        <i class="ti ti-chevron-down  ms-auto lg:text-sm text-lg"></i>
                    </button>

                    <div
                        class="hs-dropdown-menu transition-[opacity,margin] duration-[0.1ms] sm:duration-[150ms] hs-dropdown-open:opacity-100 opacity-0 left-0
                     hidden z-10 sm:mt-3 top-full start-0 min-w-[15rem] @if($navbar['app_list_two_side']) lg:w-[800px] @else lg:w-[500px] @endif before:absolute lg:bg-white bg-transparent dark:bg-dark lg:shadow-md shadow-none">
                        <div class="grid grid-cols-12">

                            <div
                                class=" @if($navbar['app_list_two_side']) lg:col-span-4 @else lg:col-span-6  @endif col-span-12 flex items-stretch lg:px-5 lg:pr-0 px-0 py-5">
                                <div class="grid grid-cols-12 lg:gap-3 w-full">

                                    <div class="col-span-12 lg:col-span-12 flex items-stretch">
                                        <ul>@foreach($navbar['app_lists_left'] as $appList)
                                                <li class="mb-5">
                                                    <a href="{{ $appList['route'] }}"
                                                       class="flex gap-3 items-center  group relative">
                                            <span
                                                class="apps-icons">
                                                <img
                                                    src="{{ isset($appList['icon_links']) && $appList['icon_links']!='' ? $appList['icon_links'] : asset('assets/images/svgs/icon-connect.svg') }}"
                                                    class="h-6 w-6">
                                            </span>
                                                        <div class="">
                                                            <h6 class="font-semibold text-sm group-hover:text-primary">
                                                                {{ $appList['title'] }}
                                                            </h6>
                                                            <p class="text-xs">{{ $appList['sub-title'] }}</p>
                                                        </div>

                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @if($navbar['app_list_two_side'])
                                <div
                                    class="lg:col-span-4 col-span-12 flex items-stretch lg:px-5 lg:pr-0 px-0 py-5">
                                    <div class="grid grid-cols-12 lg:gap-3 w-full">

                                        <div class="col-span-12 lg:col-span-12 flex items-stretch">
                                            <ul>@foreach($navbar['app_lists_right'] as $appList)
                                                    <li class="mb-5">
                                                        <a href="{{ $appList['route'] }}"
                                                           class="flex gap-3 items-center  group relative">
                                            <span class="apps-icons">
                                                <img
                                                    src="{{ isset($appList['icon_links']) && $appList['icon_links']!='' ? $appList['icon_links'] : asset('assets/images/svgs/icon-connect.svg') }}"
                                                    class="h-6 w-6">

                                            </span>
                                                            <div class="">
                                                                <h6 class="font-semibold text-sm group-hover:text-primary">
                                                                    {{ $appList['title'] }}
                                                                </h6>
                                                                <p class="text-xs">{{ $appList['sub-title'] }}</p>
                                                            </div>

                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div
                                class="lg:col-span-4 col-span-12  flex items-strech">
                                <div
                                    class="lg:p-5 lg:border-s border-border dark:border-darkborder">
                                    <h5 class="text-xl font-semibold mb-4 text-dark dark:text-white">
                                        Quick Links</h5>
                                    <ul>
                                        @foreach($navbar['quick_links'] as $quick)
                                            <li class="mb-4"><a href="{{ $quick['route'] }}"
                                                                class="card-title text-sm hover:text-primary">
                                                    {{ $quick['title'] }}
                                                </a>
                                            </li>
                                        @endforeach


                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($navbar['type']=='link')
            <div>
                <a href="{{ $navbar['route'] }}"
                   class="header-link-btn dark:hover:text-primary">
                    <i class="ti ti-message-dots lg:hidden lg:text-sm text-xl"></i>{{ $navbar['title'] }}</a>
            </div>
        @endif
    @endforeach
</div>
