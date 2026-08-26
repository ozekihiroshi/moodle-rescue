from pathlib import Path


root = Path(__file__).resolve().parents[1]
target = root / "sample-content/introduction-to-python/python-lab/project-files/ja/projects/library-manager/check_library_manager.py"
target.write_text(
    '''#!/usr/bin/env python3
"""Project 2.4の図書記録管理を、入力CSVを変更せずに確認します。"""
from __future__ import annotations

import os
import runpy
from pathlib import Path

os.environ["LIBRARY_MANAGER_TARGET"] = str(Path(__file__).with_name("library_manager.py"))
canonical = Path(__file__).resolve().parents[3] / "projects" / "library-manager" / "check_library_manager.py"
runpy.run_path(str(canonical), run_name="__main__")
''',
    encoding="utf-8",
    newline="\n",
)
print(target)
