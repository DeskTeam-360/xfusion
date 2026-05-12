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
            <p class="text-xs text-base-content/50 mb-4">Band thresholds are defined in <code class="text-xs">UserDetail::SCORING_GAUGE_ZONE_RED_BELOW</code> ({{ $zoneRedBelow }}) and <code class="text-xs">SCORING_GAUGE_ZONE_AMBER_BELOW</code> ({{ $zoneAmberBelow }}). The coloured arc uses 0–{{ $zoneRedBelow }} / {{ $zoneRedBelow }}–{{ $zoneAmberBelow }} / {{ $zoneAmberBelow }}–{{ $gaugeMax }}.</p>

            @if (count($scoringGroups) === 0)
                <p class="text-sm text-base-content/60">No scoring groups configured, or all groups have no fields.</p>
            @else
                <div class="space-y-10">
                    @foreach ($scoringGroups as $group)
                        <div class="border-t border-base-200 pt-8 first:border-t-0 first:pt-0">
                            <div class="flex flex-col lg:flex-row flex-wrap items-start gap-8">
                                <div class="flex-1 min-w-[16rem]">
                                    <h3 class="font-semibold text-base mb-1">{{ $group['title'] }}</h3>
                                    @if (! empty($group['description']))
                                        <p class="text-sm text-base-content/70 mb-4">{{ $group['description'] }}</p>
                                    @endif

                                    <div class="overflow-x-auto">
                                        <table class="table table-zebra table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Form</th>
                                                    <th>Field</th>
                                                    <th>Value</th>
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

                                {{-- RPM-style semicircular gauge (0 → max) --}}
                                <div class="shrink-0 flex flex-col items-center w-full max-w-[14rem] sm:w-[14rem]">
                                    <span class="text-xs font-medium uppercase tracking-wide text-base-content/50 mb-1">Group average</span>
                                    <svg class="w-full h-auto max-h-[9.5rem]" viewBox="0 0 220 130" role="img" aria-label="Scoring group average gauge, 0 to {{ (int) $gaugeMax }}">
                                        {{-- Zone-coloured arc (parameters: SCORING_GAUGE_ZONE_*) --}}
                                        @foreach ($gaugeArcSegments as $seg)
                                            <path
                                                d="{{ $seg['d'] }}"
                                                fill="none"
                                                stroke="{{ $seg['stroke'] }}"
                                                stroke-width="10"
                                                stroke-linecap="round"
                                            />
                                        @endforeach
                                        {{-- Tick marks 0–5 --}}
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
                                            <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" class="stroke-base-content/35" stroke-width="2" stroke-linecap="round"/>
                                            <text
                                                x="{{ $lx }}"
                                                y="{{ $ly + 4 }}"
                                                text-anchor="middle"
                                                class="fill-base-content/70 text-[11px] font-medium"
                                            >{{ $i }}</text>
                                        @endfor
                                        {{-- Needle (explicit stroke — Tailwind stroke-* often omitted for SVG in Blade) --}}
                                        <g transform="rotate({{ number_format($group['gauge_needle_deg'], 2, '.', '') }} 110 110)">
                                            <line
                                                x1="110" y1="112" x2="110" y2="36"
                                                fill="none"
                                                stroke="{{ $needleColor }}"
                                                stroke-width="4"
                                                stroke-linecap="round"
                                            />
                                        </g>
                                        <circle cx="110" cy="110" r="7" fill="#1f2937"/>
                                        <circle cx="110" cy="110" r="4" fill="#ffffff"/>
                                    </svg>
                                    <div class="text-center -mt-1 space-y-0.5">
                                        @if ($group['average'] !== null)
                                            <p class="text-lg font-bold tabular-nums">
                                                {{ $group['average'] }}
                                                @if ($group['average'] > $gaugeMax + 0.0001)
                                                    <span class="text-xs font-normal text-base-content/60">(capped on gauge)</span>
                                                @endif
                                            </p>
                                            <p class="text-sm font-semibold" style="color: {{ $group['gauge_needle_color'] }}">{{ $group['gauge_zone_label'] }}</p>
                                            <p class="text-xs text-base-content/60">Mean of numeric fields · scale 0–{{ (int) $gaugeMax }}</p>
                                        @else
                                            <p class="text-sm text-base-content/60">No numeric values in this group</p>
                                            <p class="text-xs text-base-content/50">Needle at minimum</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
