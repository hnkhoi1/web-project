import { Head, Link } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { search as lawSearch } from '@/routes/law';

const reasons = [
    {
        num: '01',
        title: 'Hỏi bằng lời văn tự nhiên',
        body: 'Không cần biết số điều hay thuật ngữ pháp lý. Cứ mô tả đúng chuyện đang xảy ra, hệ thống sẽ tìm điều luật phù hợp.',
    },
    {
        num: '02',
        title: 'Biết cả hai chiều',
        body: (
            <>
                Không chỉ mức xử lý với người vi phạm, bạn còn biết{' '}
                <b>mình có quyền được bồi thường những khoản gì</b> với tư cách
                người bị hại.
            </>
        ),
    },
    {
        num: '03',
        title: 'Lưu lại điều cần nhớ',
        body: 'Ghi chú riêng giúp bạn giữ câu trả lời và điều luật quan trọng, xem lại trên điện thoại, máy tính bảng hay máy tính.',
    },
];

export default function About() {
    return (
        <>
            <Head title="Giới thiệu" />

            <div className="space-y-10">
                <section className="space-y-3">
                    <p className="text-sm font-medium text-muted-foreground">
                        Về chúng tôi
                    </p>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        Pháp luật hình sự, trình bày theo cách ai cũng hiểu được
                    </h1>
                    <p className="max-w-3xl text-muted-foreground">
                        Hình Sự Tra Cứu giúp thu hẹp khoảng cách giữa văn bản
                        luật đầy thuật ngữ và những câu hỏi rất đời thường: "Bị
                        đánh thì được đền bù gì?", "Lừa tiền qua mạng có đòi lại
                        được không?". Bạn nhận câu trả lời trong vài giây, bằng
                        chính ngôn ngữ bạn dùng để hỏi.
                    </p>
                </section>

                <section className="space-y-4">
                    <h2 className="text-xl font-semibold">
                        Vì sao chọn chúng tôi
                    </h2>
                    <div className="grid gap-4 md:grid-cols-3">
                        {reasons.map((r) => (
                            <Card key={r.num}>
                                <CardContent className="space-y-2">
                                    <div className="text-sm text-muted-foreground">
                                        {r.num}
                                    </div>
                                    <h3 className="font-medium">{r.title}</h3>
                                    <p className="text-sm text-muted-foreground">
                                        {r.body}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </section>

                <section className="space-y-4">
                    <h2 className="text-xl font-semibold">Cách sử dụng</h2>
                    <div className="grid gap-4 md:grid-cols-3">
                        <Card>
                            <CardContent className="space-y-2">
                                <div className="text-sm text-muted-foreground">
                                    Bước 1
                                </div>
                                <h3 className="font-medium">
                                    Mô tả tình huống
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Vào trang{' '}
                                    <Link href="/tu-van" className="underline">
                                        Tư vấn
                                    </Link>
                                    , gõ câu hỏi bằng lời thường ngày; càng chi
                                    tiết (số tiền, tỉ lệ thương tích) càng chính
                                    xác.
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="space-y-2">
                                <div className="text-sm text-muted-foreground">
                                    Bước 2
                                </div>
                                <h3 className="font-medium">
                                    Nhận phân tích ngay
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Điều luật áp dụng, mức xử lý tham khảo và
                                    quyền lợi bồi thường bạn có thể yêu cầu nếu
                                    là người bị hại.
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="space-y-2">
                                <div className="text-sm text-muted-foreground">
                                    Bước 3
                                </div>
                                <h3 className="font-medium">
                                    Đối chiếu &amp; lưu lại
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Xem chi tiết ở trang{' '}
                                    <Link
                                        href={lawSearch.url()}
                                        className="underline"
                                    >
                                        Tra cứu
                                    </Link>
                                    , lưu vào ghi chú, hoặc{' '}
                                    <Link href="/lien-he" className="underline">
                                        gửi câu hỏi
                                    </Link>{' '}
                                    nếu cần người xem xét kỹ hơn.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <section className="space-y-2">
                    <h2 className="text-xl font-semibold">
                        Một lưu ý quan trọng
                    </h2>
                    <p className="text-muted-foreground">
                        Hình Sự Tra Cứu là công cụ hỗ trợ tra cứu và tư vấn sơ
                        bộ. Đây <b>không phải</b> ý kiến pháp lý chính thức và
                        không thay thế luật sư. Với vụ việc phức tạp hoặc có giá
                        trị lớn, hãy liên hệ luật sư hoặc cơ quan có thẩm quyền.
                    </p>
                </section>
            </div>
        </>
    );
}
