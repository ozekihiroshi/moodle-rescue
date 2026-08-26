#!/usr/bin/env python3
import json
from pathlib import Path
R=Path(__file__).resolve().parents[1];T=R/'sample-content/introduction-to-python/python-lab/templates'
def md(i,s):return {'cell_type':'markdown','id':i,'metadata':{},'source':s.splitlines(keepends=True)}
def code(i,s):return {'cell_type':'code','execution_count':None,'id':i,'metadata':{},'outputs':[],'source':s.splitlines(keepends=True)}
SET='''from pathlib import Path
import pandas as pd
import matplotlib.pyplot as plt

def find_course_data(filename):
    roots=[Path.cwd(),*Path.cwd().parents,Path.home()/"work",Path("/opt/python-lab/course-materials")]
    checked=[]
    for root in roots:
        for p in (root/"data"/filename,root/filename):
            if p in checked: continue
            checked.append(p)
            if p.is_file(): return p
    raise FileNotFoundError("Course data was not found:\\n"+"\\n".join(map(str,checked)))

data_file=find_course_data("learning-centres-practice.csv")
raw=pd.read_csv(data_file)
print("Source:",data_file.resolve(),"shape:",raw.shape)
'''
def nb(lang):
 ja=lang=='ja'; cells=[
 md('p42-title','# 4.2 — ガイド付きプロジェクト：学習センター分析\n\n6か月の架空データから、コース別の全体修了率と一人修了当たり教材費について何が言え、次に何を調べるべきかを、再現可能な根拠で答えます。' if ja else '# 4.2 — Guided project: Learning-centre analysis\n\nUse six months of fictional data to answer: What can be said about overall completion and material cost per completion by course, and what should be investigated next?'),
 md('p42-scope','## 1. 問い・範囲・指標を固定する\n\n対象は2026年1〜6月の架空のセンター月記録です。全体修了率は修了者合計÷登録者合計×100、一人修了当たり教材費は教材費合計÷修了者合計です。個人データ、因果推論、センター順位付けは対象外です。' if ja else '## 1. Fix the question, scope, and measures\n\nScope is fictional centre-month records from January through June 2026. Overall completion rate is total completed divided by total registered times 100; material cost per completion is total cost divided by total completed. Personal data, causal inference, and centre ranking are out of scope.'),
 code('p42-load',SET),
 md('p42-inspect','## 2. 読み込み結果を検査する\n\n列名、行数、型、欠損、カテゴリ値、数値範囲を表示し、必要列が存在することを集合の差で確認します。ここではまだ値を修正しません。' if ja else '## 2. Inspect the loaded data\n\nDisplay columns, shape, types, missingness, category values, and numeric ranges. Check required columns with a set difference. Do not change values yet.'),code('p42-inspect-code','''required={"month","centre_id","district","course","registered","attended","completed","material_cost"}
missing=required-set(raw.columns)
if missing: raise KeyError(sorted(missing))
print(raw.dtypes); print(raw.isna().sum()); print(sorted(raw["district"].dropna().unique()))
'''),
 md('p42-clean','## 3. 規則を先に定義し、監査記録を作る\n\n元データを保持し、地区表記を規則で正規化します。欠損出席、修了者数が出席者数を上回る行、業務キー重複を別々に数え、推測修正せず分析対象フラグへ反映します。' if ja else '## 3. Define rules first and build an audit record\n\nPreserve the source and normalise district labels by rule. Count missing attendance, completion above attendance, and duplicate business keys separately. Do not guess corrections; use an analysis-ready flag.'),code('p42-clean-code','''clean=raw.copy(); clean["district_raw"]=clean["district"]; clean["district"]=clean["district"].astype("string").str.strip().str.title()
missing_attended=clean["attended"].isna(); invalid_completion=clean["completed"].notna() & clean["attended"].notna() & (clean["completed"]>clean["attended"])
key=["centre_id","month","course"]; duplicate=clean.duplicated(key,keep=False)
clean["analysis_ready"]=~(missing_attended|invalid_completion|duplicate)
analysis=clean.loc[clean["analysis_ready"]].copy()
audit=pd.DataFrame({"issue":["missing attended","completion above attendance","duplicate business key"],"affected":[int(missing_attended.sum()),int(invalid_completion.sum()),int(duplicate.sum())],"action":["flag; exclude from rate analysis","flag; do not guess","review duplicate group"]})
audit
'''),
 md('p42-analyse','## 4. 分子と分母を集計して指標を計算する\n\nコースごとの記録数、異なるセンター数、登録者・修了者・教材費合計を作り、その後に二つの指標を計算します。行別率平均を全体率として使いません。' if ja else '## 4. Aggregate numerators and denominators, then calculate measures\n\nBy course, calculate record count, distinct centres, and totals for registered, completed, and material cost. Then calculate both measures. Do not use mean row rate as the overall rate.'),code('p42-analyse-code','''summary=analysis.groupby("course").agg(records=("centre_id","size"),centres=("centre_id","nunique"),registered=("registered","sum"),completed=("completed","sum"),material_cost=("material_cost","sum")).reset_index()
summary["completion_rate"]=summary["completed"]/summary["registered"]*100
summary["cost_per_completion"]=summary["material_cost"]/summary["completed"]
summary.round(2)
'''),
 md('p42-validate','## 5. 件数と合計を照合する\n\n元件数＝分析可能件数＋フラグ件数、グループ合計＝分析用明細合計を`assert`で確認します。' if ja else '## 5. Reconcile counts and totals\n\nUse assertions to verify source equals analysis-ready plus flagged rows and grouped totals equal analysis detail.'),code('p42-validate-code','''assert len(raw)==int(clean["analysis_ready"].sum())+int((~clean["analysis_ready"]).sum())
assert summary["registered"].sum()==analysis["registered"].sum()
assert summary["completed"].sum()==analysis["completed"].sum()
assert abs(summary["material_cost"].sum()-analysis["material_cost"].sum())<1e-9
print("Validation passed")
'''),
 md('p42-chart','## 6. 一つの問いに一つの主図を作る\n\n全体修了率を0〜100%の横棒で比較します。一人修了当たり教材費は表で併記し、必要なら第二図にします。軸、単位、タイトル、件数を明示します。' if ja else '## 6. Create one primary chart for one question\n\nCompare overall completion rates with horizontal bars on a zero-to-100% axis. Report cost per completion in the table and use a second chart only if needed. Label axes, units, title, and n.'),code('p42-chart-code','''plot_data=summary.sort_values("completion_rate")
fig,ax=plt.subplots(figsize=(7,4)); ax.barh(plot_data["course"],plot_data["completion_rate"],color="#356a9a"); ax.set(xlabel="Overall completion rate (%)",ylabel="Course",title=f"Completion by course (analysis-ready centre-months n={len(analysis)})"); ax.set_xlim(0,100); ax.grid(axis="x",alpha=.25); plt.tight_layout(); plt.show()
'''),
 md('p42-report','## 7. 報告を書く\n\n150〜250語（日本語では300〜500字程度）で、問いへの回答、比較数値、データ範囲、品質処置、限界、次に調べる問いを書きます。「原因」「効果」「優れている」は、このデータだけでは使いません。' if ja else '## 7. Write the report\n\nIn 150–250 words, answer the question with comparative numbers, data scope, quality actions, one limitation, and the next question. Do not claim cause, effect, or superiority from this dataset.'),code('p42-report-work','# ここに報告を書きます。\n' if ja else '# Write the report here.\n'),
 md('p42-submit','## 提出前確認\n\nNotebookに、問いと指標定義、検査出力、監査表、分析用集計表、照合結果、ラベル付き図、報告文が含まれ、上から再実行できることを確認します。画像だけ、コードだけ、説明だけでは提出要件を満たしません。' if ja else '## Submission check\n\nThe notebook must include question and measure definitions, inspection output, audit table, analysis summary, reconciliation, labelled chart, and report, and run from top to bottom. An image alone, code alone, or prose alone is incomplete.')]
 return {'cells':cells,'metadata':{'kernelspec':{'display_name':'Python 3 (ipykernel)','language':'python','name':'python3'},'language_info':{'name':'python','version':'3'},'pyai':{'project':'4.2','language':lang,'revision':27}},'nbformat':4,'nbformat_minor':5}
for lang,p in {'en':T/'P3_learning_centres_analysis.ipynb','ja':T/'ja/P3_learning_centres_analysis.ipynb'}.items():p.parent.mkdir(parents=True,exist_ok=True);p.write_text(json.dumps(nb(lang),ensure_ascii=False,indent=1)+'\n',encoding='utf-8');print('wrote',p.relative_to(R))
