#!/usr/bin/env python3
"""日本語表示で正規のProject 2.4チェッカーを実行します。"""
import os
import runpy
from pathlib import Path

os.environ["LIBRARY_MANAGER_TARGET"] = str(Path(__file__).with_name("library_manager.py"))
os.environ["LIBRARY_MANAGER_SAMPLE"] = str(Path(__file__).parent / "data" / "books.csv")
os.environ["LIBRARY_MANAGER_CHECK_LANGUAGE"] = "ja"
canonical = Path(__file__).resolve().parents[3] / "projects" / "library-manager" / "check_library_manager.py"
runpy.run_path(str(canonical), run_name="__main__")
