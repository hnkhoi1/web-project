<?php

namespace App\Console\Commands;

use App\Models\LawEmbedding;
use App\Search\ArticleRepository;
use App\Search\EmbeddingClient;
use Illuminate\Console\Command;

class SearchEmbed extends Command
{
    private const BATCH = 32;

    protected $signature = 'search:embed {--wait=60 : Seconds to wait for the embedder}';

    protected $description = 'Store embeddings for every article and clause whose text changed';

    public function handle(ArticleRepository $articles, EmbeddingClient $client): int
    {
        if (! $this->waitForEmbedder($client, (int) $this->option('wait'))) {
            $this->warn('Embedder không phản hồi; giữ nguyên các vector hiện có.');

            return self::SUCCESS;
        }

        $model = (string) config('search.embedder_model');
        $existing = LawEmbedding::query()->where('model', $model)->get()
            ->keyBy(fn (LawEmbedding $r): string => $r->dieu_luat_id.'/'.$r->so_khoan);

        $pending = [];
        foreach ($articles->all() as $article) {
            $pending[] = [$article['id'], LawEmbedding::ARTICLE, $this->join([$article['tieu_de'], $article['tom_tat'], $article['tu_khoa']])];
            foreach ($article['khoan'] as $clause) {
                $pending[] = [$article['id'], $clause['so_khoan'], $this->join([$clause['dieu_kien'], $clause['noi_dung'], $clause['tu_khoa']])];
            }
        }

        $total = count($pending);
        $todo = array_values(array_filter($pending, function (array $p) use ($existing): bool {
            $row = $existing->get($p[0].'/'.$p[1]);

            return $row === null || $row->content_hash !== hash('sha256', $p[2]);
        }));

        $done = 0;
        foreach (array_chunk($todo, self::BATCH) as $batch) {
            $vectors = $client->embedPassages(array_column($batch, 2));
            if ($vectors === null) {
                $this->warn("Embedder lỗi sau {$done}/".count($todo).' dòng; các dòng còn lại giữ nguyên.');

                return self::SUCCESS;
            }
            foreach ($batch as $i => [$articleId, $soKhoan, $text]) {
                LawEmbedding::query()->updateOrCreate(
                    ['dieu_luat_id' => $articleId, 'so_khoan' => $soKhoan, 'model' => $model],
                    ['content_hash' => hash('sha256', $text), 'vector' => $vectors[$i]],
                );
                $done++;
            }
        }

        $this->info("Đã nhúng {$done} dòng mới trên tổng {$total}.");

        return self::SUCCESS;
    }

    private function waitForEmbedder(EmbeddingClient $client, int $seconds): bool
    {
        $deadline = time() + max(0, $seconds);
        while (! $client->healthy()) {
            if (time() >= $deadline) {
                return false;
            }
            sleep(2);
        }

        return true;
    }

    /** @param  list<string|null>  $parts */
    private function join(array $parts): string
    {
        return trim(implode("\n", array_map(fn (?string $p): string => (string) $p, $parts)));
    }
}
