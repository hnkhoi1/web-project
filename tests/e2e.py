import asyncio, os, sys
from playwright.async_api import async_playwright
BASE = 'file://' + os.getcwd() + '/'
res = []
def ok(c, m): res.append(('PASS ' if c else 'FAIL ') + m)
async def main():
    try:
      await run()
    finally:
      print('\n'.join(res))
async def run():
    async with async_playwright() as p:
        b = await p.chromium.launch()
        ctx = await b.new_context(viewport={'width': 1280, 'height': 800})
        await ctx.route('**/fonts.g*', lambda r: r.abort())
        pg = await ctx.new_page(); errs = []
        pg.on('pageerror', lambda e: errs.append(str(e)))
        pg.on('dialog', lambda d: asyncio.ensure_future(d.accept()))
        # 1. chưa đăng nhập -> bị đẩy về login
        await pg.goto(BASE + 'tu-van.html'); ok(pg.url.endswith('login.html'), 'chưa đăng nhập vào tu-van.html => về login.html')
        # 2. đăng nhập sai / đúng
        await pg.fill('#ident', 'demo'); await pg.fill('#password', 'sai'); await pg.click('button[type=submit]')
        ok(await pg.is_visible('#form-error'), 'sai mật khẩu hiện lỗi chung')
        await pg.fill('#password', '12345'); await pg.click('button[type=submit]')
        await pg.wait_for_url('**/tu-van.html'); ok(True, 'đăng nhập demo/12345 rồi quay lại đúng trang đang vào (tu-van.html)')
        # 3. chatbot
        await pg.click('.suggestion >> nth=0'); await pg.wait_for_selector('.msg.bot >> nth=1')
        txt = await pg.inner_text('#chat-log'); ok('Điều 134' in txt and 'Quyền lợi' in txt, 'chatbot trả lời Điều 134 kèm quyền lợi')
        # 3b. hồi quy: "tôi bị đánh" phải ra Điều 134 (không phải 321 đánh bạc), hỏi thêm tỉ lệ thương tích
        await pg.fill('#chat-input', 'tôi bị đánh'); await pg.press('#chat-input', 'Enter'); await pg.wait_for_selector('.msg.bot >> nth=2')
        txt = await pg.inner_text('.msg.bot >> nth=2'); ok('Điều 134' in txt and '321' not in txt and 'tỉ lệ tổn thương' in txt, '"tôi bị đánh" -> Điều 134 + hỏi tỉ lệ thương tích (không ra đánh bạc)')
        await pg.fill('#chat-input', 'tôi đánh bạc bị công an bắt'); await pg.press('#chat-input', 'Enter'); await pg.wait_for_selector('.msg.bot >> nth=3')
        ok('Điều 321' in await pg.inner_text('.msg.bot >> nth=3'), '"tôi đánh bạc..." -> Điều 321')
        await pg.click('text=+ Lưu vào ghi chú'); await pg.goto(BASE + 'index.html')
        ok(await pg.locator('.note').count() == 1, 'ghi chú từ chatbot xuất hiện ở trang chủ')
        # 4. tài khoản demo bị khóa sửa/xóa
        await pg.goto(BASE + 'ho-so.html'); ok(await pg.is_visible('#demo-lock') and await pg.is_disabled('#delete-btn') and await pg.is_disabled('#displayName'), 'hồ sơ demo bị khóa, không xóa được')
        await pg.goto(BASE + 'doi-mat-khau.html'); ok(await pg.is_disabled('#current'), 'đổi mật khẩu demo bị khóa')
        # 5. tùy chỉnh tối cho demo
        await pg.goto(BASE + 'tuy-chinh.html'); await pg.check('input[name=theme][value=dark]'); await pg.click('button[type=submit]')
        await pg.goto(BASE + 'index.html'); ok(await pg.evaluate("document.documentElement.dataset.theme") == 'dark', 'tùy chỉnh giao diện tối được lưu')
        # 6. đăng xuất -> đăng ký tài khoản mới
        await pg.click('.user-btn'); await pg.click('#logout-btn'); await pg.wait_for_url('**/login.html')
        await pg.click('text=Đăng ký miễn phí')
        await pg.fill('#email', 'an@example.com'); await pg.fill('#displayName', 'Văn An'); await pg.fill('#password', 'abc12345'); await pg.fill('#password2', 'abc12346')
        await pg.click('button[type=submit]'); ok(await pg.locator('.field-err').count() >= 1, 'đăng ký: mật khẩu nhập lại không khớp bị báo lỗi')
        await pg.fill('#password2', 'abc12345'); await pg.click('button[type=submit]'); await pg.wait_for_url('**/index.html')
        ok(await pg.is_visible('.verify-banner'), 'đăng ký xong tự đăng nhập + hiện thanh chưa kích hoạt')
        ok('Văn An' in await pg.inner_text('#greet'), 'trang chủ chào đúng tên')
        # tài khoản chưa kích hoạt vẫn dùng được chatbot
        await pg.goto(BASE + 'tu-van.html'); ok(await pg.is_visible('#chat-input'), 'chưa kích hoạt vẫn dùng đủ chức năng')
        await pg.click('#activate-btn'); await pg.click('#act-go'); await pg.wait_for_load_state()
        ok(await pg.locator('.verify-banner').count() == 0, 'kích hoạt xong thanh thông báo biến mất')
        # 7. xem database + xóa tài khoản mới
        await pg.goto(BASE + 'database.html'); rows = await pg.locator('#users-body tr').count(); ok(rows == 2, 'database có demo + 1 tài khoản mới')
        await pg.goto(BASE + 'ho-so.html'); await pg.click('#delete-btn'); await pg.fill('#del-pw', 'saimk'); await pg.click('#delete-form button[type=submit]')
        ok(await pg.locator('#del-pw-err').count() == 1, 'xóa tài khoản: sai mật khẩu bị chặn')
        await pg.fill('#del-pw', 'abc12345'); await pg.click('#delete-form button[type=submit]'); await pg.wait_for_url('**/login.html')
        await pg.fill('#ident', 'an@example.com'); await pg.fill('#password', 'abc12345'); await pg.click('button[type=submit]')
        ok(await pg.is_visible('#form-error'), 'tài khoản đã xóa không đăng nhập lại được')
        await pg.fill('#ident', 'demo'); await pg.fill('#password', '12345'); await pg.click('button[type=submit]'); await pg.wait_for_url('**/index.html')
        ok(True, 'tài khoản demo vẫn đăng nhập bình thường sau khi xóa tài khoản kia')
        # 8. quên mật khẩu qua OTP
        await pg.click('.user-btn'); await pg.click('#logout-btn'); await pg.goto(BASE + 'register.html')
        await pg.fill('#email', 'b@example.com'); await pg.fill('#displayName', 'Bình'); await pg.fill('#password', 'abc12345'); await pg.fill('#password2', 'abc12345'); await pg.click('button[type=submit]'); await pg.wait_for_url('**/index.html')
        await pg.click('.user-btn'); await pg.click('#logout-btn'); await pg.goto(BASE + 'forgot-password.html')
        await pg.fill('#email', 'b@example.com'); await pg.click('#f1 button'); otp = (await pg.inner_text('.otp')).strip()
        await pg.fill('#otp', '000000' if otp != '000000' else '111111'); await pg.click('#f2 button'); ok(await pg.is_visible('#form-error'), 'OTP sai bị từ chối')
        await pg.fill('#otp', otp); await pg.click('#f2 button'); await pg.fill('#new-pw', 'mkmoi9999'); await pg.fill('#new-pw2', 'mkmoi9999'); await pg.click('#f3 button[type=submit]')
        await pg.wait_for_url('**/login.html'); ok(await pg.locator('.alert-ok').count() == 1 and 'index' not in pg.url, 'đặt lại mật khẩu xong về login (không tự đăng nhập)')
        await pg.fill('#ident', 'b@example.com'); await pg.fill('#password', 'mkmoi9999'); await pg.click('button[type=submit]'); await pg.wait_for_url('**/index.html'); ok(True, 'đăng nhập bằng mật khẩu mới')
        # 9. responsive: điện thoại
        await pg.set_viewport_size({'width': 375, 'height': 700}); await pg.goto(BASE + 'index.html')
        ok(await pg.is_visible('.nav-toggle') and not await pg.is_visible('#site-nav'), 'điện thoại: menu hamburger, nav ẩn')
        over = await pg.evaluate('document.documentElement.scrollWidth > window.innerWidth + 1'); ok(not over, 'điện thoại: không bị tràn ngang')
        await pg.goto(BASE + 'tu-van.html'); over = await pg.evaluate('document.documentElement.scrollWidth > window.innerWidth + 1'); ok(not over, 'điện thoại: trang chatbot không tràn ngang')
        ok(not errs, 'không có lỗi JavaScript: ' + '; '.join(errs[:3]))
        await b.close()
asyncio.run(main())
