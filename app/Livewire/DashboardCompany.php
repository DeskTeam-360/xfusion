<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardCompany extends Component
{

    public $user;
    public string $companyId = '';

    public $userEmployee;
    public ?int $complete = 0;

    public ?int $inComplete = 0;

    /**
     * Counts grouped by normalized access slug (from wp_usermeta user_access JSON).
     * One user contributes +1 to every slug they carry.
     *
     * @var array<string, int>
     */
    public array $accessTagCounts = [];

    /**
     * @var list<array{slug: string, count: int}>
     */
    public array $accessTagRows = [];


    public function mount()
    {
        $this->user = Auth::user();
        $company = $this->user->meta->where('meta_key', '=', 'company')->first();

        if ($company === null) {
            $this->companyId = '';
            $this->userEmployee = collect();
            $this->accessTagCounts = [];
            $this->accessTagRows = [];

            return;
        }

        $raw = is_object($company) ? ($company->meta_value ?? '') : ($company['meta_value'] ?? '');
        if (is_array($raw)) {
            $raw = $raw[0] ?? '';
        }
        $this->companyId = $raw !== null && $raw !== '' ? (string) $raw : '';

        if ($this->companyId === '') {
            $this->userEmployee = collect();
            $this->accessTagCounts = [];
            $this->accessTagRows = [];

            return;
        }

        $this->getData();
    }

    public function getData()
    {
        $this->complete = 0;
        $this->inComplete = 0;
        $this->accessTagCounts = [];
        $this->accessTagRows = [];

        if ($this->companyId === '') {
            $this->userEmployee = collect();

            return;
        }

        $this->userEmployee = User::with('meta')
            ->whereHas('meta', function ($q) {
                $q->where('meta_key', config('app.wp_prefix', 'wp_') . 'capabilities')
                    ->where('meta_value', 'like', '%subscriber%');
            })->whereHas('meta', function ($q) {
                $q->where('meta_key', 'company')
                    ->where('meta_value', $this->companyId);
            })
            ->get();

        foreach ($this->userEmployee as $c) {
            $this->inComplete += 1;
        }

        $this->buildAccessTagCounts();
    }

    private function buildAccessTagCounts(): void
    {
        $this->accessTagCounts = [];

        $orderIndex = [];
        $seq = 0;
        foreach (UserRole::query()->orderBy('id')->get(['accesses']) as $role) {
            $items = self::decodeAccessesValue((string) ($role->accesses ?? ''));

            foreach ($items as $item) {
                $slug = self::normalizeAccessSlug($item);
                if ($slug === '') {
                    continue;
                }

                if (! array_key_exists($slug, $orderIndex)) {
                    ++$seq;
                    $orderIndex[$slug] = $seq;
                }
            }
        }

        foreach ($this->userEmployee as $employee) {
            $metaRow = $employee->meta->where('meta_key', 'user_access')->first();
            $raw = is_object($metaRow) ? ($metaRow->meta_value ?? '') : ($metaRow['meta_value'] ?? '');
            if (is_array($raw)) {
                $raw = json_encode($raw);
            }

            foreach (self::decodeAccessesValue((string) $raw) as $item) {
                $slug = self::normalizeAccessSlug($item);
                if ($slug === '') {
                    continue;
                }

                $this->accessTagCounts[$slug] = ($this->accessTagCounts[$slug] ?? 0) + 1;
                if (! array_key_exists($slug, $orderIndex)) {
                    $orderIndex[$slug] = 500000 + ord($slug[0] ?? 'z');
                }
            }
        }

        $pairs = [];
        foreach ($this->accessTagCounts as $slug => $count) {
            $pairs[] = ['slug' => $slug, 'count' => $count, '_ord' => $orderIndex[$slug] ?? 999999];
        }

        usort($pairs, function (array $a, array $b): int {
            if ($a['_ord'] !== $b['_ord']) {
                return $a['_ord'] <=> $b['_ord'];
            }

            return strcmp($a['slug'], $b['slug']);
        });

        $this->accessTagRows = array_values(array_map(static function (array $p): array {
            return ['slug' => $p['slug'], 'count' => $p['count']];
        }, $pairs));
    }

    /**
     * @return list<string>
     */
    private static function decodeAccessesValue(string $stored): array
    {
        $stored = trim($stored);
        if ($stored === '') {
            return [];
        }

        $decoded = json_decode($stored, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $out = [];
            foreach ($decoded as $x) {
                if (is_string($x) || is_numeric($x)) {
                    $out[] = (string) $x;
                }
            }

            return $out;
        }

        return array_values(array_filter(array_map('trim', explode(',', $stored))));
    }

    private static function normalizeAccessSlug(string $item): string
    {
        return strtolower(trim($item));
    }

    public function getDataUserGrowh($i)
    {
        return User::whereHas('meta', function ($q) {
                $q->where('meta_key', 'company')
                    ->where('meta_value', $this->companyId);
            })
            ->whereMonth('user_registered', Carbon::now()->subMonths(2 - $i)->month)
            ->whereYear('user_registered', Carbon::now()->subMonths(2 - $i)->year)
            ->get()->count();
    }

    public function render()
    {
        return view('livewire.dashboard-company');
    }
}
