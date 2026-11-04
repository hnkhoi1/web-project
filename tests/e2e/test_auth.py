"""E2E xác thực: chặn khi chưa đăng nhập, đăng nhập, đăng xuất, đăng ký, kích hoạt, quên mật khẩu."""
import sys

import common


async def run(t):
    pg = t.page
    # chưa đăng nhập -> bị đẩy về login, đăng nhập xong quay lại đúng trang
    await t.goto('tu-van.html')
    t.ok(pg.url.endswith('login.html'), 'chưa đăng nhập vào tu-van.html => về login.html')
    await pg.fill('#ident', 'demo'); await pg.fill('#password', 'sai'); await pg.click('button[type=submit]')
    t.ok(await pg.is_visible('#form-error'), 'sai mật khẩu hiện lỗi chung')
    await pg.fill('#password', '12345'); await pg.click('button[type=submit]')
    await pg.wait_for_url('**/tu-van.html')
    t.ok(True, 'đăng nhập demo/12345 rồi quay lại đúng trang đang vào (tu-van.html)')

    # đăng xuất -> Back không vào lại được trang nội bộ
    await t.logout()
    await pg.go_back(); await pg.wait_for_url('**/login.html')
    t.ok(pg.url.endswith('login.html'), 'đăng xuất rồi bấm Back không vào lại trang nội bộ')

    # đăng ký
    await pg.click('text=Đăng ký miễn phí')
    await pg.fill('#email', 'an@example.com'); await pg.fill('#displayName', 'Văn An')
    await pg.fill('#password', 'abc12345'); await pg.fill('#password2', 'abc12346')
    await pg.click('button[type=submit]')
    t.ok(await pg.locator('.field-err').count() >= 1, 'đăng ký: mật khẩu nhập lại không khớp bị báo lỗi')
    await pg.fill('#password2', 'abc12345'); await pg.click('button[type=submit]'); await pg.wait_for_url('**/index.html')
    t.ok(await pg.is_visible('.verify-banner'), 'đăng ký xong tự đăng nhập + hiện thanh chưa kích hoạt')
    t.ok('Văn An' in await pg.inner_text('#greet'), 'trang chủ chào đúng tên')
    await t.goto('tu-van.html')
    t.ok(await pg.is_visible('#chat-input'), 'chưa kích hoạt vẫn dùng đủ chức năng')
    await pg.click('#activate-btn'); await pg.click('#act-go'); await pg.wait_for_load_state()
    t.ok(await pg.locator('.verify-banner').count() == 0, 'kích hoạt xong thanh thông báo biến mất')

    # quên mật khẩu qua OTP
    await t.logout(); await t.goto('forgot-password.html')
    await pg.fill('#email', 'an@example.com'); await pg.click('#f1 button')
    otp = (await pg.inner_text('.otp')).strip()
    await pg.fill('#otp', '000000' if otp != '000000' else '111111'); await pg.click('#f2 button')
    t.ok(await pg.is_visible('#form-error'), 'OTP sai bị từ chối')
    await pg.fill('#otp', otp); await pg.click('#f2 button')
    await pg.fill('#new-pw', 'mkmoi9999'); await pg.fill('#new-pw2', 'mkmoi9999'); await pg.click('#f3 button[type=submit]')
    await pg.wait_for_url('**/login.html')
    t.ok(await pg.locator('.alert-ok').count() == 1 and 'index' not in pg.url, 'đặt lại mật khẩu xong về login (không tự đăng nhập)')
    await pg.fill('#ident', 'an@example.com'); await pg.fill('#password', 'abc12345'); await pg.click('button[type=submit]')
    t.ok(await pg.is_visible('#form-error'), 'mật khẩu cũ bị từ chối sau khi đặt lại')
    # bước Back ở trên để lại hs_next=tu-van.html nên đăng nhập xong có thể về trang đó thay vì index
    await pg.fill('#password', 'mkmoi9999'); await pg.click('button[type=submit]'); await pg.wait_for_url(lambda u: not u.endswith('login.html'))
    t.ok(True, 'đăng nhập bằng mật khẩu mới')


if __name__ == '__main__':
    sys.exit(common.main([sys.modules[__name__]]))
