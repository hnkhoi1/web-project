<?php

namespace App\Search;

/** Follow-up handling for the chatbot (port of continueQuestion and the pendingUnit logic in js/chatbot.js). */
class ChatConversation
{
    public function __construct(private readonly RankingService $ranking) {}

    /**
     * The full question to answer, given the user's message and the pending context (if any).
     *
     * @param  array{question: string, unit: ?string, article_id: int}|null  $pending
     */
    public function resolve(string $message, ?array $pending): string
    {
        if ($pending === null) {
            return $this->cap($message);
        }

        if (preg_match('/^\s*([0-9]+(?:[.,][0-9]+)?)\s*(%|tri[eệ]u|t(?:ỷ|ỉ|y))?\s*(?:đồng)?\s*$/iu', $message, $m) === 1) {
            $unit = ($m[2] ?? '') !== '' ? $m[2] : $pending['unit'];
            $suffix = $unit === '%' ? '%' : ($unit ? " {$unit} đồng" : '');

            return $this->cap("{$pending['question']} {$m[1]}{$suffix}");
        }

        $current = $this->ranking->rank($message);
        $newTopicMin = (float) config($current->engine === 'blend' ? 'search.blend.new_topic_min_score' : 'search.new_topic_min_score');
        if ($current->status !== 'none'
            && $current->results[0]->score >= $newTopicMin
            && $current->results[0]->id !== $pending['article_id']) {
            return $this->cap($message); // A new question about another article drops the pending context.
        }

        return $this->cap("{$pending['question']}. Thông tin bổ sung: {$message}");
    }

    /**
     * Context to keep for the next message, or null when nothing is pending.
     *
     * @param  array<string, mixed>  $answer
     * @return array{question: string, unit: ?string, article_id: int}|null
     */
    public function nextPending(string $fullQuestion, array $answer): ?array
    {
        if (($answer['found'] ?? false) !== true || ($answer['mode'] ?? null) !== 'overview' || empty($answer['ask'])) {
            return null;
        }
        $ask = (string) $answer['ask'];
        $unit = preg_match('/tỉ lệ|phần trăm|%/iu', $ask) === 1 ? '%'
            : (preg_match('/triệu|giá trị tài sản/iu', $ask) === 1 ? 'triệu' : null);

        return ['question' => $fullQuestion, 'unit' => $unit, 'article_id' => (int) $answer['article']['id']];
    }

    private function cap(string $s): string
    {
        return mb_substr($s, 0, (int) config('search.max_question_chars'));
    }
}
