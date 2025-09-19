<?php

namespace App\Livewire;

use App\Models\Tag;
use App\Models\User;
use App\Models\WpUserMeta;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AddTagToUser extends Component
{
    public $userId;
    public $availableTags = [];
    public $selectedTags = [];
    
    public $tags = [];

    public function mount($userId)
    {
        $this->userId = $userId;
        $this->loadAvailableTags();
        $this->loadCurrentTags();
    }

    public function loadAvailableTags()
    {
        // Get all available tags
        $allTags = Tag::all();
        
        // Get current user tags
        $currentUserTags = User::find($this->userId)->meta->where('meta_key', '=', 'access_tags')->first();
        $currentTagIds = [];
        
        if ($currentUserTags && $currentUserTags->meta_value) {
            $currentTagIds = explode(';', $currentUserTags->meta_value);
        }
        
        // Filter out tags that are already assigned to the user and format for Select2
        $this->availableTags = $allTags->filter(function($tag) use ($currentTagIds) {
            return !in_array($tag->id, $currentTagIds);
        })->map(function($tag) {
            return [
                'value' => $tag->id,
                'title' => $tag->name . ($tag->description ? ' - ' . $tag->description : '')
            ];
        })->values()->toArray();
    }

    public function loadCurrentTags()
    {
        $currentUserTags = User::find($this->userId)->meta->where('meta_key', '=', 'access_tags')->first();
        if ($currentUserTags && $currentUserTags->meta_value) {
            $this->selectedTags = explode(';', $currentUserTags->meta_value);
        } else {
            $this->selectedTags = [];
        }
    }

    public function addTags()
    {
        // Manual validation
        if (empty($this->tags) || !is_array($this->tags)) {
            $this->dispatch('swal:alert', data: [
                'icon' => 'warning',
                'title' => 'Please select at least one tag',
            ]);
            return;
        }
        
        $this->resetErrorBag();

        try {
            // Update user's access_tags meta
            $accessTag = WpUserMeta::where('user_id', $this->userId)
                ->where('meta_key', 'access_tags')
                ->first();

            $currentTags = [];
            if ($accessTag && $accessTag->meta_value) {
                $currentTags = explode(';', $accessTag->meta_value);
            }

            $newTags = array_merge($currentTags, $this->tags);
            $newTags = array_unique($newTags);
            $newTagsString = implode(';', $newTags);

            if ($accessTag) {
                $accessTag->update(['meta_value' => $newTagsString]);
            } else {
                WpUserMeta::create([
                    'user_id' => $this->userId,
                    'meta_key' => 'access_tags',
                    'meta_value' => $newTagsString
                ]);
            }

            // Update user's keap_tags meta
            $keapTag = WpUserMeta::where('user_id', $this->userId)
                ->where('meta_key', 'keap_tags')
                ->first();

            if ($keapTag) {
                $currentKeapTags = explode(';', $keapTag->meta_value);
                $newKeapTags = array_merge($currentKeapTags, $this->tags);
                $newKeapTags = array_unique($newKeapTags);
                $keapTag->update(['meta_value' => implode(';', $newKeapTags)]);
            } else {
                WpUserMeta::create([
                    'user_id' => $this->userId,
                    'meta_key' => 'keap_tags',
                    'meta_value' => implode(';', $this->tags)
                ]);
            }

            // Update keap_tags_applies with current timestamp
            $keapTagApply = WpUserMeta::where('user_id', $this->userId)
                ->where('meta_key', 'keap_tags_applies')
                ->first();

            $currentApplyTimes = [];
            if ($keapTagApply && $keapTagApply->meta_value) {
                $currentApplyTimes = explode(';', $keapTagApply->meta_value);
            }

            $newApplyTimes = array_merge($currentApplyTimes, array_fill(0, count($this->tags), now()->toDateTimeString()));
            $keapTagApplyString = implode(';', $newApplyTimes);

            if ($keapTagApply) {
                $keapTagApply->update(['meta_value' => $keapTagApplyString]);
            } else {
                WpUserMeta::create([
                    'user_id' => $this->userId,
                    'meta_key' => 'keap_tags_applies',
                    'meta_value' => $keapTagApplyString
                ]);
            }

            // Update to Keap if user has Keap integration
            $keapContactId = WpUserMeta::where('user_id', $this->userId)
                ->where('meta_key', 'keap_contact_id')
                ->first();

            if ($keapContactId && !empty($keapContactId) && $keapContactId->meta_value) {
                try {
                    Keap::contact()->tag($keapContactId->meta_value, $this->tags);    
                } catch (\Exception $keapException) {
                    // Log Keap error but don't fail the whole operation
                    \Log::error('Failed to sync tags to Keap contact: ' . $keapException->getMessage());
                }
            }

            $this->dispatch('swal:alert', data: [
                'icon' => 'success',
                'title' => 'Success',
                'text' => 'Tags have been successfully added to the user'
            ]);

            // Clear selected tags and reload available tags
            $this->tags = [];
            $this->loadAvailableTags();

            // Refresh the page to show updated tags
            $this->dispatch('refreshPage');
            

        } catch (\Exception $e) {
            $this->dispatch('swal:alert', data: [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Failed to add tags: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.add-tag-to-user');
    }
}
