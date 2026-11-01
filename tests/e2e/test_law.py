"""E2E phần luật: chatbot, lưu câu trả lời vào ghi chú, tra cứu, chi tiết điều luật."""
import sys

import common


async def run(t):
    pg = t.page
    await t.login(); await t.goto('tu-van.html')

    # câu ngoài phạm vi, hỏi ở phiên chat mới (chatbot đang chờ bổ sung thì sẽ ghép câu vào ngữ cảnh cũ)
    await pg.fill('#chat-input', 'thời tiết hôm nay thế nào'); await pg.press('#chat-input', 'Enter'); await pg.wait_for_selector('.msg.bot >> nth=1')
    t.ok('chưa tìm được điều luật phù hợp' in await pg.inner_text('.msg.bot >> nth=1'), 'câu ngoài phạm vi -> chưa tìm được điều luật')
    await t.goto('tu-van.html')

    # chatbot
    await pg.click('.suggestion >> nth=0'); await pg.wait_for_selector('.msg.bot >> nth=1')
    txt = await pg.inner_text('#chat-log')
    t.ok('Điều 134' in txt and 'Quyền lợi' in txt, 'chatbot trả lời Điều 134 kèm quyền lợi')
    # hồi quy: "tôi bị đánh" phải ra Điều 134 (không phải 321 đánh bạc), hỏi thêm tỉ lệ thương tích
    await pg.fill('#chat-input', 'tôi bị đánh'); await pg.press('#chat-input', 'Enter'); await pg.wait_for_selector('.msg.bot >> nth=2')
    txt = await pg.inner_text('.msg.bot >> nth=2')
    t.ok('Điều 134' in txt and '321' not in txt and 'tỉ lệ tổn thương' in txt, '"tôi bị đánh" -> Điều 134 + hỏi tỉ lệ thương tích (không ra đánh bạc)')
    await pg.fill('#chat-input', 'tôi đánh bạc bị công an bắt'); await pg.press('#chat-input', 'Enter'); await pg.wait_for_selector('.msg.bot >> nth=3')
    t.ok('Điều 321' in await pg.inner_text('.msg.bot >> nth=3'), '"tôi đánh bạc..." -> Điều 321')
    await pg.click('.msg.bot >> nth=3 >> text=+ Lưu vào ghi chú'); await pg.wait_for_selector('text=✓ Đã lưu')
    await t.goto('index.html')
    t.ok(await pg.locator('.note').count() == 1, 'ghi chú từ chatbot xuất hiện ở trang chủ')

    # tra cứu
    await t.goto('tra-cuu.html?q=' + 'lừa đảo')
    t.ok(await pg.locator('#results .result-row').count() >= 1, 'tra cứu "lừa đảo" có kết quả (khôi phục từ ?q=)')
    await pg.fill('#q', '134'); await pg.click('#search-form button[type=submit]')
    t.ok(await pg.locator('#results .result-row').count() == 1 and 'Điều 134' in await pg.inner_text('#results'), 'tra cứu "134" ra đúng 1 điều')
    await t.goto('dieu-luat-chi-tiet.html?id=134')
    t.ok(await pg.locator('#detail .khoan').count() >= 2, 'chi tiết Điều 134 hiển thị các khoản')
    await t.goto('dieu-luat-chi-tiet.html?id=999')
    t.ok('Không tìm thấy điều luật' in await pg.inner_text('#detail'), '?id=999 -> không tìm thấy điều luật')

    # điện thoại
    await pg.set_viewport_size({'width': 375, 'height': 700}); await t.goto('tu-van.html')
    t.ok(not await t.overflows(), 'điện thoại: trang chatbot không tràn ngang')


if __name__ == '__main__':
    sys.exit(common.main([sys.modules[__name__]]))
