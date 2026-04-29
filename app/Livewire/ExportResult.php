<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\CourseList;
use App\Models\CourseGroup;

use App\Models\CompanyEmployee;
use App\Models\CourseGroupDetail;
use App\Models\User;
use App\Models\WpUserMeta;
use App\Models\WpGfEntryMeta;
use App\Models\WpGfFormMeta;
use App\Models\WpPost;
use App\Models\WpPostMeta;
use Illuminate\Support\Str;
use Livewire\Component;

class ExportResult extends Component
{
    public bool $isCompanyDashboard = false;

    public ?int $lockedCompanyId = null;

    /** Single native select; syncs hidden course lists from group. */
    public string $dashboardCourseGroupId = '';

    public $results=[];
    public $title;
    public $form_ids=[];
    public $field_target=[];
    public $users = [];
    public $userLists = [];
    public $companies=[];
    public $typeUser=[];
    public $courseLists=[];
    public $fields=[];
    public $optionUsers = [];
    public $optionTypeUser=[];
    public $optionCompanies = [];
    public $optionFields = [];
    public $optionCourseTitle = [];

    public $optionCourseGroupLists = [];
    public $optionCourseLists2 = [];

    public $courseGroupLists = [];
    public $courseLists2 = [];
    public $headerFormat="[clean_course_title] - [clean_question]";

    public $headerFormatPivot1='Clean';
    public $headerFormatPivot2='Clean';
    public $headerFormatPivotOption=['Full', 'Clean'];
    public $table=0;

    /** @var array<int, string> */
    public array $workTypeByUser = [];

    /** @var array<int, array{complete: int, pct: float|null, avg_score: float|null}> */
    public array $userRowStats = [];

    /** @var list<array{form_id: int|string, field_id: mixed, label: string, form_title: string, participation_count: int, participation_rate: float|null, avg_assessment: float|null}> */
    public array $activityFooterStats = [];

    public int $totalActivitiesCount = 0;

    public ?float $grandAvgActivityAssessment = null;

    /** Users with ≥1 answered activity vs users with none (overall). */
    public array $chartUserParticipationPie = [
        'participating' => 0,
        'non_participating' => 0,
        'pct' => 0.0,
    ];

    /** Same counts, grouped by work_type meta. */
    public array $chartUserParticipationPieByWorkType = [];

    /** @var list<array{label: string, full_label: string, axis_label: string, count: int, width_pct: float, color: string}> */
    public array $chartParticipationBar = [];

    /** Pastel bar colors (Terra-style horizontal chart). */
    private const CHART_BAR_PASTELS = [
        '#93c5fd', '#86efac', '#fca5a5', '#d8b4fe', '#fcd34d', '#67e8f9',
        '#fdba74', '#a5b4fc', '#f9a8d4', '#5eead4', '#c4b5fd', '#bef264',
        '#fda4af', '#94a3b8',
    ];

