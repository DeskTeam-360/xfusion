<?php

namespace App\Livewire\Form;

use App\Models\XfusionKnowledge;
use App\Services\XfusionLlmKnowledgeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class XfusionKnowledgeForm extends Component
{
    public ?string $dataId = null;

    public string $title = '';

    public string $content = '';

    public string $category = '';

    public string $post_status = 'publish';

    /** @var list<string> */
    public array $categoryOptions = [];

    public ?string $syncStatus = null;

    public ?string $syncMessage = null;

    public function mount(?string $dataId = null): void
    {
        $this->dataId = $dataId !== null && $dataId !== '' ? $dataId : null;
        $this->categoryOptions = config('xfusion-llm.categories', []);

        if ($this->dataId !== null) {
            $post = XfusionKnowledge::with('postMeta')->findOrFail((int) $this->dataId);
            $this->title = (string) $post->post_title;
            $this->content = (string) $post->post_content;
            $this->category = (string) ($post->getMeta(XfusionKnowledge::META_CATEGORY) ?? '');
            $this->post_status = (string) $post->post_status;
            $this->syncStatus = $post->getMeta(XfusionKnowledge::META_SYNC_STATUS);
            $this->syncMessage = $post->getMeta(XfusionKnowledge::META_SYNC_ERROR);
        } elseif (count($this->categoryOptions) > 0) {
            $this->category = $this->categoryOptions[0];
        }
    }

    public function saveNew(): void
    {
        $this->validateForm();

        $authorId = (int) Auth::id();
        $slug = XfusionKnowledge::uniqueSlug($this->title);
        $now = now();

        $post = XfusionKnowledge::create(array_merge(
            XfusionKnowledge::defaultPostAttributes($authorId),
            [
                'post_title' => trim($this->title),
                'post_content' => $this->content,
                'post_status' => $this->post_status,
                'post_name' => $slug,
                'guid' => '',
                'post_modified' => $now->format('Y-m-d H:i:s'),
                'post_modified_gmt' => $now->utc()->format('Y-m-d H:i:s'),
            ]
        ));

        $post->setMeta(XfusionKnowledge::META_CATEGORY, trim($this->category));

        $sync = app(XfusionLlmKnowledgeService::class)->upsertPost($post, trim($this->category));

        $this->notifyAfterSave($sync, 'Knowledge created.');

        $this->redirect(route('xfusion-knowledge.edit', ['xfusion_knowledge' => $post->ID]));
    }

    public function saveExisting(): void
    {
        $this->validateForm();

        $post = XfusionKnowledge::findOrFail((int) $this->dataId);
        $now = now();

        $post->update([
            'post_title' => trim($this->title),
            'post_content' => $this->content,
            'post_status' => $this->post_status,
            'post_name' => XfusionKnowledge::uniqueSlug($this->title, (int) $post->ID),
            'post_modified' => $now->format('Y-m-d H:i:s'),
            'post_modified_gmt' => $now->utc()->format('Y-m-d H:i:s'),
        ]);

        $post->setMeta(XfusionKnowledge::META_CATEGORY, trim($this->category));

        $sync = app(XfusionLlmKnowledgeService::class)->upsertPost($post->fresh(), trim($this->category));

        $this->syncStatus = $post->fresh()->getMeta(XfusionKnowledge::META_SYNC_STATUS);
        $this->syncMessage = $post->fresh()->getMeta(XfusionKnowledge::META_SYNC_ERROR);

        $this->notifyAfterSave($sync, 'Knowledge updated.');
    }

    public function resyncToLlm(): void
    {
        if ($this->dataId === null) {
            return;
        }

        $post = XfusionKnowledge::findOrFail((int) $this->dataId);
        $category = trim($this->category) !== ''
            ? trim($this->category)
            : (string) ($post->getMeta(XfusionKnowledge::META_CATEGORY) ?? '');

        $sync = app(XfusionLlmKnowledgeService::class)->upsertPost($post, $category);

        $this->syncStatus = $post->fresh()->getMeta(XfusionKnowledge::META_SYNC_STATUS);
        $this->syncMessage = $post->fresh()->getMeta(XfusionKnowledge::META_SYNC_ERROR);

        $this->notifyAfterSave($sync, 'Re-sync requested.');
    }

    private function validateForm(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:255',
            'post_status' => 'required|in:publish,draft,pending,private',
        ]);
    }

    /**
     * @param  array{ok: bool, message: string}  $sync
     */
    private function notifyAfterSave(array $sync, string $baseTitle): void
    {
        if ($sync['ok']) {
            $this->dispatch('swal:alert', data: [
                'icon' => 'success',
                'title' => $baseTitle.' '.$sync['message'],
            ]);

            return;
        }

        $this->dispatch('swal:alert', data: [
            'icon' => 'warning',
            'title' => $baseTitle.' Saved in WordPress, but LLM sync failed: '.$sync['message'],
        ]);
    }

    public function render()
    {
        return view('livewire.form.xfusion-knowledge');
    }
}
