"""E2E ghi chú ở trang chủ: thêm, sửa, ghim, tìm, xóa, chống XSS, tách theo người dùng, responsive."""
import sys

import common

XSS = '<img src=x onerror="window.__xss=1"><script>window.__xss=1</script>'


async def run(t):
    pg = t.page
    await t.login()

    async def save_note(title, content='', pinned=False):
        await pg.click('#note-new')
        await pg.fill('#note-title', title); await pg.fill('#note-content', content)
        if pinned:
            await pg.check('#note-pinned')
        await pg.click('#note-save')

    notes = pg.locator('#notes-grid .note')
    t.ok(await pg.is_visible('#notes-empty') and await notes.count() == 0, 'chưa có ghi chú: hiện trạng thái rỗng')

    await pg.click('#note-new'); await pg.click('#note-save')
    t.ok(await pg.is_visible('#note-err'), 'không cho lưu khi cả tiêu đề và nội dung rỗng')
    await pg.click('#note-cancel')

    await save_note('Họp nhóm', 'Chuẩn bị slide')
    await save_note('Mã độc', XSS)
    t.ok(await notes.count() == 2 and not await pg.is_visible('#notes-empty'), 'thêm 2 ghi chú, trạng thái rỗng biến mất')
    t.ok(XSS in await notes.filter(has_text='Mã độc').inner_text() and await pg.evaluate('window.__xss') is None,
         'nội dung chứa HTML/script hiển thị dạng chữ, không chạy')

    await notes.filter(has_text='Họp nhóm').get_by_role('button', name='Ghim Họp nhóm').click()
    t.ok((await notes.first.locator('h3').inner_text()).startswith('📌 Họp nhóm'), 'ghi chú được ghim lên đầu')

    await notes.filter(has_text='Họp nhóm').get_by_role('button', name='Sửa ghi chú').click()
    await pg.fill('#note-title', 'Họp nhóm tối'); await pg.click('#note-save')
    t.ok(await notes.filter(has_text='Họp nhóm tối').count() == 1, 'sửa tiêu đề ghi chú')

    await pg.fill('#notes-q', 'không-có-ghi-chú-này')
    await pg.wait_for_selector('#notes-empty:not([hidden])')
    t.ok('Không có ghi chú nào khớp' in await pg.inner_text('#notes-empty'), 'tìm không thấy: thông báo đúng')
    await pg.fill('#notes-q', 'slide')
    await pg.wait_for_function("document.querySelectorAll('#notes-grid .note').length === 1")
    t.ok('Họp nhóm tối' in await notes.first.inner_text(), 'tìm theo nội dung ra đúng 1 ghi chú')
    await pg.fill('#notes-q', '')
    await pg.wait_for_function("document.querySelectorAll('#notes-grid .note').length === 2")

    await notes.filter(has_text='Mã độc').get_by_role('button', name='Xóa ghi chú').click()
    await pg.click('#confirm-yes')
    t.ok(await notes.count() == 1, 'xóa ghi chú sau khi xác nhận')

    # ghi chú của demo không hiện với người khác
    await t.logout(); await t.register('binh@example.com', 'Bình', 'abc12345')
    t.ok(await notes.count() == 0, 'ghi chú của người A không hiện cho người B')

    # điện thoại
    await pg.set_viewport_size({'width': 375, 'height': 700}); await t.goto('index.html')
    t.ok(await pg.is_visible('.nav-toggle') and not await pg.is_visible('#site-nav'), 'điện thoại: menu hamburger, nav ẩn')
    t.ok(not await t.overflows(), 'điện thoại: không bị tràn ngang')


if __name__ == '__main__':
    sys.exit(common.main([sys.modules[__name__]]))
