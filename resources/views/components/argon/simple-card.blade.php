@props([
	'title'=> 'No title',
	'value'=>'Rp. 0',
	'fluctuation'=>'none',
	'fluctuationValue' =>'0%',
	'fluctuationNote' =>'',
	'icon' => 'fa-solid fa-circle-question',
	'iconBackground'=> 'bg-gradient-to-tl from-blue-500 to-violet-500'
])
<div class="w-full max-w-full px-3 mb-6 sm:w-1/2 sm:flex-none xl:mb-0 xl:w-1/4">
    <div
        class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
        <div class="flex-auto p-4">
            <div class="flex flex-row -mx-3">
                <div class="flex-none w-2/3 max-w-full px-3">
                    <div>
                        <p class="mb-0 font-sans text-sm font-semibold leading-normal uppercase dark:text-white dark:opacity-60">
                            {{ $title }}
                        </p>
                        <h5 class="mb-2 font-bold dark:text-white">{{ $value }}</h5>

                    </div>
                </div>
                <div class="px-3 text-right basis-1/3">
                    <div class="inline-block w-12 h-12 text-center rounded-circle {{ $iconBackground }} text-2xl"
                         style="display:inline-block; line-height: 50px; width: 50px; height: 50px; text-align: center; vertical-align: bottom;">
                        <i class="{{ $icon }} text-white"></i>
                    </div>
                </div>

            </div>

            <p class="mb-0 dark:text-white dark:opacity-60">
                            <span class="text-sm font-bold leading-normal
                            @if($fluctuation=="increase") text-emerald-500 @endif
                            @if($fluctuation=="decrease") text-red-600 @endif
                            @if($fluctuation=="flat") text-gray-400 @endif
                            ">
                                @if($fluctuation!="none")
                                @if($fluctuation=="increase")
                                    +
                                @endif
                                @if($fluctuation=="decrease")
                                    -
                                @endif{{  $fluctuationValue }}</span>
                {{ $fluctuationNote }}
                @else
                    <br>
                @endif
            </p>

        </div>
    </div>
</div>
