#!/usr/bin/env python3
"""Strip Stripe test literals from database/seeds/DemoSeeder.php (for git filter-branch)."""
from __future__ import annotations

import pathlib
import re
import sys

PATH = pathlib.Path("database/seeds/DemoSeeder.php")
if not PATH.is_file():
    sys.exit(0)

text = PATH.read_text(encoding="utf-8", errors="strict")

text, n1 = re.subn(
    r"('val'\s*=>\s*)\"sk_test_[^\"]+\"",
    r"\1(string) env('DEMO_STRIPE_TEST_SECRET_KEY', '')",
    text,
    count=1,
)
text, n2 = re.subn(
    r"('val'\s*=>\s*)\"pk_test_[^\"]+\"",
    r"\1(string) env('DEMO_STRIPE_TEST_PUBLISHABLE_KEY', '')",
    text,
    count=1,
)

PATH.write_text(text, encoding="utf-8", newline="\n")
