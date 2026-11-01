"""Chạy toàn bộ e2e:  python tests/e2e/run_all.py   (HS_BASE: xem common.py)"""
import sys

import common
import test_account
import test_admin
import test_auth
import test_law
import test_notes

MODULES = [test_auth, test_notes, test_law, test_account, test_admin]

if __name__ == '__main__':
    sys.exit(common.main(MODULES))
