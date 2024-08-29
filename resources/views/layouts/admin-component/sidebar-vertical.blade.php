<aside id="application-sidebar-brand"
       class="hs-overlay hs-overlay-open:translate-x-0 -translate-x-full xl:rtl:-translate-x-0 rtl:translate-x-full  left-0 rtl:left-auto rtl:right-0 transform hidden xl:block xl:translate-x-0 xl:end-auto xl:bottom-0 fixed top-0 with-vertical  left-sidebar transition-all duration-300 h-screen xl:z-[2] z-[60] flex-shrink-0 border-r rtl:border-l rtl:border-r-0 w-[270px] border-border dark:border-darkborder bg-white dark:bg-dark">
    <!-- ---------------------------------- -->
    <!-- Start Vertical Layout Sidebar -->
    <!-- ---------------------------------- -->
    <div class="py-5 px-5 flex justify-between">
        <div class="brand-logo flex  items-center ">
            <a href="{{ route('dashboard') }}"
               class="text-nowrap logo-img">
                <img
                    src="{{ $logoDark }}"
                    class="dark:hidden block rtl:hidden"
                    alt="Logo-Dark"
                />
                <img
                    src="{{ $logoLight }}"
                    class="dark:block hidden rtl:hidden rtl:dark:hidden"
                    alt="Logo-light"
                />

                <img
                    src="{{ $logoDark }}"
                    class="dark:hidden hidden rtl:block rtl:dark:hidden"
                    alt="Logo-Dark"
                />
                <img
                    src="{{ $logoLight }}"
                    class="dark:hidden hidden rtl:hidden rtl:dark:block"
                    alt="Logo-light"
                />
            </a>
        </div>

    </div>
    <div class="overflow-hidden">
        <div class="scroll-sidebar" data-simplebar="">
            <div class="px-6 mt-8 mini-layout" data-te-sidenav-menu-ref>
                <nav class="hs-accordion-group w-full flex flex-col">
                    <ul data-te-sidenav-menu-ref id="sidebarnav">
                        <!---Dashboard Menu---->

                        @foreach($sidebar as $menuHeader)
                                <div
                                    class="caption">
                                    <i class="ti ti-dots nav-small-cap-icon "></i>
                                    <span class="hide-menu">{{ $menuHeader['title'] }}</span>
                                </div>
                                @foreach($menuHeader['lists'] as $menu)
                                    @if($menu['type']=="link")
                                        <li class="sidebar-item">
                                            <a class="sidebar-link dark-sidebar-link {{ false?' active activemenu dark:text-white':'' }}"
                                               href="{{ $menu['route'] }}">
                                                {!! $menu['icon'] !!}
                                                <span class="hide-menu flex-shrink-0">{{ $menu['title'] }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if($menu['type']=="accordion")
                                        <li class="hs-accordion sidebar-item">
                                            <a
                                                class="hs-accordion-toggle sidebar-link dropdown-menu-link  ">
                                                {!! $menu['icon'] !!}
                                                <span
                                                    class="hide-menu">{{ $menu['title'] }}</span>
                                                <span class="hide-menu ms-auto">
              <i
                  class="ti ti-chevron-down text-lg ms-auto  hs-accordion-active:hidden "></i>
              <i
                  class="ti ti-chevron-up text-lg ms-auto hs-accordion-active:block ml-auto hidden z-10 relative"></i>
            </span>
                                            </a>

                                            <div id="form-elements"
                                                 class="hs-accordion-content ">
                                                <ul class>
                                                    @foreach($menu['lists'] as $list)
                                                        <li class="pl-4 pr-3">
                                                            <a
                                                                class="dropdown-submenu-link "
                                                                href="{{ $list['route'] }}">
                                                                {!! $list['icon'] !!}

                                                                <span class="hide-menu">{{ $list['title'] }}</span>
                                                            </a>
                                                        </li>
                                                    @endforeach



                                                </ul>
                                            </div>
                                        </li>
                                    @endif
                                @endforeach


                        @endforeach


                    </ul>
                </nav>
            </div>
        </div>
    </div>
</aside>
