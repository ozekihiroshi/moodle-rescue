<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$items = json_decode(file_get_contents((getenv('PYAI_V46_CONTENT_PATH') ?: '/workspace/sample-content/introduction-to-python/integrated-practices-v46.json')), true, 512, JSON_THROW_ON_ERROR);
$errors = [];
$checked = [];

foreach ($items as $item) {
    $name = $item[$ja ? 'ja_page' : 'en_page'];
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $name]);
    if (!$page) {
        $errors[] = "missing page: {$name}";
        continue;
    }
    $content = $page->content;
    $practice = $ja ? '統合練習：章末課題へつながる軽い予行演習' : 'Integrated practice: a lighter rehearsal for the chapter project';
    $required = $ja
        ? [$practice, '章末課題との接続', '作るもの', '完成条件', '解答への手引き', '模範解答と解説', '値を変えて再確認する', 'このレッスンと統合練習を終えると']
        : [$practice, 'Connection to the chapter project', 'What to build', 'Completion criteria', 'Step-by-step guide', 'Model answer and explanation', 'Change and recheck', 'After completing this lesson and its integrated practice'];
    foreach ($required as $needle) {
        if (!str_contains($content, $needle)) $errors[] = "{$item['key']} missing {$needle}";
    }
    if (substr_count($content, $practice) !== 1) $errors[] = "{$item['key']} practice heading count is not one";
    $summaryintro = $ja ? 'このレッスンと統合練習を終えると、次のことができます。' : 'After completing this lesson and its integrated practice, you can:';
    if (substr_count($content, $summaryintro) !== 1) $errors[] = "{$item['key']} summary outcome introduction count is not one";
    if (!str_contains($content, '<details') || !str_contains($content, '<summary>')) $errors[] = "{$item['key']} collapsible answer missing";
    $searchable = (int)$page->contentformat === (int)FORMAT_HTML ? html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8') : $content;
    if (!str_contains($searchable, $item['code'])) $errors[] = "{$item['key']} model code mismatch";
    $summarypos = max(strpos($content, $ja ? '>まとめ<' : '>Summary<') ?: 0, strpos($content, $ja ? '## まとめ' : '## Summary') ?: 0);
    $practicepos = strpos($content, $practice);
    if ($practicepos === false || $summarypos <= $practicepos) $errors[] = "{$item['key']} summary is not after practice";
    if ((int)$page->contentformat === FORMAT_MARKDOWN && !str_contains($content, '```python')) $errors[] = "{$item['key']} Markdown code fence missing";
    if ((int)$page->contentformat === FORMAT_HTML && !str_contains($content, '<pre')) $errors[] = "{$item['key']} HTML code block missing";
    $checked[] = ['key' => $item['key'], 'page' => $name, 'id' => (int)$page->id];
}

if (count($checked) !== 23) $errors[] = 'expected 23 checked lessons, got ' . count($checked);
if ($errors) {
    fwrite(STDERR, json_encode(['status' => 'error', 'shortname' => $shortname, 'errors' => $errors], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(1);
}
echo json_encode(['status' => 'ok', 'shortname' => $shortname, 'checked' => count($checked), 'marker' => 'PYAI-V46-INTEGRATED-PRACTICE-VERIFIED'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
