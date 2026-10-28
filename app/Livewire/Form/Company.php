<?php

namespace App\Livewire\Form;

use App\Repository\View\CompanyEmployee;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;


class Company extends Component
{

    use WithFileUploads;

    // Locked: public properties can be rewritten from the browser, and these two
    // decide which company update() writes to and which method the form calls.
    #[Locked]
    public $action;
    #[Locked]
    public $dataId;
    public $data;

    // Informational only — any user can be selected as a company leader
    // even if they already belong to another company; this just surfaces
    // that fact so the person assigning them can make an informed choice.
    public $leaderWarning = null;

//    #[Validate('required')]
    public $user_id;
//    #[Validate('required|max:255')]
    public $title;
    public $slug;
//    #[Validate('image:4096|mimes:png')]
    public $logo_url;
//    #[Validate('image:4096|mimes:png')]
    public $qrcode_url;
    public $company_url;

    // Organizational Context™ — shown read-only in the 1-on-1 wizard's
    // Step 1 evidence card; group-level values (if set) take precedence.
    public $role;
    public $team;
    public $organizational_goals;
    public $readiness_priorities;

    protected $rules = [
        'title' => 'required|max:255',
        'slug' => 'nullable|string|max:120|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:wp_companies,slug',
        'logo_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096|dimensions:min_width=140,min_height=60,max_width=160,max_height=80',
        'company_url' => 'nullable',
        'user_id' => 'required',
        'role' => 'nullable|string|max:255',
        'team' => 'nullable|string|max:255',
        'organizational_goals' => 'nullable|string',
        'readiness_priorities' => 'nullable|string',
    ];

    protected $messages = [
        'title.required' => 'Please fill the Company name.',
        'logo_url.image' => 'The logo must be an image file.',
        'logo_url.mimes' => 'The logo must be a file of type: jpeg, png, jpg, gif.',
        'logo_url.max' => 'The logo may not be greater than 4MB.',
        'logo_url.dimensions' => 'The logo must be between 140x60 and 160x80 pixels (recommended: 150x70).',
        'user_id.required' => 'Please select a Company leader.',
        'slug.regex' => 'The company custom URL may only contain lowercase letters, numbers and single dashes.',
        'slug.unique' => 'That custom URL is already used by another company.',
    ];

    protected $validationAttributes = [
        'slug' => 'company custom URL',
    ];

    public function mount()
    {
        if ($this->dataId) {
            $data = \App\Models\Company::find($this->dataId);
            $this->user_id = (int)$data->user_id;
            $this->title = $data->title;
            $this->slug = $data->slug;
            $this->logo_url = $data->logo_url;
            $this->qrcode_url = $data->qrcode_url;
            $this->company_url = $data->company_url;
            $this->role = $data->role;
            $this->team = $data->team;
            $this->organizational_goals = $data->organizational_goals;
            $this->readiness_priorities = $data->readiness_priorities;
        }
    }

    /** Livewire lifecycle hook — fires whenever the Leader select changes. */
    public function updatedUserId($value): void
    {
        $this->leaderWarning = null;
        if (! $value) {
            return;
        }

        $companyNames = CompanyEmployee::where('user_id', $value)
            ->where('company_id', '!=', $this->dataId ?? 0)
            ->with('company:id,title')
            ->get()
            ->pluck('company.title')
            ->filter()
            ->unique()
            ->values();

        if ($companyNames->isNotEmpty()) {
            $this->leaderWarning = 'This user already belongs to: ' . $companyNames->implode(', ') . '.';
        }
    }

    /**
     * The custom URL field submits "" when left blank. nullable only skips
     * the regex for null, so an empty box was rejected even though the
     * label says to leave it empty and let the name become the URL.
     */
    private function normalizeSlug(): void
    {
        if (! is_string($this->slug) || trim($this->slug) === '') {
            $this->slug = null;

            return;
        }

        $this->slug = trim($this->slug);
    }

