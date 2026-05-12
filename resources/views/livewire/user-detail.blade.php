<div class="w-full space-y-10">
    <section class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-lg">Identitas</h2>
            <dl class="grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-base-content/60">Nama</dt>
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
                    <dt class="text-xs text-base-content/60">Peran</dt>
                    <dd>{{ $identity['role'] ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-base-content/60">Perusahaan</dt>
                    <dd>{{ $identity['company'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-lg">Progress per course</h2>
            <p class="text-sm text-base-content/70 mb-4">
                Persentase dari topik yang selesai dibanding total topik terbit di setiap course (LearnDash).
            </p>

            @if (count($courseProgress) === 0)
                <p class="text-sm text-base-content/60">Belum ada data progress course (<code>_sfwd-course_progress</code>) untuk user ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th class="text-right">Selesai</th>
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
            <h2 class="card-title text-lg">Course scoring group</h2>
            <p class="text-sm text-base-content/70 mb-4">
                Nilai diambil dari entri Gravity Forms terbaru (status aktif) per form & field yang dikonfigurasi di grup scoring.
            </p>

            @if (count($scoringGroups) === 0)
                <p class="text-sm text-base-content/60">Belum ada grup scoring yang dikonfigurasi.</p>
            @else
                <div class="space-y-8">
                    @foreach ($scoringGroups as $group)
                        <div class="border-t border-base-200 pt-6 first:border-t-0 first:pt-0">
                            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
                                <h3 class="font-semibold text-base">{{ $group['title'] }}</h3>
                                @if ($group['average'] !== null)
                                    <span class="badge badge-neutral">Rata-rata angka: {{ $group['average'] }}</span>
                                @endif
                            </div>
                            @if (! empty($group['description']))
                                <p class="text-sm text-base-content/70 mb-3">{{ $group['description'] }}</p>
                            @endif

                            <div class="overflow-x-auto">
                                <table class="table table-zebra table-sm">
                                    <thead>
                                        <tr>
                                            <th>Form</th>
                                            <th>Field</th>
                                            <th>Nilai</th>
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
