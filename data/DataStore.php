<?php
/**
 * DataStore.php
 * -----------------------------------------------------------------
 * Lớp truy xuất dữ liệu thay thế MySQL/PDO — toàn bộ đọc/ghi dùng
 * hàm PHP thuần (require, json_encode/json_decode, file_get_contents/
 * file_put_contents), không có bất kỳ câu lệnh SQL nào.
 *
 *   - "Bảng" dieu_luat  -> backend/data/dieu_luat_data.php (mảng PHP, chỉ đọc)
 *   - "Bảng" lich_su_hoi_dap -> backend/data/lich_su_hoi_dap.json (append)
 *   - "Bảng" lien_he         -> backend/data/lien_he.json (append)
 *
 * Đây vẫn là một "cơ sở dữ liệu" theo đúng nghĩa yêu cầu đề bài (database
 * dùng PHP), chỉ khác là lưu trữ dạng tệp thay vì máy chủ MySQL — phù hợp
 * ràng buộc chỉ dùng HTML/CSS/JavaScript/PHP (không dùng ngôn ngữ SQL).
 * -----------------------------------------------------------------
 */

final class DataStore
{
    private static function dataDir(): string
    {
        return __DIR__;
    }

    /** @return array<int, array> toàn bộ điều luật */
    public static function allDieuLuat(): array
    {
        static $rows = null;
        if ($rows === null) {
            $rows = require self::dataDir() . '/dieu_luat_data.php';
        }
        return $rows;
    }

    public static function dieuLuatById(int $id): ?array
    {
        foreach (self::allDieuLuat() as $row) {
            if ($row['id'] === $id) return $row;
        }
        return null;
    }

    /** @return array<int, array> các điều cùng chương, khác id */
    public static function lienQuan(string $chuong, int $excludeId, int $limit = 4): array
    {
        $out = [];
        foreach (self::allDieuLuat() as $row) {
            if ($row['chuong'] === $chuong && $row['id'] !== $excludeId) {
                $out[] = ['id' => $row['id'], 'tieu_de' => $row['tieu_de']];
                if (count($out) >= $limit) break;
            }
        }
        return $out;
    }

    // ---------------------------------------------------------
    // Kho JSON dùng chung (đọc/ghi nguyên khối, đủ nhẹ cho demo)
    // ---------------------------------------------------------
    private static function readJsonStore(string $file): array
    {
        $path = self::dataDir() . '/' . $file;
        if (!file_exists($path)) return [];
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private static function writeJsonStore(string $file, array $data): bool
    {
        $path = self::dataDir() . '/' . $file;
        return file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    }

    /** Ghi log một lượt hỏi-đáp chatbot. */
    public static function logHoiDap(string $cauHoi, ?int $dieuLuatId, ?float $diemTuongDong): void
    {
        $rows = self::readJsonStore('lich_su_hoi_dap.json');
        $rows[] = [
            'id' => count($rows) + 1,
            'cau_hoi' => $cauHoi,
            'dieu_luat_id' => $dieuLuatId,
            'diem_tuong_dong' => $diemTuongDong,
            'thoi_gian' => date('c'),
        ];
        self::writeJsonStore('lich_su_hoi_dap.json', $rows);
    }

    /** Lưu một câu hỏi gửi từ trang Liên hệ, trả về id vừa tạo. */
    public static function saveLienHe(array $entry): int
    {
        $rows = self::readJsonStore('lien_he.json');
        $id = count($rows) + 1;
        $rows[] = array_merge(['id' => $id, 'trang_thai' => 'moi', 'tao_luc' => date('c')], $entry);
        self::writeJsonStore('lien_he.json', $rows);
        return $id;
    }
}
