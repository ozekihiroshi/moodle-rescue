#!/usr/bin/env python3
"""Publish the exact Stage 1 contracts and verify untruncated inspection output."""

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"
PROJECTS = BASE / "python-lab/project-files"
REFERENCE = BASE / "reference-solutions/project-3a"


def replace_once(path: Path, old: str, new: str) -> None:
    text = path.read_text(encoding="utf-8")
    count = text.count(old)
    if count != 1:
        raise RuntimeError(f"Expected one occurrence in {path}, found {count}: {old[:80]!r}")
    path.write_text(text.replace(old, new), encoding="utf-8", newline="\n")


EN_CONTRACT = """`count_district_values(records)` counts raw values, including inconsistent spelling or whitespace.

### Stage 1 function contract

| Function | Return value |
|---|---|
| `load_records(path)` | A pandas DataFrame read from `path`, preserving the CSV row order and column order |
| `build_school_date_view(records)` | A new DataFrame sorted by `school_id` ascending and then `date` ascending, with a new index starting at 0; `records` is unchanged |
| `count_district_values(records)` | A two-column DataFrame named `district, records`; raw district strings remain distinct and rows follow the order in which each value first appears |

The completed `main()` is supplied. It displays both 37-row tables with `to_string(index=False)` so pandas cannot replace middle rows with an ellipsis. It displays district values with Python quotes, making leading or trailing whitespace visible.
"""


JA_CONTRACT = """`count_district_values(records)`は空白や大文字・小文字の違いを直さず、記録された値を数えます。

### 第1段階の3関数の契約

| 関数 | 返す値 |
|---|---|
| `load_records(path)` | `path`のCSVを読み込んだpandas DataFrame。CSVの行順と列順を保つ |
| `build_school_date_view(records)` | `school_id`の昇順、次に`date`の昇順で並べ、indexを0から振り直した新しいDataFrame。`records`自体は変更しない |
| `count_district_values(records)` | `district, records`の2列を持つDataFrame。原資料の地区文字列を区別し、各値が最初に現れた順で並べる |

完成済みの`main()`は、二つの37行表を`to_string(index=False)`で表示するため、pandasが中間行を省略記号へ置き換えることはありません。地区名はPythonの引用符付きで表示し、前後空白も目で判別できるようにします。
"""


def update_briefs() -> None:
    for path in [BASE / "project-3a-brief-en.md", PROJECTS / "projects/school-meal-review/PROJECT_BRIEF.md"]:
        replace_once(
            path,
            "`count_district_values(records)` counts raw values, including inconsistent spelling or whitespace.\n",
            EN_CONTRACT,
        )
        replace_once(path, "→ check inspect_school_meals.py\n", "→ complete inspect_school_meals.py and pass its automatic check\n")
        replace_once(path, "→ check meal_delivery_review.py\n", "→ complete meal_delivery_review.py and pass its automatic check\n")
    for path in [BASE / "project-3a-brief-ja.md", PROJECTS / "ja/projects/school-meal-review/PROJECT_BRIEF.md"]:
        replace_once(
            path,
            "`count_district_values(records)`は空白や大文字・小文字の違いを直さず、記録された値を数えます。\n",
            JA_CONTRACT,
        )
        replace_once(path, "→ inspect_school_meals.pyを確認する\n", "→ inspect_school_meals.pyを完成させ、自動確認に合格する\n")
        replace_once(path, "→ meal_delivery_review.pyを確認する\n", "→ meal_delivery_review.pyを完成させ、自動確認に合格する\n")


def update_program(path: Path, solution: bool) -> None:
    replace_once(
        path,
        '    """Return counts for the raw district values as recorded."""\n',
        '    """Return raw district counts as a district/records DataFrame."""\n',
    )
    if solution:
        replace_once(
            path,
            '    return records["district"].value_counts(dropna=False, sort=False)\n',
            '    counts = records["district"].value_counts(dropna=False, sort=False)\n'
            '    return counts.rename_axis("district").reset_index(name="records")\n',
        )
    replace_once(
        path,
        '    print(records.to_string(index=False))\n',
        '    print(records.to_string(index=False, line_width=200))\n',
    )
    replace_once(
        path,
        '    print(school_date_view.to_string(index=False))\n',
        '    print(school_date_view.to_string(index=False, line_width=200))\n',
    )
    replace_once(
        path,
        '    print(district_counts.to_string())\n',
        '    print(district_counts.to_string(index=False, formatters={"district": repr}))\n',
    )


def update_checker(path: Path) -> None:
    replace_once(
        path,
        "from pandas.testing import assert_frame_equal, assert_series_equal\n",
        "from pandas.testing import assert_frame_equal\n",
    )
    replace_once(
        path,
        '        expected = raw["district"].value_counts(dropna=False, sort=False)\n'
        '        assert_series_equal(actual, expected)\n',
        '        counts = raw["district"].value_counts(dropna=False, sort=False)\n'
        '        expected = counts.rename_axis("district").reset_index(name="records")\n'
        '        assert_frame_equal(actual, expected)\n',
    )
    old = '''        for school_id in raw["school_id"].unique():
            assert school_id in result.stdout
        assert digest(SOURCE) == before
'''
    new = '''        def section_lines(start, end):
            section = result.stdout.split(start, 1)[1].split(end, 1)[0]
            return [line for line in section.splitlines() if line.strip()]

        all_rows = section_lines("ALL RECORDS:\\n", "SCHOOL/DATE VIEW:\\n")
        sorted_rows = section_lines("SCHOOL/DATE VIEW:\\n", "MISSING VALUES:\\n")
        assert len(all_rows) == len(raw) + 1, f"ALL RECORDS has {len(all_rows) - 1} data rows"
        assert len(sorted_rows) == len(raw) + 1, f"SCHOOL/DATE VIEW has {len(sorted_rows) - 1} data rows"
        assert not any("..." in line for line in all_rows + sorted_rows), "a table was truncated"
        for school_id in raw["school_id"].unique():
            assert school_id in result.stdout
        whitespace_value = next(value for value in raw["district"] if value != value.strip())
        assert repr(whitespace_value) in result.stdout, "district whitespace is not visible"
        assert digest(SOURCE) == before
'''
    replace_once(path, old, new)


def main() -> None:
    update_briefs()
    for language in ["projects", "ja/projects"]:
        project = PROJECTS / language / "school-meal-review"
        update_program(project / "inspect_school_meals.py", False)
        update_checker(project / "check_inspect_school_meals.py")
    update_program(REFERENCE / "inspect_school_meals.py", True)
    print({"briefs": 4, "function_contracts": 3, "untruncated_tables": 2, "stage1_checks": 5})


if __name__ == "__main__":
    main()
