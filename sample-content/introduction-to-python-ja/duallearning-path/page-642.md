## 導入

**データを直す前に、何を問題とするか決める**

3.2では、必要な列と条件を指定して行を抽出しました。しかし、欠損や表記ゆれ、不可能な値を説明しないまま抽出すると、正しく動くコードから誤った結論が生まれます。データクリーニングは、見栄えを整えたり、都合の悪い行を削除したりする作業ではありません。元データを保持し、規則を先に定義し、問題を検出し、処置と結果を記録する工程です。

## このレッスンの到達目標

このレッスンを終えると、次のことができます。

- 原本、作業用、分析用のデータを分けて保持できる。
- 型変換失敗、欠損、表記ゆれ、範囲違反、項目間矛盾、重複を区別して検出できる。
- 複数の品質理由を残した確認対象表を作れる。
- 件数照合と監査記録により、分析用データが作られた過程を説明できる。

**学習経路:** 必須：3.3.1〜3.3.6　／　統合練習：3.3.7

## 3.3.1 原本を保ち、品質問題を定義する

## 3.3.2 原本・作業用・分析用データを分ける

`raw`は読み込んだ状態のまま保持します。`clean = raw.copy()`で作業用データを作り、問題の有無を表すフラグを追加します。そのうえで、定義した分析に使える行だけを`analysis`へ取り出します。三つを分けると、どの値を変え、どの行を分析対象外にしたかを追跡できます。

    raw = pd.read_csv(data_file)
    clean = raw.copy()
    print("Rows:", len(raw))
    print(raw.dtypes)
    print(raw.isna().sum())

### 修正前に型・欠損・カテゴリ・範囲を確認する

最初に`dtypes`、`isna().sum()`、カテゴリの`unique()`、数値列の`describe()`を確認します。この時点の件数が基準です。問題を見つける前に値を置き換えると、元からあった問題と処理によって生じた問題を区別できません。

## 3.3.3 型変換失敗と欠損を扱う

`pd.to_numeric(..., errors="coerce")`は数値へ変換できない文字を欠損値へ変えます。便利ですが、変換前から空だった値と、新しく変換に失敗した値は意味が違います。変換前の欠損マスクと変換後の欠損マスクの差を取り、列ごとの失敗件数を記録します。

    before_missing = clean["attended"].isna()
    converted = pd.to_numeric(clean["attended"], errors="coerce")
    conversion_failed = converted.isna() & ~before_missing
    clean["attended"] = converted
    print("Source missing:", int(before_missing.sum()))
    print("Conversion failures:", int(conversion_failed.sum()))

### 欠損と0を混同しない

出席者数の空欄は「未報告」であり、「出席者が0人」とは限りません。根拠なく0や平均値で補完すると、割合や合計の意味を変えます。`isna()`で欠損を明示し、集計から除くのか、確認待ちとして隔離するのか、信頼できる原資料で修正するのかを決めて記録します。

**確認:** 空欄を0へ置き換える前に、二つの値が業務上同じ意味か説明してください。

## 3.3.4 表記を整え、元の値を残す

地区名の前後空白や大文字・小文字の違いは、集計時に別カテゴリを作ります。作業列へ`str.strip()`と`str.title()`を適用し、元の文字列は`district_raw`に残します。ただし、似た名前を同じ意味だと推測して統合してはいけません。明示された表記規則または対応表が必要です。

    clean["district_raw"] = clean["district"]
    clean["district"] = clean["district"].astype("string").str.strip().str.title()
    changed = clean["district_raw"].astype("string") != clean["district"]
    print("Changed labels:", int(changed.sum()))

## 3.3.5 範囲・項目間制約・重複を検査する

人数と費用は0以上という単独の範囲規則に加え、この表では`registered >= attended >= completed`という項目間の制約があります。欠損を不正値と混同せず、規則ごとに名前付きマスクを作って件数を表示します。どの規則に違反したか分からない一つの巨大な条件式にはしません。

    missing_attended = clean["attended"].isna()
    completion_over_attendance = (
        clean["completed"].notna()
        & clean["attended"].notna()
        & (clean["completed"] > clean["attended"])
    )
    negative_cost = clean["material_cost"].notna() & (clean["material_cost"] < 0)
    print(int(missing_attended.sum()), int(completion_over_attendance.sum()), int(negative_cost.sum()))

### 業務キーで重複グループ全体を調べる

この表の1記録を「センター・月・コース」と定義するなら、その三列が業務キーです。`duplicated(subset=business_key, keep=False)`は重複グループの全行を示します。同じセンターが異なる月に現れることは正当なので、全列一致やセンターIDだけで判定してはいけません。

    business_key = ["centre_id", "month", "course"]
    duplicate_key = clean.duplicated(subset=business_key, keep=False)
    print("Duplicate-key rows:", int(duplicate_key.sum()))

## 3.3.6 確認対象と監査記録を残す

