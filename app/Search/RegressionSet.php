<?php

namespace App\Search;

/** The original question list (tests/fixtures/search-regression.json) that any tuning must keep passing. */
final readonly class RegressionSet
{
    /**
     * @param  list<array{string, int}>  $questions
     * @param  list<string>  $none
     * @param  list<array{int, string, string}>  $followUps
     * @param  list<array{string, string, int}>  $conversations
     * @param  list<array{int, string, string, string}>  $chatContext
     */
    private function __construct(
        private array $questions,
        private array $none,
        private array $followUps,
        private array $conversations,
        private array $chatContext,
    ) {}

    public static function load(): self
    {
        $data = json_decode((string) file_get_contents(base_path('tests/fixtures/search-regression.json')), true, flags: JSON_THROW_ON_ERROR);

        return new self($data['questions'], $data['none'], $data['follow_ups'], $data['conversations'], $data['chat_context']);
    }

    /** @return array<string, int> Question to the article it must find. */
    public function found(): array
    {
        $found = [];
        foreach ($this->questions as [$question, $id]) {
            $found[$question] = $id;
        }
        foreach ($this->followUps as [$id, $question]) {
            $found[$question] = $id;
        }
        foreach ($this->conversations as [$initial, $continued, $id]) {
            $found[$initial] = $id;
            $found[$continued] = $id;
        }

        return $found;
    }

    /** @return list<string> Questions that must find nothing. */
    public function none(): array
    {
        return $this->none;
    }

    /** @return list<string> Every question and chat message whose vector the tests replay. */
    public function questions(): array
    {
        return [...array_keys($this->found()), ...$this->none, ...array_column($this->chatContext, 2)];
    }
}
