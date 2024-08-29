<aside class="with-horizontal hidden lg:flex ">
    <nav class="relative border-b border-border dark:border-darkborder py-4">
        <div class="container">
            <div class="flex gap-1 items-center relative">
                <!-- Dropdown Menu / Multilevel -->

                @foreach($sidebar as $menuHeader)

                    <div
                        class="hs-dropdown  [--strategy:static] lg:[--strategy:absolute] [--adaptive:none] sm:[--trigger:hover] relative">
                        <button type="button"
                                class="horizontal-menu {{ false?'bg-primary text-white dark:text-white hover:bg-primary hover:text-white':'' }}">
                            @isset($menuHeader['icon'])
                                {!! $menuHeader['icon'] !!}
                            @endisset
                            {{--                            <i class="ti ti-home-2 text-base"></i>--}}
                            {{ $menuHeader['title'] }} <i class="ti ti-chevron-down ms-auto text-lg"></i>

                        </button>
                        <div
                            class="horizontal-items hs-dropdown-menu before:absolute left-0 hidden transition-[opacity,margin] duration-[0.1ms] sm:duration-[150ms] hs-dropdown-open:opacity-100 opacity-0 ">
                            @foreach($menuHeader['lists'] as $menu)

                                @if($menu['type']=="link")
                                    <a class="horizontal-link {{ false?'active horizontal-link-active':'' }}"
                                       href="{{ $menu['route'] }}">
                                        {!! $menu['icon'] !!}

                                        <span
                                            class="hide-menu flex-shrink-0 text-sm leading-tight">{{ $menu['title'] }}</span>
                                    </a>
                                @endif

                                @if($menu['type']=="accordion")
                                        <div
                                        class="hs-dropdown relative [--strategy:static] sm:[--strategy:absolute] [--adaptive:none]">
                                        <button type="button"
                                                class="horizontal-link w-full ">
                                            {!! $menu['icon'] !!}
                                            {{ $menu['title'] }}
                                            <i class="ti ti-chevron-right ms-auto text-md"></i>
                                        </button>
                                        <div
                                            class="py-4 px-3 hs-dropdown-menu transition-[opacity,margin] duration-[0.1ms] sm:duration-[150ms] hs-dropdown-open:opacity-100 opacity-0  min-w-64 hidden z-10 sm:mt-2  before:absolute  before:-start-5 before:top-0 before:h-full before:w-5 top-0 start-full !mx-[10px]">
                                            @foreach($menu['lists'] as $list)
                                                <a class="horizontal-link"
                                                   href="{{ $list['route'] }}">
                                                    {!! $list['icon'] !!}
                                                    {{ $list['title'] }}
                                                </a>

                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </nav>
</aside>