問題を検出しただけでは、正しい値は分かりません。原資料で確認できれば修正し、表記規則があれば正規化し、根拠がなければ欠損または確認待ちとして扱います。不可能な修了者数を出席者数へ合わせるといった推測はしません。ここでは行を削除せず`analysis_ready`フラグを付け、分析用の抽出を別に作ります。

    clean["analysis_ready"] = ~(missing_attended | completion_over_attendance | negative_cost | duplicate_key)
    analysis = clean.loc[clean["analysis_ready"]].copy()
    print("Raw:", len(raw), "ready:", len(analysis), "flagged:", int((~clean["analysis_ready"]).sum()))

### 問題理由を行単位の確認対象表へ残す

規則ごとの件数だけでは原資料のどの行を確認すべきか分かりません。個別フラグを残し、公開した順序ですべての該当理由を連結し、識別列とともに確認対象表へ取り出します。一行が複数規則へ違反した場合も理由を一つに絞りません。

    issue_rules = [(missing_attended, 'missing attended'), (duplicate_key, 'duplicate business key')]
    clean['issue'] = ''
    for mask, label in issue_rules:
        clean.loc[mask, 'issue'] += label + '; '
    records_to_verify = clean.loc[~clean['analysis_ready'], ['month','centre_id','course','issue']]

### 監査記録とassertで再検証する

監査記録には、問題名、検出規則、影響件数、実施した処置、未解決件数を残します。0件だった検査も、確認を実施した証拠です。最後に、元件数が分析可能件数とフラグ件数の合計に一致すること、分析用データが同じ制約を満たすことを`assert`で確認します。

    assert len(raw) == len(clean)
    assert len(clean) == int(clean["analysis_ready"].sum()) + int((~clean["analysis_ready"]).sum())
    assert not (analysis["completed"] > analysis["attended"]).any()

### 課題へ進む前に境界値を検証する

原本は`raw`として保持し、作業表は`raw.copy(deep=True)`で分けます。数値は`pd.to_numeric(..., errors="coerce")`で変換してから、欠損、負数、比較可能な項目間制約を個別に判定します。重複キーは文字列の前後空白を除いた後、`duplicated(..., keep=False)`でグループ全行を示します。

**確認:** 一行が複数規則へ違反したとき、理由を一つに絞らないのはなぜでしょうか。

## 統合練習：章末課題へつながる軽い予行演習

**章末課題との接続：**

小さな給水容器記録を使い、原本を黙って直さず、品質フラグと監査経路を作ります。

### 作るもの

`litres`を`errors='coerce'`で変換します。数値欠損、負数、`container_id,date`重複全行をフラグ化し、有効表と要確認表へ分けてください。

### 完成条件

- 原表のdeep copyを保持します。

- 一行が複数フラグを持って構いません。

- 全原本行が必ず一方の出力へ入ります。

### 解答への手引き

1.  `isna()`、`< 0`、`duplicated(..., keep=False)`を使います。

2.  要確認マスクは`|`で結合します。

3.  分割後に行数を照合します。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> **模範解答と解説**
>
> ```
> import pandas as pd
> records = pd.DataFrame({
>     "container_id": ["C01", "C02", "C02", "C03"],
>     "date": ["2026-09-01", "2026-09-01", "2026-09-01", "2026-09-02"],
>     "litres": [20, "", 18, -2],
> })
> original = records.copy(deep=True)
> working = records.copy(deep=True)
> working["litres"] = pd.to_numeric(working["litres"], errors="coerce")
> working["missing_number"] = working["litres"].isna()
> working["negative_number"] = working["litres"].lt(0).fillna(False)
> working["duplicate_record"] = working.duplicated(["container_id", "date"], keep=False)
> review_mask = working[["missing_number", "negative_number", "duplicate_record"]].any(axis=1)
> valid = working.loc[~review_mask].copy()
> review = working.loc[review_mask].copy()
> assert len(records) == len(valid) + len(review)
> assert records.equals(original)
> print("VALID:", len(valid), "REVIEW:", len(review))
> ```
>
> **代表的な確認結果**
>
>     VALID: 1 REVIEW: 3
>
> クリーニングは規則に基づく分類であり、黙った書換えではありません。別々のフラグが理由を残します。

### 値を変えて再確認する

負数かつ重複となる一行を追加し、両フラグと件数照合を確認します。

## まとめ

このレッスンと統合練習を終えると、次のことができます。

- 原本を保ったまま作業用コピーへ品質フラグを追加しました。
- 問題ごとに名前付き規則を作り、一行に複数ある理由も残しました。
- 分析対象と確認対象を分け、件数・制約・監査記録を再検証しました。

## 次のレッスンへ

分析に使える行と確認が必要な行を説明できる形で分けました。3.4では、分析用データを目的に合う粒度へ集計し、判断に使える指標と順位を作ります。

**学習時間の目安:** 約3時間
