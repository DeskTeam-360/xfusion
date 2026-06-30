<div class="text-dark dark:text-darklink">
    <form wire:submit.prevent="save" class="max-w-xl space-y-4">
        <div>
            <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Company <span class="text-error">*</span></label>
            <select wire:model.live="companyId"
                    class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"
                    required>
                <option value="">Select company…</option>
                @foreach($companies as $company)
                    <option value="{{ $company['id'] }}">{{ $company['title'] }}</option>
                @endforeach
            </select>
            @error('companyId') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Leader <span class="text-error">*</span></label>
                <select wire:model="leaderUserId"
                        class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white">
                    <option value="">Select leader…</option>
                    @foreach($companyEmployeeOptions as $opt)
                        <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                    @endforeach
                </select>
                @error('leaderUserId') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Employee <span class="text-error">*</span></label>
                <select wire:model="employeeUserId"
                        class="form-control w-full rounded border border-border bg-white px-3 py-2 text-dark dark:bg-darkgray dark:border-darkborder dark:text-white">
                    <option value="">Select employee…</option>
                    @foreach($companyEmployeeOptions as $opt)
                        <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                    @endforeach
                </select>
                @error('employeeUserId') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            {{ $dataId === null ? 'Create pair' : 'Save' }}
        </button>
    </form>

    @if($dataId !== null)
    <div class="mt-10 max-w-3xl">
        <h3 class="mb-3 text-lg font-semibold text-dark dark:text-white">Conversations</h3>

        <form wire:submit.prevent="scheduleConversation" class="mb-6 flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-sm font-bold text-dark dark:text-light">Schedule new conversation</label>
                <input wire:model="newScheduledAt" type="datetime-local"
                       class="form-control rounded border border-border bg-white px-3 py-2 text-sm text-dark dark:bg-darkgray dark:border-darkborder dark:text-white"/>
                @error('newScheduledAt') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-success btn-sm">Schedule</button>
        </form>

        @if($conversations->isEmpty())
            <p class="text-sm text-dark/70 dark:text-darklink">No conversations yet.</p>
        @else
            <div class="overflow-x-auto rounded border border-border dark:border-darkborder">
                <table class="min-w-full divide-y divide-border text-sm dark:divide-darkborder">
                    <thead class="bg-gray-50/80 dark:bg-darkgray/40">
                        <tr>
                            <th class="px-3 py-2 text-start font-semibold">Scheduled</th>
                            <th class="px-3 py-2 text-start font-semibold">Held</th>
                            <th class="px-3 py-2 text-start font-semibold">Status</th>
                            <th class="px-3 py-2 text-start font-semibold">AI Synthesis</th>
                            <th class="px-3 py-2 text-end font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-white dark:divide-darkborder dark:bg-darkgray/20">
                        @foreach($conversations as $c)
                        <tr wire:key="conv-{{ $c->id }}">
                            <td class="px-3 py-2">{{ $c->scheduled_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $c->held_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="badge {{ $c->status === 'completed' ? 'bg-success' : ($c->status === 'cancelled' ? 'bg-secondary' : 'bg-info') }}">
                                    {{ ucfirst(str_replace('_', ' ', $c->status)) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-dark/60 dark:text-darklink">
                                {{ $c->synthesis ? 'Available' : '—' }}
                            </td>
                            <td class="px-3 py-2 text-end">
                                @if($c->status === 'scheduled')
                                <button type="button" wire:click="cancelConversation({{ $c->id }})" class="btn btn-error btn-outline btn-xs">Cancel</button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <p class="mt-3 text-xs text-dark/50 dark:text-darklink">
            Preparation, notes and live conversation flow happen in the WordPress <code>[fusion_one_on_one]</code> shortcode (visible to the leader and employee only). This admin view is oversight-only.
        </p>
    </div>
    @endif
</div>
