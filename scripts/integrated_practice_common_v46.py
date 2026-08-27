"""Structured bilingual content for the v46 integrated-practice upgrade."""


def lesson(key, en_page, ja_page, en, ja, code, expected=""):
    return {
        "key": key,
        "en_page": en_page,
        "ja_page": ja_page,
        "en": block(*en),
        "ja": block(*ja),
        "code": code.strip(),
        "expected": expected.strip(),
    }


def block(connection, task, completion, hints, explanation, transfer):
    return {
        "connection": connection,
        "task": task,
        "completion": completion,
        "hints": hints,
        "explanation": explanation,
        "transfer": transfer,
    }
