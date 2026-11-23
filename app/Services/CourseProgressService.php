<?php

namespace App\Services;

use App\Models\User;
use App\Models\WpGfEntry;
use App\Models\WpPost;
use App\Models\WpPostMeta;
use App\Models\WpUserMeta;

/**
 * Brings a person's LearnDash course progress (`_sfwd-course_progress`) in line with their active
 * Gravity Forms entries: a topic whose form they submitted is marked complete.
 *
 * This is what the "Refresh Progress" button on Admin > User > Course does, and what the
 * /api/next-course call (fired by WordPress after every form submission) now runs too, so a
 * submitted form always shows up in the progress without anybody pressing the button.
 */
class CourseProgressService
{
    /**
     * @return array{ok: bool, http: int, message: string, data: array<string, mixed>}
     */
    public function refreshForUser(int $userId): array
    {
        $user = User::find($userId);
        if (! $user) {
            return $this->result(false, 404, 'User not found');
        }

        $userMeta = WpUserMeta::where('user_id', $userId)->where('meta_key', '=', '_sfwd-course_progress')->first();
        if (! $userMeta) {
            return $this->result(false, 404, 'No course progress found for this user');
        }

        $courseUser = @unserialize((string) $userMeta->meta_value, ['allowed_classes' => false]);
        if (! is_array($courseUser)) {
            return $this->result(false, 500, 'The stored course progress could not be read');
        }

        $updated = 0;
        $processed = 0;
        $errors = [];

        $entries = WpGfEntry::where('created_by', $userId)->where('status', 'active')->get();

        foreach ($entries as $entry) {
            $processed++;

            try {
                [$courseId, $lessonId, $topicId] = $this->topicOfEntry((string) $entry->source_url);

                // Only a topic that was really found: with a missing id this used to write a
                // nameless entry into the progress array.
                if ($courseId !== null && $lessonId !== null && $topicId !== null) {
                    $courseUser[$courseId]['topics'][$lessonId][$topicId] = 1;
                    $updated++;
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage().' '.$entry->source_url;
            }

            $entry->update(['is_read' => 1]);
        }

        if ($updated > 0) {
            $userMeta->update(['meta_value' => serialize($courseUser)]);
        }

        return $this->result(true, 200, 'Fresh progress update completed', [
            'user_id' => $userId,
            'user_meta_id' => $courseUser,
            'total_entries_processed' => $processed,
            'progress_entries_updated' => $updated,
            'user_email' => $user->user_email,
            'list_errors' => $errors,
        ]);
    }

    /**
     * [course id, lesson id, topic id] of the topic a form was submitted on, read from the entry's
     * source URL (.../topics/<topic-name>/); nulls when it is not a topic page or not found.
     *
     * @return array{0: ?string, 1: ?string, 2: ?int}
     */
    public function topicOfEntry(string $sourceUrl): array
    {
        $topicName = self::topicNameFromUrl($sourceUrl);
        if ($topicName === null) {
            return [null, null, null];
        }

        $topic = WpPost::where('post_name', $topicName)->where('post_type', 'sfwd-topic')->first();
        if (! $topic) {
            return [null, null, null];
        }

        $lessonId = WpPostMeta::where('post_id', $topic->ID)->where('meta_key', '=', 'lesson_id')->value('meta_value');
        $courseId = WpPostMeta::where('post_id', $topic->ID)->where('meta_key', '=', 'course_id')->value('meta_value');
        if (! filled($lessonId) || ! filled($courseId)) {
            return [null, null, null];
        }

        return [(string) $courseId, (string) $lessonId, (int) $topic->ID];
    }

    /** "https://x/topics/revitalize-new-questionnaire/?a=1" => "revitalize-new-questionnaire". */
    public static function topicNameFromUrl(string $url): ?string
    {
        return preg_match('#/topics/([^/?\#]+)/#', $url, $m) ? $m[1] : null;
    }

    /** @return array{ok: bool, http: int, message: string, data: array<string, mixed>} */
    private function result(bool $ok, int $http, string $message, array $data = []): array
    {
        return ['ok' => $ok, 'http' => $http, 'message' => $message, 'data' => $data];
    }
}
