<?php

namespace App\Services;

use App\Models\OneOnOneConversation;
use App\Models\OneOnOneNote;
use App\Models\OneOnOnePreparation;
use Illuminate\Support\Facades\DB;

class OneOnOneWizardDraftService
{
    /**
     * @return array<string, mixed>
     */
    public function decodePrepContent(mixed $content): array
    {
        if (is_array($content)) {
            return $content;
        }
        if (! is_string($content) || trim($content) === '') {
            return [];
        }
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : ['summary' => $content];
    }

    /**
     * @return array<string, string>
     */
    public function encodePrepValues(array $values): string
    {
        $clean = [];
        foreach ($values as $key => $value) {
            $clean[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Wizard load payload — preparation slugs + conversation note slugs.
     *
     * @param  list<string>  $allowedRoles
     * @return array{employee: array<string, string>, leader: array<string, string>, conversation: array<string, string>}
     */
    public function draftPayload(
        OneOnOneConversation $conversation,
        array $allowedRoles,
        int $userId,
        string $notesScope = 'own'
    ): array {
        $out = [
            'employee' => [],
            'leader' => [],
            'conversation' => [],
        ];

        $preps = $conversation->preparations()->get(['author_role', 'content']);

        foreach (['employee', 'leader'] as $role) {
            if (! in_array($role, $allowedRoles, true)) {
                continue;
            }
            $row = $preps->firstWhere('author_role', $role);
            $out[$role] = $this->slugStringMap($row ? $this->decodePrepContent($row->content) : []);
        }

        $notesQuery = $conversation->notes()->orderBy('id');
        if ($notesScope !== 'all') {
            $notesQuery->where('created_by', $userId);
        }

        foreach ($notesQuery->get(['section', 'note']) as $note) {
            $section = (string) $note->section;
            if ($section === '') {
                continue;
            }
            $out['conversation'][$section] = (string) $note->note;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function savePreparationRole(
        OneOnOneConversation $conversation,
        string $role,
        array $values,
        int $authorUserId
    ): OneOnOnePreparation {
        if (! in_array($role, OneOnOnePreparation::validRoles(), true)) {
            throw new \InvalidArgumentException('Invalid preparation role.');
        }

        return OneOnOnePreparation::updateOrCreate(
            ['conversation_id' => $conversation->id, 'author_role' => $role],
            [
                'author_user_id' => $authorUserId,
                'content' => $this->encodePrepValues($values),
            ]
        );
    }

    /**
     * Replace conversation guide notes for this save (non-empty sections upserted).
     *
     * @param  array<string, mixed>  $values
     */
    public function saveConversationNotes(OneOnOneConversation $conversation, array $values, int $userId): void
    {
        DB::transaction(function () use ($conversation, $values, $userId) {
            $sections = array_keys($values);

            if ($sections !== []) {
                $conversation->notes()
                    ->where('created_by', $userId)
                    ->whereIn('section', $sections)
                    ->delete();
            }

            foreach ($values as $section => $note) {
                $sectionKey = (string) $section;
                $text = trim(is_scalar($note) ? (string) $note : '');
                if ($sectionKey === '' || $text === '') {
                    continue;
                }

                OneOnOneNote::create([
                    'conversation_id' => $conversation->id,
                    'section' => $sectionKey,
                    'note' => $text,
                    'created_by' => $userId,
                ]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    private function slugStringMap(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            if (is_scalar($value)) {
                $out[(string) $key] = (string) $value;
            }
        }

        return $out;
    }
}
