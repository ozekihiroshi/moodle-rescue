<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$path = (getenv('PYAI_V46_CONTENT_PATH') ?: '/workspace/sample-content/introduction-to-python/integrated-practices-v46.json');
$items = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

function v46_html_list(array $items, bool $ordered = false): string {
    $tag = $ordered ? 'ol' : 'ul';
    return '<' . $tag . '>' . implode('', array_map(fn($v) => '<li>' . format_text($v, FORMAT_MARKDOWN, ['filter' => false]) . '</li>', $items)) . '</' . $tag . '>';
}

function v46_html(array $item, bool $ja): string {
    $t = $item[$ja ? 'ja' : 'en'];
    $h = fn($s) => s($s);
    $title = $ja ? '統合練習：章末課題へつながる軽い予行演習' : 'Integrated practice: a lighter rehearsal for the chapter project';
    $task = $ja ? '作るもの' : 'What to build';
    $done = $ja ? '完成条件' : 'Completion criteria';
    $hints = $ja ? '解答への手引き' : 'Step-by-step guide';
    $try = $ja ? 'まず自分で作り、実行結果を確認してから模範解答を開いてください。' : 'Build and run your own version before opening the model answer.';
    $model = $ja ? '模範解答と解説' : 'Model answer and explanation';
    $expected = $ja ? '代表的な確認結果' : 'Representative check';
    $transfer = $ja ? '値を変えて再確認する' : 'Change and recheck';
    $code = s($item['code']);
    $out = '<h2 style="margin-top:2em;padding-bottom:.35em;border-bottom:2px solid #0f6cbf">' . $h($title) . '</h2>';
    $out .= '<aside style="margin:1em 0;padding:.75em 1em;border-left:4px solid #5b7c99;background:#f6f8fa"><strong>' . ($ja ? '章末課題との接続：' : 'Connection to the chapter project: ') . '</strong>' . format_text($t['connection'], FORMAT_MARKDOWN, ['filter' => false]) . '</aside>';
    $out .= '<h3>' . $h($task) . '</h3><p>' . format_text($t['task'], FORMAT_MARKDOWN, ['filter' => false]) . '</p>';
    $out .= '<h3>' . $h($done) . '</h3>' . v46_html_list($t['completion']);
    $out .= '<h3>' . $h($hints) . '</h3>' . v46_html_list($t['hints'], true);
    $out .= '<p><strong>' . $h($try) . '</strong></p>';
    $out .= '<details style="margin:1em 0"><summary><strong>' . $h($model) . '</strong></summary>';
    $out .= '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . $code . '</code></pre>';
    if ($item['expected'] !== '') $out .= '<p><strong>' . $h($expected) . '</strong></p><pre>' . s($item['expected']) . '</pre>';
    $out .= '<p>' . format_text($t['explanation'], FORMAT_MARKDOWN, ['filter' => false]) . '</p></details>';
    $out .= '<h3>' . $h($transfer) . '</h3><p>' . format_text($t['transfer'], FORMAT_MARKDOWN, ['filter' => false]) . '</p>';
    return $out;
}

