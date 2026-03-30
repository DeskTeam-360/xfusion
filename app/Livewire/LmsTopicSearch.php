<?php

namespace App\Livewire;

use App\Models\WpPost;
use Livewire\Component;

class LmsTopicSearch extends Component
{
    public string $q = '';

    public bool $searched = false;

    /** @var array<int, array{title: string, url: string, snippets: array<int, string>}> */
    public array $results = [];

    public function search(): void
    {
        $this->searched = true;
        $this->results = [];
        $keyword = trim($this->q);
        if (strlen($keyword) < 2) {
            return;
        }

        $keywords = array_values(array_filter(explode(' ', strtolower($keyword))));
        if ($keywords === []) {
            return;
        }

        $query = WpPost::query()
            ->where('post_type', 'sfwd-topic')
            ->where('post_status', 'publish');

        foreach ($keywords as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';
            $query->whereHas('postMeta', function ($q) use ($like) {
                $q->where('meta_key', '_search_index')
                    ->where('meta_value', 'like', $like);
            });
        }

        $posts = $query
            ->with(['postMeta' => function ($q) {
                $q->where('meta_key', '_search_index');
            }])
            ->orderBy('post_title')
            ->limit(30)
            ->get();

        foreach ($posts as $post) {
            $indexText = (string) $post->postMeta
                ->where('meta_key', '_search_index')
                ->first()?->meta_value;

            $this->results[] = [
                'title' => $post->post_title,
                'url' => $this->topicUrl($post),
                'snippets' => $this->generateSnippets($indexText, $keywords, 8, 5),
            ];
        }
    }

    private function topicUrl(WpPost $post): string
    {
        $base = rtrim((string) config('app.wordpress_url'), '/');
        $path = trim((string) config('app.wordpress_topic_path', 'topics'), '/');
        $slug = $post->post_name;

        if ($post->guid !== '' && filter_var($post->guid, FILTER_VALIDATE_URL)) {
            $g = (string) $post->guid;
            if (! str_contains(strtolower($g), '?p=')) {
                return $g;
            }
        }

        return $base . '/' . $path . '/' . rawurlencode((string) $slug) . '/';
    }

    /**
     * @param  array<int, string>  $keywords
     * @return array<int, string>
     */
    private function generateSnippets(string $text, array $keywords, int $radius = 10, int $maxSnippets = 3): array
    {
        $text = strtolower(strip_tags($text));
        $words = $text === '' ? [] : explode(' ', $text);
        $total = count($words);
        if ($total === 0) {
            return [];
        }

        $snippets = [];
        $usedIndexes = [];

        foreach ($words as $index => $word) {
            foreach ($keywords as $keyword) {
                if ($keyword === '') {
                    continue;
                }
                if (str_contains($word, $keyword) && ! in_array($index, $usedIndexes, true)) {
                    $start = max(0, $index - $radius);
                    $end = min($total - 1, $index + $radius);
                    $slice = array_slice($words, $start, $end - $start + 1);
                    $snippet = implode(' ', $slice);
                    foreach ($keywords as $kw) {
                        if ($kw === '') {
                            continue;
                        }
                        $snippet = (string) preg_replace(
                            '/(' . preg_quote($kw, '/') . ')/i',
                            '<strong>$1</strong>',
                            $snippet
                        );
                    }
                    $snippets[] = '…' . $snippet . '…';
                    for ($i = $start; $i <= $end; $i++) {
                        $usedIndexes[] = $i;
                    }
                    if (count($snippets) >= $maxSnippets) {
                        return $snippets;
                    }
                }
            }
        }

        return $snippets;
    }

    public function render()
    {
        return view('livewire.lms-topic-search');
    }
}
