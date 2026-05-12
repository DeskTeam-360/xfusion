@php
    $gaugeMax = \App\Livewire\UserDetail::SCORING_GROUP_GAUGE_MAX;
    $zoneRedBelow = \App\Livewire\UserDetail::SCORING_GAUGE_ZONE_RED_BELOW;
    $zoneAmberBelow = \App\Livewire\UserDetail::SCORING_GAUGE_ZONE_AMBER_BELOW;
    $gaugeArcSegments = \App\Livewire\UserDetail::scoringGaugeArcSegmentPaths();
    $needleColor = '#000000';
@endphp

<div class="w-full space-y-10">
    <section class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-lg">Identity</h2>
            <dl class="grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-base-content/60">Name</dt>
                    <dd class="font-medium">{{ $identity['name'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/60">Username</dt>
                    <dd>{{ $identity['login'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/60">Email</dt>
                    <dd>{{ $identity['email'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/60">Role</dt>
                    <dd>{{ $identity['role'] ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-base-content/60">Company</dt>
                    <dd>{{ $identity['company'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-lg">Course progress</h2>
            <p class="text-sm text-base-content/70 mb-4">
                Percentage of completed topics versus published topics per LearnDash course.
            </p>

            @if (count($courseProgress) === 0)
                <p class="text-sm text-base-content/60">No course progress meta (<code>_sfwd-course_progress</code>) for this user.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th class="text-right">Done</th>
                                <th class="w-48">Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courseProgress as $row)
                                <tr>
                                    <td class="font-medium">{{ $row['title'] }}</td>
                                    <td class="text-right whitespace-nowrap text-sm">
                                        {{ $row['completed'] }} / {{ $row['total'] }}
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <progress class="progress progress-primary flex-1" value="{{ $row['percent'] }}" max="100"></progress>
                                            <span class="text-sm tabular-nums w-10 text-right">{{ $row['percent'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-lg">Course scoring groups</h2>
            <p class="text-sm text-base-content/70 mb-3">
                Values come from each user’s latest active Gravity Forms submission per configured form and field.
                The needle shows the <strong>group average</strong> on a <strong>0–{{ (int) $gaugeMax }}</strong> scale (capped at max).
            </p>
            <ul class="text-sm text-base-content/80 mb-4 space-y-1.5 border border-base-200 rounded-lg px-4 py-3 bg-base-200/30">
                <li class="flex gap-2"><span aria-hidden="true">🔴</span> <span><strong class="text-red-600">Red</strong> (1–{{ (int) $zoneRedBelow }}): Needs improvement</span></li>
                <li class="flex gap-2"><span aria-hidden="true">🟡</span> <span><strong class="text-yellow-600">Yellow</strong> ({{ $zoneRedBelow == floor($zoneRedBelow) ? (int) $zoneRedBelow : $zoneRedBelow }}–{{ rtrim(rtrim(number_format($zoneAmberBelow, 2, '.', ''), '0'), '.') }}): Progressing</span></li>
                <li class="flex gap-2"><span aria-hidden="true">🟢</span> <span><strong class="text-green-600">Green</strong> ({{ rtrim(rtrim(number_format($zoneAmberBelow, 2, '.', ''), '0'), '.') }}–{{ (int) $gaugeMax }}): Excellent</span></li>
            </ul>

            @if (count($scoringGroups) === 0)
                <p class="text-sm text-base-content/60">No scoring groups configured, or all groups have no fields.</p>
            @else
                {{-- Compact RPM gauges: 3 per row, centered (including last partial row) --}}
                <div class="mx-auto flex w-full max-w-[29rem] flex-wrap justify-center gap-2 sm:gap-3">
                    @foreach ($scoringGroups as $group)
                        <div class="w-28 shrink-0 rounded-lg border border-base-200 bg-base-100 p-2 flex flex-col items-center text-center shadow-sm sm:w-36">
                            <h3 class="text-[11px] font-semibold leading-tight line-clamp-2 min-h-[2.25rem] w-full mb-1 px-0.5" title="{{ $group['title'] }}">
                                {{ $group['title'] }}
                            </h3>
                            <svg
                                class="w-full max-w-[5.5rem] h-auto max-h-[4.75rem] mx-auto"
                                viewBox="0 0 220 130"
                                role="img"
                                aria-label="{{ $group['title'] }} gauge, 0 to {{ (int) $gaugeMax }}"
                            >
                                @foreach ($gaugeArcSegments as $seg)
                                    <path
                                        d="{{ $seg['d'] }}"
                                        fill="none"
                                        stroke="{{ $seg['stroke'] }}"
                                        stroke-width="10"
                                        stroke-linecap="round"
                                    />
                                @endforeach
                                @for ($i = 0; $i <= 5; $i++)
                                    @php
                                        $theta = pi() * (1 - $i / 5);
                                        $x1 = 110 + 72 * cos($theta);
                                        $y1 = 110 - 72 * sin($theta);
                                        $x2 = 110 + 62 * cos($theta);
                                        $y2 = 110 - 62 * sin($theta);
                                        $lx = 110 + 52 * cos($theta);
                                        $ly = 110 - 52 * sin($theta);
                                    @endphp
                                    <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="#9ca3af" stroke-opacity="0.45" stroke-width="2" stroke-linecap="round"/>
                                    <text
                                        x="{{ $lx }}"
                                        y="{{ $ly + 4 }}"
                                        text-anchor="middle"
                                        fill="#6b7280"
                                        font-size="11"
                                        font-weight="600"
                                    >{{ $i }}</text>
                                @endfor
                                <g transform="rotate({{ number_format($group['gauge_needle_deg'], 2, '.', '') }} 110 110)">
                                    <line
                                        x1="110"
                                        y1="112"
                                        x2="110"
                                        y2="36"
                                        fill="none"
                                        stroke="{{ $needleColor }}"
                                        stroke-width="4"
                                        stroke-linecap="round"
                                    />
                                </g>
                                <circle cx="110" cy="110" r="7" fill="#1f2937"/>
                                <circle cx="110" cy="110" r="4" fill="#ffffff"/>
                            </svg>
                            <div class="mt-0.5 space-y-0 w-full">
                                @if ($group['average'] !== null)
                                    <p class="text-sm font-bold tabular-nums leading-tight">{{ $group['average'] }}</p>
                                    <p class="text-[10px] font-medium leading-tight line-clamp-1" style="color: {{ $group['gauge_needle_color'] }}">{{ $group['gauge_zone_label'] }}</p>
                                @else
                                    <p class="text-[10px] text-base-content/60">—</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Full field breakdown tables --}}
                <div class="mt-10 space-y-8">
                    @foreach ($scoringGroups as $group)
                        <div class="mt-5 mb-5 pt-6 first:pt-0">
                            <h3 class="font-semibold text-base mb-1">{{ $group['title'] }}</h3>
                            @if (! empty($group['description']))
                                <p class="text-sm text-base-content/70 mb-3">{{ $group['description'] }}</p>
                            @endif
                            <div class="overflow-x-auto">
                                <table class="table table-zebra table-sm" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="text-align: left;">Form</th>
                                            <th style="text-align: left;">Field</th>
                                            <th style="text-align: left;">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group['rows'] as $r)
                                            <tr>
                                                <td class="text-sm">{{ $r['form_title'] }}</td>
                                                <td class="text-sm">{{ $r['field_label'] }}</td>
                                                <td class="text-sm">{{ $r['value'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
