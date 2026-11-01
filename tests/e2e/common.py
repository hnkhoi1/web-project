"""Khung chung cho e2e (Playwright).

Chọn nơi chạy bằng biến môi trường HS_BASE:
  (không đặt) hoặc file   mở thẳng file html/ bằng file://   (mặc định)
  http                    tự bật server tĩnh của Python trên cổng trống
  http://... / file://... dùng đúng địa chỉ này, ví dụ http://localhost/web-project/html/ (XAMPP)

Mỗi file test_*.py có hàm `async def run(t)`; mỗi module chạy trong một browser context
riêng nên localStorage luôn trống khi bắt đầu.
"""
import asyncio
import functools
import http.server
import os
import sys
import threading
import traceback
from pathlib import Path

from playwright.async_api import async_playwright

ROOT = Path(__file__).resolve().parents[2]
TIMEOUT_MS = 10_000

# Console Windows mặc định cp1252, không in được tiếng Việt.
for stream in (sys.stdout, sys.stderr):
    if hasattr(stream, 'reconfigure'):
        stream.reconfigure(encoding='utf-8')


class _QuietHandler(http.server.SimpleHTTPRequestHandler):
    def log_message(self, *args):
        pass


def _start_static_server():
    handler = functools.partial(_QuietHandler, directory=str(ROOT))
    server = http.server.ThreadingHTTPServer(('127.0.0.1', 0), handler)
    threading.Thread(target=server.serve_forever, daemon=True).start()
    return server, f'http://127.0.0.1:{server.server_address[1]}/html/'


def resolve_base():
    """Trả về (base_url, server_hoặc_None)."""
    v = os.environ.get('HS_BASE', '').strip()
    if v in ('', 'file'):
        return ROOT.joinpath('html').as_uri() + '/', None
    if v == 'http':
        server, base = _start_static_server()
        return base, server
    if v.startswith(('http://', 'https://', 'file://')):
        return v.rstrip('/') + '/', None
    raise SystemExit(f'HS_BASE không hợp lệ: {v!r} (dùng file, http hoặc một URL tới thư mục html/)')


class T:
    """Trạng thái của một module test: trang đang mở, kết quả, lỗi JS."""

    def __init__(self, name, base, page):
        self.name, self.base, self.page = name, base, page
        self.results, self.js_errors = [], []

    def ok(self, cond, msg):
        self.results.append((bool(cond), msg))

    def url(self, path):
        return self.base + path

    async def goto(self, path):
        await self.page.goto(self.url(path))

    async def login(self, ident='demo', password='12345', expect='index.html'):
        await self.goto('login.html')
        await self.page.fill('#ident', ident)
        await self.page.fill('#password', password)
        await self.page.click('button[type=submit]')
        await self.page.wait_for_url('**/' + expect)

    async def logout(self):
        await self.page.click('.user-btn')
        await self.page.click('#logout-btn')
        await self.page.wait_for_url('**/login.html')

    async def register(self, email, name, password):
        await self.goto('register.html')
        await self.page.fill('#email', email)
        await self.page.fill('#displayName', name)
        await self.page.fill('#password', password)
        await self.page.fill('#password2', password)
        await self.page.click('button[type=submit]')
        await self.page.wait_for_url('**/index.html')

    async def overflows(self):
        return await self.page.evaluate('document.documentElement.scrollWidth > window.innerWidth + 1')


async def _run_module(browser, module, base):
    name = Path(module.__file__).stem
    ctx = await browser.new_context(viewport={'width': 1280, 'height': 800})
    await ctx.route('**/fonts.g*', lambda r: r.abort())   # chạy được khi không có mạng
    page = await ctx.new_page()
    page.set_default_timeout(TIMEOUT_MS)
    page.set_default_navigation_timeout(3 * TIMEOUT_MS)   # lần mở trang đầu tiên đôi khi chậm (máy vừa khởi động)
    t = T(name, base, page)
    page.on('pageerror', lambda e: t.js_errors.append(str(e)))
    page.on('dialog', lambda d: asyncio.ensure_future(d.accept()))
    try:
        await module.run(t)
    except Exception as e:                                # module dừng giữa chừng: ghi FAIL, chạy module sau
        t.ok(False, f'dừng ở bước sau "{t.results[-1][1] if t.results else "bắt đầu"}": {type(e).__name__}: {str(e).splitlines()[0]}')
        if os.environ.get('HS_DEBUG'):
            traceback.print_exc()
    t.ok(not t.js_errors, 'không có lỗi JavaScript' + (': ' + '; '.join(t.js_errors[:3]) if t.js_errors else ''))
    await ctx.close()
    return t


async def run_modules(modules):
    base, server = resolve_base()
    print(f'HS_BASE = {base}\n')
    failed = total = 0
    try:
        async with async_playwright() as p:
            browser = await p.chromium.launch()
            for m in modules:
                t = await _run_module(browser, m, base)
                print(f'== {t.name} ==')
                for passed, msg in t.results:
                    print(('PASS ' if passed else 'FAIL ') + msg)
                    total += 1
                    failed += not passed
                print()
            await browser.close()
    finally:
        if server:
            server.shutdown()
    print(f'Tổng: {total - failed}/{total} PASS' + (f', {failed} FAIL' if failed else ''))
    return 1 if failed else 0


def main(modules):
    return asyncio.run(run_modules(modules))