    public function create()
    {
        $this->normalizeSlug();
        $this->validate();

        $logoUrl = $this->logo_url != null ? $this->logo_url->store(path: 'public/photos') : '';
        $qrcodeUrl = $this->qrcode_url != null ? $this->qrcode_url->store(path: 'public/qrcode') : '';

        // One transaction: a failure part-way must not leave a company without its leader/employee row.
        DB::transaction(function () use ($logoUrl, $qrcodeUrl) {
            $company = \App\Models\Company::create([
                'user_id' => $this->user_id,
                'title' => $this->title,
                'slug' => $this->slug ?: null,
                'logo_url' => $logoUrl,
                'qrcode_url' => $qrcodeUrl,
                'company_url' => $this->company_url,
                'role' => $this->role,
                'team' => $this->team,
                'organizational_goals' => $this->organizational_goals,
                'readiness_priorities' => $this->readiness_priorities,
            ]);
            \App\Models\User::find($this->user_id)
                ->saveMeta([
                    'company' => $company->id,
                ]);
            CompanyEmployee::create([
                'user_id' => $this->user_id,
                'company_id' => $company->id,
            ]);
        });
        // Flashed, not dispatched: a browser event fired right before the redirect is lost with the page.
        session()->flash('swal', ['icon' => 'success', 'title' => 'Successfully added company']);
        $this->redirect(route('company.index'));
    }

    public function update()
    {
        // mount() pre-fills logo_url with the existing logo's URL string
        // (for display) when the leader isn't uploading a new one — the
        // image/mimes/dimensions rules only make sense against an actual
        // new upload, not that string, or saving without touching the
        // logo would always fail validation.
        $logoRule = $this->logo_url instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
            ? 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096|dimensions:min_width=140,min_height=60,max_width=160,max_height=80'
            : 'nullable';

        $this->normalizeSlug();
        $this->validate([
            'user_id' => 'required',
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', \Illuminate\Validation\Rule::unique('wp_companies', 'slug')->ignore($this->dataId)],
            'logo_url' => $logoRule,
        ]);
        $data_compare = \App\Models\Company::find($this->dataId);
        $previousLeaderId = (int) $data_compare->user_id;

        if ($data_compare->logo_url != $this->logo_url) {
            if ($this->logo_url != null) {
                $logoUrl = $this->logo_url->store(path: 'public/photos');
                \App\Models\Company::find($this->dataId)->update([
                    'logo_url' => $logoUrl,
                ]);
            }
        } else if ($data_compare->qrcode_url != $this->qrcode_url) {
            if ($this->qrcode_url != null) {
                $qrcodeUrl = $this->qrcode_url->store(path: 'public/qrcode');
                \App\Models\Company::find($this->dataId)->update([
                    'qrcode_url' => $qrcodeUrl
                ]);
            }
        }

        \App\Models\Company::find($this->dataId)->update([
            'user_id' => $this->user_id,
            'title' => $this->title,
            'slug' => $this->slug ?: null,
            'company_url' => $this->company_url,
            'role' => $this->role,
            'team' => $this->team,
            'organizational_goals' => $this->organizational_goals,
            'readiness_priorities' => $this->readiness_priorities,
        ]);
        // A new leader must be a member of the company too (as create() does), or they
        // would lead it without being able to open its modules. The previous leader stays
        // an employee. Idempotent: running it twice adds nothing.
        if ((int) $this->user_id !== $previousLeaderId) {
            CompanyEmployee::firstOrCreate([
                'user_id' => $this->user_id,
                'company_id' => $this->dataId,
            ]);
            \App\Models\User::find($this->user_id)?->saveMeta(['company' => $this->dataId]);
        }

        session()->flash('swal', ['icon' => 'success', 'title' => 'Successfully changed the company']);
        $this->redirect(route('company.index'));
    }

    public function render()
    {
        // Any user can be assigned as a company leader, including someone who
        // already leads/works at another company — see updatedUserId() for the
        // informational warning. Loaded per render (two columns) instead of being
        // a public property, which would ship every WordPress user to the browser
        // inside the Livewire snapshot on every request.
        $usersOption = \App\Models\User::query()
            ->orderBy('user_email')
            ->get(['ID', 'user_email'])
            ->map(fn ($u) => ['value' => $u->ID, 'title' => $u->user_email])
            ->all();

        return view('livewire.form.company', ['usersOption' => $usersOption]);
    }
}
