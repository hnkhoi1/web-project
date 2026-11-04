"""E2E trang quản trị (T32): chặn người dùng thường, tổng quan, người dùng, liên hệ, lịch sử hỏi đáp.
Khi chưa có API (T14), trang dùng stub: tài khoản demo đóng vai admin, lịch sử là 3 dòng mẫu."""
import sys

import common


async def run(t):
    pg = t.page

    # người dùng thường: bị chặn, rồi gửi 1 liên hệ để admin xử lý
    await t.register('an@example.com', 'Văn An', 'abc12345')
    await t.goto('admin.html')
    t.ok(await pg.is_visible('#admin-denied') and await pg.locator('#admin-nav').count() == 0, 'người dùng thường bị chặn ở trang quản trị')
    await t.goto('admin-nguoi-dung.html')
    t.ok(await pg.is_visible('#admin-denied') and await pg.locator('#users-body').count() == 0, 'người dùng thường không xem được danh sách người dùng')
    await t.goto('lien-he.html'); await pg.fill('#noi_dung', 'Cho tôi hỏi về Điều 134.')
    await pg.click('#contact-form button[type=submit]'); await pg.wait_for_selector('.alert-ok')

    # admin: vào từ trang Dữ liệu demo
    await t.logout(); await t.login()
    await t.goto('database.html'); await pg.click('#admin-link'); await pg.wait_for_url('**/admin.html')
    nav = pg.locator('#admin-nav a')
    t.ok(await nav.count() == 4 and await pg.get_attribute('#admin-nav a[aria-current]', 'href') == 'admin.html', 'menu quản trị có 4 mục, đánh dấu Tổng quan')
    await pg.wait_for_selector('#stat-grid .stat')
    tiles = await pg.locator('#stat-grid .stat b').all_inner_texts()
    t.ok(tiles[:3] == ['2', '0', '1'], f'tổng quan: 2 người dùng, 0 ghi chú, 1 liên hệ chưa trả lời ({tiles})')
    t.ok('Văn An' in await pg.inner_text('#recent-contacts'), 'tổng quan: hiện liên hệ mới nhất')
    t.ok(await pg.inner_text('#nav-contacts .badge') == '1', 'menu hiện số liên hệ chưa trả lời')

    # liên hệ
    await pg.click('#admin-nav >> text=Liên hệ'); await pg.wait_for_selector('.contact-item')
    await pg.click('.contact-item >> text=Đánh dấu đã trả lời')
    t.ok('Đã trả lời' in await pg.inner_text('.contact-item .badge') and await pg.is_hidden('#nav-contacts .badge'), 'đổi trạng thái liên hệ sang đã trả lời')
    await pg.select_option('#contact-filter', 'moi')
    t.ok(await pg.locator('.contact-item').count() == 0, 'lọc "Chưa trả lời" không còn liên hệ nào')
    await pg.reload(); await pg.wait_for_selector('.contact-item')
    t.ok('Đã trả lời' in await pg.inner_text('.contact-item .badge'), 'trạng thái liên hệ được lưu sau khi tải lại')

    # lịch sử hỏi đáp
    await pg.click('#admin-nav >> text=Lịch sử hỏi đáp'); await pg.wait_for_selector('#history-body tr')
    total = await pg.locator('#history-body tr').count()
    await pg.fill('#history-q', '321')
    t.ok(total >= 1 and await pg.locator('#history-body tr').count() == 1, 'lịch sử: lọc theo số điều')

    # người dùng
    await pg.click('#admin-nav >> text=Người dùng'); await pg.wait_for_selector('#users-body tr')
    rows = pg.locator('#users-body tr')
    t.ok(await rows.count() == 2 and await rows.filter(has_text='demo').get_by_role('button').is_disabled(), 'danh sách 2 tài khoản, không xóa được tài khoản demo')
    await pg.fill('#user-q', 'an@'); t.ok(await rows.count() == 1, 'tìm người dùng theo email')
    await rows.get_by_role('button', name='Xóa tài khoản an@example.com').click(); await pg.click('#user-delete-yes')
    await pg.fill('#user-q', '')
    await pg.wait_for_function("document.querySelectorAll('#users-body tr').length === 1")
    t.ok(True, 'xóa tài khoản người dùng sau khi xác nhận')
    await t.logout(); await pg.fill('#ident', 'an@example.com'); await pg.fill('#password', 'abc12345'); await pg.click('button[type=submit]')
    t.ok(await pg.is_visible('#form-error'), 'tài khoản bị admin xóa không đăng nhập được')

    # điện thoại
    await t.login(); await pg.set_viewport_size({'width': 375, 'height': 700})
    for p in ('admin.html', 'admin-nguoi-dung.html', 'admin-lien-he.html', 'admin-lich-su.html'):
        await t.goto(p)
        t.ok(not await t.overflows(), f'điện thoại: {p} không tràn ngang')


if __name__ == '__main__':
    sys.exit(common.main([sys.modules[__name__]]))
