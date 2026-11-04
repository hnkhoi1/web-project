"""E2E tài khoản: khóa tài khoản demo, tùy chỉnh, dữ liệu demo, xóa tài khoản, liên hệ."""
import sys

import common


async def run(t):
    pg = t.page
    await t.login()

    # tài khoản demo bị khóa sửa/xóa
    await t.goto('ho-so.html')
    t.ok(await pg.is_visible('#demo-lock') and await pg.is_disabled('#delete-btn') and await pg.is_disabled('#displayName'), 'hồ sơ demo bị khóa, không xóa được')
    await t.goto('doi-mat-khau.html')
    t.ok(await pg.is_disabled('#current'), 'đổi mật khẩu demo bị khóa')

    # tùy chỉnh tối
    await t.goto('tuy-chinh.html'); await pg.check('input[name=theme][value=dark]'); await pg.click('button[type=submit]')
    await t.goto('index.html')
    t.ok(await pg.evaluate('document.documentElement.dataset.theme') == 'dark', 'tùy chỉnh giao diện tối được lưu')

    # liên hệ
    await t.goto('lien-he.html'); await pg.fill('#noi_dung', 'ngắn'); await pg.click('#contact-form button[type=submit]')
    t.ok(await pg.locator('#contact-form .field-err').count() >= 2, 'liên hệ: email trống và nội dung quá ngắn bị báo lỗi')
    await pg.fill('#email', 'demo@example.com'); await pg.fill('#noi_dung', 'Tôi muốn hỏi thêm về Điều 134.')
    await pg.click('#contact-form button[type=submit]'); await pg.wait_for_selector('.alert-ok')
    t.ok(True, 'liên hệ: gửi thành công có thông báo')

    # tài khoản thường: xem database, xóa tài khoản
    await t.logout(); await t.register('an@example.com', 'Văn An', 'abc12345')
    await t.goto('database.html')
    t.ok(await pg.locator('#users-body tr').count() == 2, 'database có demo + 1 tài khoản mới')
    await t.goto('ho-so.html'); await pg.click('#delete-btn')
    await pg.fill('#del-pw', 'saimk'); await pg.click('#delete-form button[type=submit]')
    t.ok(await pg.locator('#del-pw-err').count() == 1, 'xóa tài khoản: sai mật khẩu bị chặn')
    await pg.fill('#del-pw', 'abc12345'); await pg.click('#delete-form button[type=submit]'); await pg.wait_for_url('**/login.html')
    await pg.fill('#ident', 'an@example.com'); await pg.fill('#password', 'abc12345'); await pg.click('button[type=submit]')
    t.ok(await pg.is_visible('#form-error'), 'tài khoản đã xóa không đăng nhập lại được')
    await pg.fill('#ident', 'demo'); await pg.fill('#password', '12345'); await pg.click('button[type=submit]'); await pg.wait_for_url('**/index.html')
    t.ok(True, 'tài khoản demo vẫn đăng nhập bình thường sau khi xóa tài khoản kia')


if __name__ == '__main__':
    sys.exit(common.main([sys.modules[__name__]]))
