<?php

namespace App\Console\Commands;

use App\Models\LawEmbedding;
use App\Search\ArticleRepository;
use App\Search\EmbeddingClient;
use App\Search\LawPassages;
use Illuminate\Console\Command;

class SearchStatus extends Command
{
    protected $signature = 'search:status';

    protected $description = 'Check that the chatbot is ready: law data, search mode, embedder and stored vectors';

    public function handle(ArticleRepository $articles, EmbeddingClient $client): int
    {
        $passages = LawPassages::all($articles);
        $articleCount = count($articles->all());
        $blend = (bool) config('search.blend.enabled');
        $ok = true;

        $this->line('<options=bold>Chatbot</>');

        if ($articleCount === 0) {
            $this->error('  ✗ Chưa có dữ liệu luật. Chạy: php artisan migrate --seed');

            return self::FAILURE;
        }
        $this->info("  ✓ Dữ liệu luật: {$articleCount} điều, ".(count($passages) - $articleCount).' khoản');
        $this->info('  ✓ Chế độ từ khóa luôn sẵn sàng, không cần dịch vụ nào khác');

        $this->newLine();
        $this->line('<options=bold>Tìm kiếm ngữ nghĩa (tùy chọn)</>');
        $this->line('  Chế độ: '.($blend ? 'BẬT (SEARCH_BLEND_ENABLED=true)' : 'tắt (chỉ dùng từ khóa)'));

        $healthy = $client->healthy();
        $this->line('  Embedder ('.config('search.embedder_url').'): '.($healthy ? '<info>chạy</info>' : '<comment>không phản hồi</comment>'));

        $model = (string) config('search.embedder_model');
        $stored = LawEmbedding::query()->where('model', $model)->get()
            ->keyBy(fn (LawEmbedding $r): string => $r->dieu_luat_id.'/'.$r->so_khoan);
        $stale = 0;
        foreach ($passages as [$articleId, $clause, $text]) {
            $row = $stored->get($articleId.'/'.$clause);
            if ($row === null || $row->content_hash !== hash('sha256', $text)) {
                $stale++;
            }
        }
        $this->line('  Vector: '.(count($passages) - $stale).'/'.count($passages).' dòng đã có'.($stale > 0 ? " ({$stale} dòng thiếu hoặc cũ)" : ''));

        $this->newLine();
        if (! $blend) {
            $this->line('  Muốn bật: đặt SEARCH_BLEND_ENABLED=true trong .env, chạy docker compose --profile semantic up -d --build, rồi php artisan search:embed');
        } else {
            if (! $healthy) {
                $this->warn('  ! Đã bật chế độ trộn nhưng embedder không chạy: chatbot vẫn trả lời bằng từ khóa. Chạy: docker compose --profile semantic up -d');
                $ok = false;
            }
            if ($stale > 0) {
                $this->warn('  ! Thiếu vector: chạy php artisan search:embed');
                $ok = false;
            }
            if ($ok) {
                $this->info('  ✓ Chế độ trộn sẵn sàng');
            }
        }

        return self::SUCCESS;
    }
}