    public function mount($lockedCompanyId = null, $isCompanyDashboard = null): void
    {
        // Livewire hydrates :locked-company-id / :is-company-dashboard onto public props before mount().
        // Mount is often called with (null, null); assigning from those defaults would wipe hydrated values.
        if ($lockedCompanyId !== null && $lockedCompanyId !== '') {
            $this->lockedCompanyId = (int) $lockedCompanyId;
        }
        if ($isCompanyDashboard !== null) {
            $this->isCompanyDashboard = is_bool($isCompanyDashboard)
                ? $isCompanyDashboard
                : (bool) filter_var($isCompanyDashboard, FILTER_VALIDATE_BOOLEAN);
        }

        $this->optionTypeUser = [
            ['value' => 'users', 'title' => 'Users'],
            ['value' => 'companies', 'title' => 'companies'],
        ];
        
        foreach (CourseGroup::get() as $cg){
            
            $this->optionCourseLists2[$cg->id] = $cg->courseGroupDetails->pluck('course_list_id')->toArray();
            $this->optionCourseGroupLists[] = ['value'=>$cg->id, 'title'=>$cg->title.' - '.$cg->sub_title .' ('.count($cg->courseGroupDetails).')'];
        }
        foreach (CourseList::get() as $cl){
            $this->optionCourseTitle[] = ['value'=>$cl->id, 'title'=>$cl->course_title.' - '.$cl->page_title];
        }

        if (! $this->isCompanyDashboard) {
            foreach (User::get() as $user) {
                $this->optionUsers[] = ['value' => $user->ID, 'title' => $user->first_name . ' ' . $user->last_name];
            }
            foreach (Company::get() as $user) {
                $this->optionCompanies[] = ['value' => $user->id, 'title' => $user->title];
            }
        }

        $this->optionFields=['text', 'checkbox', 'number', 'select', 'multiselect', 'radio', 'email', 'name','textarea'];

        if ($this->isCompanyDashboard) {
            // Match full report field types; "radio only" leaves most GF forms with zero columns.
            $this->fields = ['text', 'checkbox', 'number', 'select', 'multiselect', 'radio', 'email', 'name', 'textarea'];
        }
    }

    public function updatedDashboardCourseGroupId(): void
    {
        if (! $this->isCompanyDashboard) {
            return;
        }
        $gid = $this->dashboardCourseGroupId !== '' ? (int) $this->dashboardCourseGroupId : 0;
        if ($gid <= 0) {
            $this->courseGroupLists = [];
            $this->courseLists = [];

            return;
        }
        $this->courseGroupLists = [$gid];
        $raw = $this->optionCourseLists2[$gid] ?? [];
        $ids = is_array($raw) ? $raw : $raw->toArray();
        $this->courseLists = array_values(array_unique(array_map('intval', $ids)));
    }

    public function getHeaderFormat($course_title, $question)
    {   
        $headerFormat = $this->headerFormat;
        $clean_question = $question;
        $clean_course_title = $course_title;

        // Phrases stripped from labels (human-readable headers)
        $blacklist = [
            'Rate your current ability to',
            '1-5 (5 highest)',
            '1-5 (5 highest).',
            'with yourself and others',
            'Rate your current level of',
            'Rate your ability to',
            'Rate the current status of',
            'Rate your current practices for maintaining',
        'Rate your',
        '(1-5-highest)',
        '(1-5-highest).',
        '(1-5-highest)',
        'Course - Topic : ',
        'Course - Topic'
        ];
        // Remove quotes and stray punctuation
        $clean_question = str_replace(["'", '"','“','”',' .','.'], '', $clean_question);
        $clean_course_title = str_replace(["'", '"','“','”',' .','.'], '', $clean_course_title);

        $clean_question = str_ireplace($blacklist, '', $clean_question);
        $clean_course_title = str_ireplace($blacklist, '', $clean_course_title);    

        $clean_question = preg_replace('/\s+/', ' ', $clean_question);
        $clean_course_title = preg_replace('/\s+/', ' ', $clean_course_title);
        $clean_question = trim($clean_question);
        $clean_course_title = trim($clean_course_title);

        $headerFormat = str_replace('[course_title]', $course_title, $headerFormat);
        $headerFormat = str_replace('[question]', $question, $headerFormat);
        $headerFormat = str_replace('[clean_question]', $clean_question, $headerFormat);
        $headerFormat = str_replace('[clean_course_title]', $clean_course_title, $headerFormat);
        return $headerFormat;
    }

    public function getData()
    {
        $this->table = 1;
        $this->getMainData();
    }
    public function getData2()
    {
        $this->table = 2;
        $this->getMainData();
    }
    