function v46_markdown(array $item, bool $ja): string {
    $t = $item[$ja ? 'ja' : 'en'];
    $lines = [];
    $lines[] = '## ' . ($ja ? '統合練習：章末課題へつながる軽い予行演習' : 'Integrated practice: a lighter rehearsal for the chapter project');
    $lines[] = '';
    $lines[] = '> **' . ($ja ? '章末課題との接続：' : 'Connection to the chapter project: ') . '**' . $t['connection'];
    $lines[] = '';
    $lines[] = '### ' . ($ja ? '作るもの' : 'What to build');
    $lines[] = '';
    $lines[] = $t['task'];
    $lines[] = '';
    $lines[] = '### ' . ($ja ? '完成条件' : 'Completion criteria');
    $lines[] = '';
    foreach ($t['completion'] as $v) $lines[] = '- ' . $v;
    $lines[] = '';
    $lines[] = '### ' . ($ja ? '解答への手引き' : 'Step-by-step guide');
    $lines[] = '';
    foreach ($t['hints'] as $i => $v) $lines[] = ($i + 1) . '. ' . $v;
    $lines[] = '';
    $lines[] = '**' . ($ja ? 'まず自分で作り、実行結果を確認してから模範解答を開いてください。' : 'Build and run your own version before opening the model answer.') . '**';
    $lines[] = '';
    $lines[] = '<details><summary><strong>' . ($ja ? '模範解答と解説' : 'Model answer and explanation') . '</strong></summary>';
    $lines[] = '';
    $lines[] = '```python';
    $lines[] = $item['code'];
    $lines[] = '```';
    if ($item['expected'] !== '') {
        $lines[] = '';
        $lines[] = '**' . ($ja ? '代表的な確認結果' : 'Representative check') . '**';
        $lines[] = '';
        $lines[] = '```text';
        $lines[] = $item['expected'];
        $lines[] = '```';
    }
    $lines[] = '';
    $lines[] = $t['explanation'];
    $lines[] = '';
    $lines[] = '</details>';
    $lines[] = '';
    $lines[] = '### ' . ($ja ? '値を変えて再確認する' : 'Change and recheck');
    $lines[] = '';
    $lines[] = $t['transfer'];
    $lines[] = '';
    return implode("\n", $lines);
}

$updated = [];
foreach ($items as $item) {
    $name = $item[$ja ? 'ja_page' : 'en_page'];
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $name], '*', MUST_EXIST);
    $before = $page->content;
    if ((int)$page->contentformat === (int)FORMAT_MARKDOWN) {
        $block = v46_markdown($item, $ja);
        $label = $ja ? '統合練習' : 'Integrated practice';
        $numbered = '## ' . $item['key'] . '.6 ' . $label;
        $plain = '## ' . $label;
        $start = strpos($before, $numbered);
        if ($start === false) $start = strpos($before, $plain);
        $summaryheading = $ja ? '## まとめ' : '## Summary';
        $end = $start !== false ? strpos($before, $summaryheading, $start) : false;
        if ($start === false || $end === false) {
            $count = 0;
            $page->content = $before;
        } else {
            $page->content = substr($before, 0, $start) . $block . substr($before, $end);
            $count = 1;
        }
        $summaryintro = $ja ? 'このレッスンと統合練習を終えると、次のことができます。' : 'After completing this lesson and its integrated practice, you can:';
        $summarypattern = '~^(## (?:Summary|まとめ)\s*\R)(?:(?:\R)?' . preg_quote($summaryintro, '~') . '\R+)*~mu';
        $page->content = preg_replace($summarypattern, '$1' . "\n" . $summaryintro . "\n\n", $page->content, 1);
    } else {
        $block = v46_html($item, $ja);
        $pattern = '~<h2[^>]*>[^<]*(?:Integrated practice|統合練習)[^<]*</h2>.*?(?=<h2[^>]*>(?:Summary|まとめ)</h2>)~su';
        $page->content = preg_replace($pattern, $block, $before, 1, $count);
        $summaryintro = $ja ? 'このレッスンと統合練習を終えると、次のことができます。' : 'After completing this lesson and its integrated practice, you can:';
        $page->content = preg_replace('~(<h2[^>]*>(?:Summary|まとめ)</h2>)(?:<p>(?:This lesson established the following:|After completing this lesson and its integrated practice, you can:|このレッスンでは、次を確認しました。|このレッスンと統合練習を終えると、次のことができます。)</p>)?~su', '$1<p>' . s($summaryintro) . '</p>', $page->content, 1);
    }
    if ($count !== 1) {
        throw new RuntimeException("Integrated-practice block not replaced exactly once: {$name} key={$item['key']} numbered=" . ($numbered ?? 'HTML') . " start=" . var_export($start ?? null, true) . " end=" . var_export($end ?? null, true) . " count={$count}");
    }
    $page->timemodified = time();
    $DB->update_record('page', $page);
    $updated[] = ['key' => $item['key'], 'page' => $name, 'id' => (int)$page->id, 'format' => (int)$page->contentformat];
}

rebuild_course_cache($course->id, true);
echo json_encode(['status' => 'ok', 'shortname' => $shortname, 'updated' => $updated, 'marker' => 'PYAI-V46-INTEGRATED-PRACTICE'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
