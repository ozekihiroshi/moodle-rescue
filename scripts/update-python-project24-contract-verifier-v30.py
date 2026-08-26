from pathlib import Path


path = Path(__file__).with_name("verify-python-project24-contract-v24.py")
text = path.read_text(encoding="utf-8")
old = '''    for language, target, expected in (
        ("en", EN / "library_manager.py", "starter program is not complete"),
        ("ja", JA / "library_manager.py", "\u30b9\u30bf\u30fc\u30bf\u30fc\u30d7\u30ed\u30b0\u30e9\u30e0\u306f\u672a\u5b8c\u6210\u3067\u3059"),
    ):
        env = os.environ.copy()
        env["LIBRARY_MANAGER_TARGET"] = str(target)
        env["LIBRARY_MANAGER_CHECK_LANGUAGE"] = language
        failed = subprocess.run([sys.executable, str(CHECKER)], cwd=cwd, env=env, text=True, capture_output=True, timeout=30)
        assert failed.returncode != 0, language
        assert expected in failed.stdout, failed.stdout
'''
new = '''    for language, target, expected in (
        ("en", EN / "library_manager.py", "[NG] parse_read / load_books: sample CSV and Boolean conversion"),
        ("ja", JA / "library_manager.py", "[NG] parse_read / load_books\uff1a\u30b5\u30f3\u30d7\u30ebCSV\u3068\u30d6\u30fc\u30eb\u5024\u5909\u63db"),
    ):
        env = os.environ.copy()
        env["LIBRARY_MANAGER_TARGET"] = str(target)
        env["LIBRARY_MANAGER_CHECK_LANGUAGE"] = language
        failed = subprocess.run([sys.executable, str(CHECKER)], cwd=cwd, env=env, text=True, capture_output=True, timeout=30)
        assert failed.returncode != 0, language
        assert failed.stdout.count("[NG]") == 10, failed.stdout
        assert expected in failed.stdout, failed.stdout
        assert "PROGRAM INCOMPLETE" in failed.stdout, failed.stdout
'''
if old in text:
    path.write_text(text.replace(old, new, 1), encoding="utf-8")
elif new not in text:
    raise SystemExit("Project 2.4 staged-verifier anchor missing")
print("Project 2.4 contract verifier aligned with staged checker feedback")
