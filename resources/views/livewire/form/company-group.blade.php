@php
    $spinnerSvg = '<svg class="size-4 animate-spin shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
@endphp

<div class="text-dark dark:text-darklink">
    @if($dataId === null)
        <form wire:submit.prevent="saveNew" class="max-w-xl space-y-4">
            <p wire:loading wire:target="saveNew" class="flex items-center gap-2 text-sm text-dark/80 dark:text-darklink" role="status">
                {!! $spinnerSvg !!}
                <span>Creating group…</span>
            </p>

            <fieldset wire:loading.attr="disabled" wire:loading.class="opacity-60" wire:target="saveNew" class="min-w-0 border-0 p-0">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Company <span class="text-error">*</span></label>
                        <select wire:model="companyId"
                                class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"
                                required>
                            <option value="">Select company…</option>
                            @foreach($companies as $company)
                                <option value="{{ $company['id'] }}">{{ $company['title'] }}</option>
                            @endforeach
                        </select>
                        @error('companyId') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Title <span class="text-error">*</span></label>
                        <input wire:model="title" type="text"
                               class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"
                               required/>
                        @error('title') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Description</label>
                        <textarea wire:model="description" rows="4"
                                  class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary inline-flex items-center gap-2" wire:loading.attr="disabled" wire:target="saveNew">
                        <span wire:loading wire:target="saveNew" aria-hidden="true">{!! $spinnerSvg !!}</span>
                        <span wire:loading.remove wire:target="saveNew">Create group</span>
                        <span wire:loading wire:target="saveNew">Please wait…</span>
                    </button>
                </div>
            </fieldset>
        </form>
    @else
        <form wire:submit.prevent="saveExisting" class="space-y-8">
            <p wire:loading wire:target="saveExisting" class="flex items-center gap-2 text-sm font-medium text-dark dark:text-white" role="status">
                {!! $spinnerSvg !!}
                <span>Saving…</span>
            </p>

            <fieldset wire:loading.attr="disabled" wire:loading.class="opacity-60" wire:target="saveExisting" class="min-w-0 border-0 p-0">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Company</label>
                        <p class="rounded border border-border bg-gray-50/60 px-3 py-2 text-sm text-dark dark:border-darkborder dark:bg-darkgray/30 dark:text-white">
                            {{ $companyTitle }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Title <span class="text-error">*</span></label>
                        <input wire:model="title" type="text"
                               class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"/>
                        @error('title') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Description</label>
                        <textarea wire:model="description" rows="3"
                                  class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"></textarea>
                    </div>
                </div>

                <div class="mt-8">
                    <h3 class="mb-2 text-lg font-semibold text-dark dark:text-white">Members</h3>
                    <p class="mb-4 text-xs text-dark/60 dark:text-darklink">Add employees from this company. Status: <strong>member</strong> or <strong>leader</strong> (max one leader).</p>
                    @error('members') <p class="text-error mb-3 text-sm">{{ $message }}</p> @enderror

                    <div class="mb-6 rounded-lg border border-border bg-gray-50/40 p-4 dark:border-darkborder dark:bg-transparent">
                        <label class="mb-2 block text-sm font-bold text-dark dark:text-light">Find employee</label>
                        <div x-data="{ query: '' }" class="space-y-2">
                            <input type="search"
                                   x-model="query"
                                   x-on:input.debounce.400ms="$wire.searchCompanyUsers(query)"
                                   placeholder="Type name or email (min. 2 characters)…"
                                   autocomplete="off"
                                   class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"/>
                            @if(count($userSearchResults) > 0)
                                <ul class="divide-y divide-border rounded border border-border bg-white dark:border-darkborder dark:bg-darkgray/30">
                                    @foreach($userSearchResults as $row)
                                        <li wire:key="cg-user-pick-{{ $row['id'] }}">
                                            <button type="button"
                                                    wire:click="addMember({{ $row['id'] }})"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-dark hover:bg-primary/10 dark:text-white dark:hover:bg-darkborder/40">
                                                <span>{{ $row['label'] }}</span>
                                                <span class="ms-auto text-xs text-dark/60 dark:text-darklink">Add</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    @if(count($members) === 0)
                        <p class="text-sm text-dark/75 dark:text-darklink">No members yet. Search and add employees above.</p>
                    @else
                        <div class="overflow-x-auto rounded border border-border dark:border-darkborder">
                            <table class="min-w-full divide-y divide-border text-sm dark:divide-darkborder">
                                <thead class="bg-gray-50/80 dark:bg-darkgray/40">
                                    <tr>
                                        <th class="px-3 py-2 text-start font-semibold text-dark dark:text-white">Employee</th>
                                        <th class="px-3 py-2 text-start font-semibold text-dark dark:text-white">Status</th>
                                        <th class="px-3 py-2 text-end font-semibold text-dark dark:text-white">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-white dark:divide-darkborder dark:bg-darkgray/20">
                                    @foreach($members as $index => $member)
                                        <tr wire:key="cg-member-{{ $index }}-{{ $member['user_id'] }}">
                                            <td class="px-3 py-2 text-dark dark:text-white">{{ $member['label'] }}</td>
                                            <td class="px-3 py-2">
                                                <select wire:change="setMemberStatus({{ $index }}, $event.target.value)"
                                                        class="form-control rounded border border-border bg-white px-2 py-1 text-sm text-dark dark:bg-darkgray dark:border-darkborder dark:text-white">
                                                    <option value="member" @selected($member['status'] === 'member')>Member</option>
                                                    <option value="leader" @selected($member['status'] === 'leader')>Leader</option>
                                                </select>
                                            </td>
                                            <td class="px-3 py-2 text-end">
                                                <button type="button"
                                                        wire:click="removeMember({{ $index }})"
                                                        class="btn btn-error btn-outline btn-xs shrink-0">Remove</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="mt-8">
                    <button type="submit" class="btn btn-primary inline-flex items-center gap-2" wire:loading.attr="disabled" wire:target="saveExisting">
                        <span wire:loading wire:target="saveExisting" aria-hidden="true">{!! $spinnerSvg !!}</span>
                        <span wire:loading.remove wire:target="saveExisting">Save</span>
                        <span wire:loading wire:target="saveExisting">Saving…</span>
                    </button>
                </div>
            </fieldset>
        </form>
    @endif
</div>