    public function getMainData()
    {
        $field_types = $this->fields;
        $course_ids = is_array($this->courseLists) ? $this->courseLists : [];

        if ($this->isCompanyDashboard && $this->lockedCompanyId) {
            // company_employees lives on the app DB; users live on WordPress. whereHas() across connections is unreliable.
            $employeeUserIds = CompanyEmployee::query()
                ->where('company_id', $this->lockedCompanyId)
                ->pluck('user_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $this->userLists = $employeeUserIds !== []
                ? User::query()->whereIn('ID', $employeeUserIds)->get()
                : collect();

            $user_ids = $this->userLists->pluck('ID')->map(fn ($id) => (int) $id)->unique()->values()->all();
        } else {
            $companies = $this->companies;
            $usersQuery = User::query()->whereIn('ID', $this->users);
            if ($companies != []) {
                $usersQuery->orWhereHas('companyEmployee', function ($q) use ($companies) {
                    $q->whereIn('company_id', $companies);
                });
            }
            $user_ids = $usersQuery->pluck('id')->toArray();
            $this->userLists = $usersQuery->get();
        }

        if ($course_ids === []) {
            $this->results = [];
            $this->field_target = [];
            $this->form_ids = [];
            $this->computeHumanReadableStats();

            return;
        }

        $form_ids = CourseList::whereIn('id', $course_ids)->pluck('wp_gf_form_id')->toArray();

        $form_meta = WpGfFormMeta::query()
            ->whereIn('form_id', $form_ids)
            ->with('wpGfForm')
            ->get(['display_meta', 'form_id']);

        $field_target = [];
        $typesLower = array_map('strtolower', $field_types);

        foreach ($form_meta as $meta) {
            $courseList = CourseList::where('wp_gf_form_id', $meta->form_id)->first();
            $courseGroup = $courseList
                ? CourseGroupDetail::where('course_list_id', $courseList->id)->first()
                : null;
                

            // `orders` from course_group_details drives column / form order in exports & UI
            $sortOrder = (int) (optional($courseGroup)->orders ?? 999999);
            $ordersLabel = ($courseGroup !== null && $courseGroup->orders !== null && $courseGroup->orders !== '')
                ? (string) $courseGroup->orders
                : 'No Orders';

            $gfTitle = $meta->wpGfForm->title ?? '';

            $ld = $this->learnDashCourseLessonTopic($courseList);

            $field_target[$meta->form_id]['sort_order'] = $sortOrder;
            $field_target[$meta->form_id]['form_title'] = $ordersLabel . ' - ' . $gfTitle;
            $field_target[$meta->form_id]['ld_course_title'] = $ld['course'];
            $field_target[$meta->form_id]['ld_lesson_title'] = $ld['lesson'];
            $field_target[$meta->form_id]['ld_topic_title'] = $ld['topic'];

            $f = json_decode($meta->display_meta)->fields ?? [];
            foreach ($f as $field) {
                $ftype = is_string($field->type ?? null) ? strtolower((string) $field->type) : '';
                if (in_array($ftype, $typesLower, true)) {
                    $field_target[$meta->form_id]['id'][] = $field->id;
                    $field_target[$meta->form_id]['title'][$field->id] = $field->label;
                }
            }
        }

        $this->sortFieldTargetByCourseGroupOrder($field_target);
        $entries = WpGfEntryMeta::whereIn('form_id', $form_ids)->whereHas('wpGfEntry', function ($q) use ($user_ids) {
            $q->whereIn('created_by', $user_ids)
                ->whereIn('status', ['active', 'Active', 'ACTIVE']);
        })->get();
        $results = [];
        foreach ($entries as $entry) {
            $k = explode('.', $entry->meta_key)[0];
            if (isset($field_target[$entry->form_id])) {
                if (isset($field_target[$entry->form_id]['id'])) {
                    $allowedFieldIds = array_map('strval', $field_target[$entry->form_id]['id']);
                    if (in_array((string) $k, $allowedFieldIds, true)) {
                        $results [$entry->wpGfEntry->created_by][$entry->form_id]['title'] = $entry->wpGfEntry->wpGfForm->title;
                        $results [$entry->wpGfEntry->created_by][$entry->form_id]['data'][$k] = $entry->meta_value;
                    }
                } else {
                    unset($field_target[$entry->form_id]);
                }

            }
        }
        $this->results = $results;
        $this->field_target = $field_target;
        $this->form_ids = $form_ids;

        $this->computeHumanReadableStats();

        if ($this->table === 1 && $this->activityFooterStats !== []) {
            $this->dispatch(
                'export-result-charts-updated',
                pie: $this->chartUserParticipationPie,
                pieByWt: array_values($this->chartUserParticipationPieByWorkType),
                bar: $this->chartParticipationBar,
            );
        }
    }

    /**
     * Per-user row stats, per-activity footer columns, and chart payloads for the human-readable table.
     */
    private function computeHumanReadableStats(): void
    {
        $this->workTypeByUser = [];
        $this->userRowStats = [];
        $this->activityFooterStats = [];
        $this->totalActivitiesCount = 0;
        $this->grandAvgActivityAssessment = null;
        $this->chartUserParticipationPie = ['participating' => 0, 'non_participating' => 0, 'pct' => 0.0];
        $this->chartUserParticipationPieByWorkType = [];
        $this->chartParticipationBar = [];

        if ($this->userLists->isEmpty() || $this->field_target === []) {
            return;
        }

        $activities = $this->flattenActivityColumns();
        $this->totalActivitiesCount = count($activities);

        $userIds = $this->userLists->map(fn ($u) => (int) ($u->ID ?? $u->id))->unique()->values()->all();
        $this->workTypeByUser = WpUserMeta::query()
            ->where('meta_key', 'work_type')
            ->whereIn('user_id', $userIds)
            ->pluck('meta_value', 'user_id')
            ->map(fn ($v) => (string) $v)
            ->all();

        $participantCount = $this->userLists->count();

        foreach ($this->userLists as $user) {
            $complete = 0;
            $scores = [];
            foreach ($activities as $act) {
                $raw = $this->rawAnswerFor($user->ID, $act['form_id'], $act['field_id']);
                if ($this->isAnswered($raw)) {
                    $complete++;
                    $n = $this->parseNumericScore($raw);
                    if ($n !== null) {
                        $scores[] = $n;
                    }
                }
            }
            $total = $this->totalActivitiesCount;
            $pct = $total > 0 ? round(($complete / $total) * 100, 1) : null;
            $avgScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;
            $this->userRowStats[$user->ID] = [
                'complete' => $complete,
                'pct' => $pct,
                'avg_score' => $avgScore,
            ];
        }

        foreach ($activities as $act) {
            $numericValues = [];
            $participation = 0;
            foreach ($this->userLists as $user) {
                $raw = $this->rawAnswerFor($user->ID, $act['form_id'], $act['field_id']);
                if ($this->isAnswered($raw)) {
                    $participation++;
                    $n = $this->parseNumericScore($raw);
                    if ($n !== null) {
                        $numericValues[] = $n;
                    }
                }
            }
            $rate = $participantCount > 0 ? round(($participation / $participantCount) * 100, 1) : null;
            $colAvg = count($numericValues) > 0 ? round(array_sum($numericValues) / count($numericValues), 2) : null;
            $this->activityFooterStats[] = [
                'form_id' => $act['form_id'],
                'field_id' => $act['field_id'],
                'label' => $act['label'],
                'form_title' => $act['form_title'],
                'participation_count' => $participation,
                'participation_rate' => $rate,
                'avg_assessment' => $colAvg,
            ];
        }

        $colAvgs = array_filter(
            array_column($this->activityFooterStats, 'avg_assessment'),
            fn ($v) => $v !== null,
        );
        if ($colAvgs !== []) {
            $this->grandAvgActivityAssessment = round(array_sum($colAvgs) / count($colAvgs), 2);
        }

        $participatingUsers = 0;
        $nonParticipatingUsers = 0;
        foreach ($this->userLists as $user) {
            $c = $this->userRowStats[$user->ID]['complete'] ?? 0;
            if ($c > 0) {
                $participatingUsers++;
            } else {
                $nonParticipatingUsers++;
            }
        }
        $userTotal = $participatingUsers + $nonParticipatingUsers;
        $this->chartUserParticipationPie = [
            'participating' => $participatingUsers,
            'non_participating' => $nonParticipatingUsers,
            'pct' => $userTotal > 0 ? ($participatingUsers / $userTotal) * 100.0 : 0.0,
        ];

        $wtBuckets = [];
        foreach ($this->userLists as $user) {
            $wt = $this->workTypeByUser[$user->ID] ?? '';
            $wtLabel = $wt !== '' ? $wt : '(none)';
            if (! isset($wtBuckets[$wtLabel])) {
                $wtBuckets[$wtLabel] = ['participating' => 0, 'non_participating' => 0];
            }
            $c = $this->userRowStats[$user->ID]['complete'] ?? 0;
            if ($c > 0) {
                $wtBuckets[$wtLabel]['participating']++;
            } else {
                $wtBuckets[$wtLabel]['non_participating']++;
            }
        }
        foreach ($wtBuckets as $label => $b) {
            $t = $b['participating'] + $b['non_participating'];
            $this->chartUserParticipationPieByWorkType[] = [
                'label' => $label,
                'participating' => $b['participating'],
                'non_participating' => $b['non_participating'],
                'pct' => $t > 0 ? ($b['participating'] / $t) * 100.0 : 0.0,
            ];
        }
        usort(
            $this->chartUserParticipationPieByWorkType,
            fn ($a, $b) => ($b['participating'] + $b['non_participating']) <=> ($a['participating'] + $a['non_participating']),
        );

        $counts = array_column($this->activityFooterStats, 'participation_count');
        $maxPart = $counts === [] ? 1 : max(max($counts), 1);
        $barRows = [];
        foreach ($this->activityFooterStats as $row) {
            $headerLabel = $this->getHeaderFormat($row['form_title'], $row['label']);
            $axisLabel = Str::limit($headerLabel, 55);
            $cnt = $row['participation_count'];
            $barRows[] = [
                'label' => Str::limit($headerLabel, 140),
                'full_label' => $headerLabel,
                'axis_label' => $axisLabel,
                'count' => $cnt,
                'width_pct' => ($cnt / $maxPart) * 100.0,
            ];
        }
        usort($barRows, fn ($a, $b) => $b['count'] <=> $a['count']);
        $pastels = self::CHART_BAR_PASTELS;
        $nPastel = count($pastels);
        foreach ($barRows as $idx => $r) {
            $r['color'] = $pastels[$idx % $nPastel];
            $this->chartParticipationBar[] = $r;
        }
    }

    /**
     * @return list<array{form_id: int|string, field_id: mixed, label: string, form_title: string}>
     */
    private function flattenActivityColumns(): array
    {
        $list = [];
        foreach ($this->field_target as $formId => $field) {
            if (! isset($field['title']) || ! is_array($field['title'])) {
                continue;
            }
            $formTitle = (string) ($field['form_title'] ?? '');
            foreach ($field['title'] as $fieldId => $label) {
                $list[] = [
                    'form_id' => $formId,
                    'field_id' => $fieldId,
                    'label' => (string) $label,
                    'form_title' => $formTitle,
                ];
            }
        }

        return $list;
    }

    private function rawAnswerFor(int|string $userId, int|string $formId, int|string $fieldId): mixed
    {
        return data_get($this->results, "{$userId}.{$formId}.data.{$fieldId}");
    }

    public function displayAnswer(int|string $userId, int|string $formId, int|string $fieldId): string
    {
        $v = $this->rawAnswerFor($userId, $formId, $fieldId);
        if ($v === null || $v === '') {
            return '-';
        }

        return (string) $v;
    }

    private function isAnswered(mixed $raw): bool
    {
        if ($raw === null) {
            return false;
        }
        $s = trim((string) $raw);

        return $s !== '' && $s !== '-';
    }

    private function parseNumericScore(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        $s = trim((string) $raw);
        if ($s === '' || $s === '-') {
            return null;
        }
        if (str_contains($s, '|')) {
            $parts = array_filter(array_map('trim', explode('|', $s)));
            $nums = [];
            foreach ($parts as $p) {
                $n = $this->parseSingleNumber($p);
                if ($n !== null) {
                    $nums[] = $n;
                }
            }

            return $nums === [] ? null : array_sum($nums) / count($nums);
        }

        return $this->parseSingleNumber($s);
    }

    private function parseSingleNumber(string $s): ?float
    {
        $s = str_replace(',', '.', preg_replace('/[^\d\.\-]/', '', $s) ?? '');
        if ($s === '' || $s === '-' || $s === '.' || $s === '-.') {
            return null;
        }
        if (! is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    /**
     * Keep forms in the same order as course group detail `orders` (lower first).
     *
     * @param  array<int|string, array<string, mixed>>  $field_target
     */
    private function sortFieldTargetByCourseGroupOrder(array &$field_target): void
    {
        uasort($field_target, function ($a, $b) {
            return ($a['sort_order'] ?? 999999) <=> ($b['sort_order'] ?? 999999);
        });
    }

    /**
     * Resolve LearnDash course / lesson / topic titles from course_lists.url → topic slug → wp_posts + postmeta.
     *
     * @return array{course: string, lesson: string, topic: string}
     */
    private function learnDashCourseLessonTopic(?CourseList $courseList): array
    {
        $fallbackCourse = $courseList?->course_title ?? '';
        $fallbackTopic = $courseList?->page_title ?? '';

        if ($courseList === null || $courseList->url === null || $courseList->url === '') {
            return [
                'course' => $fallbackCourse,
                'lesson' => '',
                'topic' => $fallbackTopic,
            ];
        }

        $topicPath = trim((string) config('app.wordpress_topic_path', 'topics'), '/');
        $pattern = '/\/' . preg_quote($topicPath, '/') . '\/([^\/]+)\/?/';

        if (! preg_match($pattern, $courseList->url, $m)) {
            return [
                'course' => $fallbackCourse,
                'lesson' => '',
                'topic' => $fallbackTopic,
            ];
        }

        $slug = rawurldecode(str_replace('+', ' ', $m[1]));

        $topicPost = WpPost::query()
            ->where('post_name', $slug)
            ->where('post_type', 'sfwd-topic')
            ->where('post_status', 'publish')
            ->first();

        if ($topicPost === null) {
            return [
                'course' => $fallbackCourse,
                'lesson' => '',
                'topic' => $fallbackTopic,
            ];
        }

        $metas = WpPostMeta::query()
            ->where('post_id', $topicPost->ID)
            ->whereIn('meta_key', ['course_id', 'lesson_id'])
            ->get()
            ->keyBy('meta_key');

        $courseId = isset($metas['course_id']) ? (int) $metas['course_id']->meta_value : 0;
        $lessonId = isset($metas['lesson_id']) ? (int) $metas['lesson_id']->meta_value : 0;

        $coursePost = $courseId > 0 ? WpPost::find($courseId) : null;
        $lessonPost = $lessonId > 0 ? WpPost::find($lessonId) : null;

        return [
            'course' => $coursePost->post_title ?? $fallbackCourse,
            'lesson' => $lessonPost->post_title ?? '',
            'topic' => $topicPost->post_title ?? $fallbackTopic,
        ];
    }

    public function getCleanHeaderFormat($title)
    {
        $clean_title = $title;

        // Phrases stripped from labels (human-readable headers)
        $blacklist = [
            'Rate your current ability to',
            '1-5 (5 highest)',
            '1-5 (5 highest).',
            'with yourself and others',
            'Rate your current level of',
            'Rate your ability to',
            'Rate the current status of',
            'Rate your current practices for maintaining',
        'Rate your',
        '(1-5-highest)',
        '(1-5-highest).',
        '(1-5-highest)',
        'Course - Topic : ',
        'Course - Topic'
        ];
        $clean_title = str_replace(["'", '"','“','”',' .','.'], '', $clean_title);
        $clean_title = str_ireplace($blacklist, '', $clean_title);
        $clean_title = preg_replace('/\s+/', ' ', $clean_title);
        $clean_title = trim($clean_title);

        return $clean_title;
    }

    public function exportCsv2()
    {
        $this->getMainData();
        $filename = ($this->title?$this->title."-":'') . time() . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

    
            $header2 = ['Name', 'Course title', 'Question', 'Answer'];
            fputcsv($handle, $header2);

            foreach ($this->userLists as $user) {
                $clean_course_title = '';
                $clean_question = '';

                foreach ($this->field_target as $form_id => $field) {
                    if (isset($field['title'])) {
                        if ($this->headerFormatPivot1 == 'Clean') {
                            $clean_course_title = $this->getCleanHeaderFormat($field['form_title']);
                        } else {
                            $clean_course_title = $field['form_title'];
                        }
                        
                        foreach ($field['title'] as $k => $f) {
                            if ($this->headerFormatPivot2 == 'Clean') {
                                $clean_question = $this->getCleanHeaderFormat($f);
                            } else {
                                $clean_question = $f;
                            }
                            $row = [];
                            $row = [$user->user_nicename];
                            $row[] = $clean_course_title;
                            $row[] = $clean_question;
                            $row[] = $this->results[$user->ID][$form_id]['data'][$k] ?? '-';
                            fputcsv($handle, $row);
                        }
                    }
                }

            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportCsv()
    {
        $this->getMainData();
        $filename = ($this->title?$this->title."-":'') . time() . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            $header2 = ['Name', 'Work Type'];
            foreach ($this->field_target as $field) {
                if (isset($field['title'])) {
                    foreach ($field['title'] as $title) {
                        $header2[] = $this->getHeaderFormat($field['form_title'], $title);
                    }
                }
            }
            $header2[] = 'Activities Complete';
            $header2[] = '% Complete';
            $header2[] = 'Average Score';
            fputcsv($handle, $header2);

            foreach ($this->userLists as $user) {
                $row = [
                    $user->user_nicename,
                    $this->workTypeByUser[$user->ID] ?? '',
                ];
                foreach ($this->field_target as $form_id => $field) {
                    if (isset($field['title'])) {
                        foreach ($field['title'] as $k => $f) {
                            $row[] = $this->displayAnswer($user->ID, $form_id, $k);
                        }
                    }
                }
                $st = $this->userRowStats[$user->ID] ?? ['complete' => 0, 'pct' => null, 'avg_score' => null];
                $row[] = $st['complete'];
                $row[] = $st['pct'] !== null ? $st['pct'].'%' : '-';
                $row[] = $st['avg_score'] !== null ? (string) $st['avg_score'] : '-';
                fputcsv($handle, $row);
            }

            if ($this->activityFooterStats !== []) {
                $row = ['Participation Count', ''];
                foreach ($this->activityFooterStats as $s) {
                    $row[] = $s['participation_count'];
                }
                $row[] = '';
                $row[] = '';
                $row[] = '';
                fputcsv($handle, $row);

                $row = ['Activity Participation %', ''];
                foreach ($this->activityFooterStats as $s) {
                    $row[] = $s['participation_rate'] !== null ? $s['participation_rate'].'%' : '-';
                }
                $row[] = '';
                $row[] = '';
                $row[] = '';
                fputcsv($handle, $row);

                $row = ['Avg Activity Assessment', ''];
                foreach ($this->activityFooterStats as $s) {
                    $row[] = $s['avg_assessment'] !== null ? (string) $s['avg_assessment'] : '-';
                }
                $row[] = '';
                $row[] = '';
                $row[] = '';
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }



    public function render()
    {

        return view('livewire.export-result');
    }
}
