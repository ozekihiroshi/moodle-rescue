<?php
// Refine the introductory comparison after the structural v39 insertion.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$name = $ja ? 'レッスン4.1：レコードと関数からオブジェクトへ'
    : 'Lesson 4.1: From records and functions to objects';
$page = $DB->get_record('page', ['course' => $course->id, 'name' => $name], '*', MUST_EXIST);

function v40_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

$recordcode = <<<'PY'
item = {
    "item_id": "E001",
    "name": "Laptop 01",
    "borrower_id": None,
}

def loan_item(record, borrower_id):
    if record["borrower_id"] is not None:
        raise ValueError("already on loan")
    record["borrower_id"] = borrower_id

loan_item(item, "M014")
print(item)
PY;
$classcode = <<<'PY'
class EquipmentItem:
    def __init__(self, item_id, name):
        self.item_id = item_id
        self.name = name
        self.borrower_id = None

    def loan_to(self, borrower_id):
        if self.borrower_id is not None:
            raise ValueError("already on loan")
        self.borrower_id = borrower_id

first = EquipmentItem("E001", "Laptop 01")
second = EquipmentItem("E002", "Portable Projector")
first.loan_to("M014")
print(first.borrower_id)
print(second.borrower_id)
PY;

if ($ja) {
    $page->content = '<div class="python-sample-lesson"><h2>辞書と関数で動くところから始める</h2>'
        . '<p>第2章では、一件のデータを辞書で表し、関数へ渡して処理しました。その方法は誤りではありません。対象が少なく、状態変更の規則も単純なら、辞書と関数の方が短く明確な場合があります。ここでは同じ貸出処理をクラスでも表し、何が変わるかをコードから確認します。</p>'
        . '<h2>このレッスンを終えるとできること</h2><ul><li>クラスとインスタンスを区別する</li><li>属性とメソッドをコードから特定する</li><li><code>__init__</code>と<code>self</code>を使って独立した個体を作る</li><li>辞書と関数の方が簡単な場合も判断する</li></ul>'
        . '<p><strong>必須：</strong>クラス、インスタンス、属性、メソッド、<code>__init__</code>、<code>self</code>。</p>'
        . '<h2>4.1.1 辞書と関数で機材を表す</h2><p>辞書は状態を保持し、関数は受け取った辞書を変更します。コードを実行し、どこにデータがあり、どこに規則があるかを指で追ってください。</p>' . v40_code($recordcode)
        . '<h3>この方法で増えていくもの</h3><p>貸出、返却、名称変更などの関数が増えると、どの関数がどのキーを変更できるかを人が把握する必要があります。ここで初めて、状態と操作を一つの単位にする価値が現れます。</p>'
        . '<h2>4.1.2 クラスは生成方法と振る舞いを定義する</h2><p><code>EquipmentItem</code>はクラスです。クラスを呼び出して作った<code>first</code>と<code>second</code>がインスタンスです。同じクラスから作られても、それぞれが自分の属性を持ちます。</p>' . v40_code($classcode)
        . '<h2>4.1.3 selfは呼び出しを受けた個体を表す</h2><p><code>first.loan_to("M014")</code>を実行したとき、メソッド内の<code>self</code>は<code>first</code>です。したがって<code>second.borrower_id</code>は変わりません。<code>self</code>を省略すると、どの個体の属性を読み書きするかを示せません。</p>'
        . '<h2>4.1.4 属性とメソッドを読み分ける</h2><p><code>item.name</code>のように状態を保持する値が属性、<code>item.loan_to()</code>のようにオブジェクトへ依頼する操作がメソッドです。括弧の有無だけでなく、状態と操作のどちらを表すかで区別します。</p>'
        . '<h3>同じ値と同じオブジェクトは別の問題</h3><p>二つの機材が同じ名称を持っていても別の機材です。<code>==</code>は値の比較に、<code>is</code>は同一オブジェクトかの確認に使います。通常の業務判定で<code>is</code>を文字列比較の代わりに使いません。</p>'
        . '<h2>4.1.5 統合練習</h2><p><code>rename(new_name)</code>を追加します。二件を作り、一件だけ名称を変更して、もう一件が変わらないことを確認してください。その後、この例が一件だけで貸出規則も増えないなら、辞書と関数のままにする判断も説明してください。</p>'
        . '<h2>まとめ</h2><ul><li>クラスは状態と関連する操作をまとめる設計単位です。</li><li>インスタンスごとに属性が独立し、<code>self</code>が対象の個体を示します。</li><li>クラスは常に正解ではなく、状態と規則が増える場面で効果を発揮します。</li></ul>'
        . '<h2>次のレッスンへ</h2><p>次は、代入場所をクラスへ移すだけでなく、生成と状態変更の条件をメソッドで守ります。</p><p style="display:none">PYAI-V40-LESSON41-COMPARISON</p></div>';
} else {
    $page->content = '<div class="python-sample-lesson"><h2>Begin with working records and functions</h2>'
        . '<p>Chapter 2 represented one record with a dictionary and passed it to functions. That approach is valid. For a few records with simple transitions it may remain shorter and clearer. Here the same lending operation is expressed with a class so that the difference can be observed in code.</p>'
        . '<h2>After this lesson you can</h2><ul><li>distinguish a class from an instance</li><li>identify attributes and methods in code</li><li>use <code>__init__</code> and <code>self</code> to create independent instances</li><li>recognise when records and functions remain simpler</li></ul>'
        . '<p><strong>Required:</strong> class, instance, attribute, method, <code>__init__</code>, and <code>self</code>.</p>'
        . '<h2>4.1.1 Represent equipment with a record and function</h2><p>The dictionary stores state and the function changes the supplied record. Run it and identify where the data and the rule are located.</p>' . v40_code($recordcode)
        . '<h3>What grows with this design</h3><p>As loan, return, and rename functions accumulate, a reader must track which functions may change which keys. That growth creates a reason to put state and permitted operations into one unit.</p>'
        . '<h2>4.1.2 A class defines construction and behaviour</h2><p><code>EquipmentItem</code> is the class. <code>first</code> and <code>second</code> are instances made from it. Each instance owns its attributes.</p>' . v40_code($classcode)
        . '<h2>4.1.3 self is the receiving instance</h2><p>During <code>first.loan_to("M014")</code>, <code>self</code> is <code>first</code>. The second borrower therefore remains unchanged. Without <code>self</code>, the method cannot identify which instance to read or change.</p>'
        . '<h2>4.1.4 Distinguish attributes and methods</h2><p><code>item.name</code> is stored state: an attribute. <code>item.loan_to()</code> asks the object to perform an operation: a method. Read their role, not only the presence of parentheses.</p>'
        . '<h3>Equal values and identical objects are different questions</h3><p>Two items may share a name and still be separate items. <code>==</code> compares values; <code>is</code> checks object identity. Do not use <code>is</code> as a substitute for ordinary string comparison.</p>'
        . '<h2>4.1.5 Integrated practice</h2><p>Add <code>rename(new_name)</code>. Create two items, rename one, and verify that the other did not change. Then explain why a one-record version with no growing rules might remain a dictionary and functions.</p>'
        . '<h2>Summary</h2><ul><li>A class is a design unit joining related state and operations.</li><li>Instances have independent attributes, and <code>self</code> identifies the receiver.</li><li>A class is not automatic; it becomes valuable as state and rules grow.</li></ul>'
        . '<h2>Next lesson</h2><p>Next, methods will protect construction and state-transition rules rather than merely relocate assignments.</p><p style="display:none">PYAI-V40-LESSON41-COMPARISON</p></div>';
}
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);
rebuild_course_cache($course->id, true);
echo json_encode(['status'=>'ok','course_id'=>(int)$course->id,'page_id'=>(int)$page->id,
    'shortname'=>$course->shortname,'marker'=>'PYAI-V40-LESSON41-COMPARISON'], JSON_UNESCAPED_UNICODE) . PHP_EOL;
