#!/usr/bin/env python3
"""Build bilingual Lesson 4.1 notebooks and concept contract."""
from __future__ import annotations
import json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]; T=ROOT/'sample-content/introduction-to-python/python-lab/templates'
def md(i,s):return {'cell_type':'markdown','id':i,'metadata':{},'source':s.splitlines(keepends=True)}
def code(i,s):return {'cell_type':'code','execution_count':None,'id':i,'metadata':{},'outputs':[],'source':s.splitlines(keepends=True)}
SETUP='''from pathlib import Path
import pandas as pd
import matplotlib.pyplot as plt

def find_course_data(filename):
    roots=[Path.cwd(),*Path.cwd().parents,Path.home()/"work",Path("/opt/python-lab/course-materials")]
    checked=[]
    for root in roots:
        for candidate in (root/"data"/filename,root/filename):
            if candidate in checked: continue
            checked.append(candidate)
            if candidate.is_file(): return candidate
    raise FileNotFoundError("Course data was not found:\\n"+"\\n".join(map(str,checked)))

raw=pd.read_csv(find_course_data("learning-centres-practice.csv"))
clean=raw.copy()
clean["district"]=clean["district"].astype("string").str.strip().str.title()
key=["centre_id","month","course"]
problem=clean["attended"].isna() | (clean["completed"]>clean["attended"]) | clean.duplicated(key,keep=False)
analysis=clean.loc[~problem].copy()
analysis["completion_rate"]=analysis["completed"]/analysis["registered"]*100
print("Source:",len(raw),"analysis-ready:",len(analysis),"flagged:",int(problem.sum()))
'''
def notebook(lang):
 if lang=='ja':
  cells=[
   md('l41-ja-title','# 4.1 — 可視化と根拠\n\n問いに合う集計表とグラフを作り、軸、単位、件数、数値、範囲、限界を示して、観察できる根拠だけを文章にします。'),
   md('l41-ja-question','## グラフを選ぶ前に、問いと一行の意味を決める\n\nグラフは分析の飾りではなく、数値間の関係を見つけて伝える道具です。カテゴリ比較ならカテゴリ別集計、時系列なら時点別集計を先に作ります。24行の明細をそのまま描くのではなく、問いと同じ粒度へ集約し、表の数値を確認してから描画します。'),code('l41-ja-setup',SETUP),
   md('l41-ja-bar','## 棒グラフはカテゴリの大きさを比較する\n\nコース別修了率のように、順序を前提としない少数カテゴリを比較するときは棒グラフが適します。比率は修了者合計÷登録者合計で作り、値の順または意味のある順へ並べます。棒の長さが量を表すため、通常は0を起点にします。'),
   code('l41-ja-bar-code','''course=analysis.groupby("course").agg(registered=("registered","sum"),completed=("completed","sum"),records=("centre_id","size")).reset_index()
course["completion_rate"]=course["completed"]/course["registered"]*100
course=course.sort_values("completion_rate")
fig,ax=plt.subplots(figsize=(7,4))
ax.barh(course["course"],course["completion_rate"],color="#356a9a")
ax.set(xlabel="Completion rate (%)",ylabel="Course",title="Overall completion rate by course")
ax.set_xlim(0,100)
ax.grid(axis="x",alpha=.25)
plt.tight_layout(); plt.show()
course
'''),
   md('l41-ja-line','## 折れ線グラフは順序のある時間変化を示す\n\n月別推移では月を日時へ変換して並べます。文字列順が常に時間順とは限りません。欠けた月がある場合、線で直接結ぶと連続して観測したように見えるため、月の一覧へ再配置し、欠損を明示します。カテゴリ名に折れ線を使うと存在しない途中の変化を暗示します。'),
   code('l41-ja-line-code','''monthly=analysis.assign(month_date=pd.to_datetime(analysis["month"]+"-01")).groupby("month_date").agg(registered=("registered","sum"),completed=("completed","sum")).reset_index().sort_values("month_date")
monthly["completion_rate"]=monthly["completed"]/monthly["registered"]*100
fig,ax=plt.subplots(figsize=(7,4)); ax.plot(monthly["month_date"],monthly["completion_rate"],marker="o")
ax.set(xlabel="Month",ylabel="Completion rate (%)",title="Monthly overall completion rate"); ax.set_ylim(0,100); ax.grid(alpha=.25); plt.xticks(rotation=30); plt.tight_layout(); plt.show()
'''),
   md('l41-ja-scatter','## 散布図は二つの数値の関係を調べる\n\n教材費と修了者数のような二つの数値を一記録ずつ対応させるときは散布図を使います。点のまとまりや外れを観察できますが、相関や傾向があっても原因を証明しません。月、センター規模、コースなど別の要因が両方に関係する可能性があります。'),
   code('l41-ja-scatter-code','''fig,ax=plt.subplots(figsize=(6,4)); ax.scatter(analysis["completed"],analysis["material_cost"],alpha=.75)
ax.set(xlabel="Completed learners",ylabel="Material cost",title="Material cost and completions per centre-month"); ax.grid(alpha=.2); plt.tight_layout(); plt.show()
'''),
   md('l41-ja-hist','## ヒストグラムは一つの数値の分布を示す\n\nヒストグラムは修了率を数値範囲へ区切り、各範囲の件数を示します。棒グラフの棒は独立カテゴリですが、ヒストグラムの棒は連続する数値区間です。ビン幅を変えると見え方が変わるため、複数の幅を試し、標本数も示します。'),
   code('l41-ja-hist-code','''fig,ax=plt.subplots(figsize=(6,4)); ax.hist(analysis["completion_rate"],bins=[50,60,70,80,90,100],edgecolor="white")
ax.set(xlabel="Centre-month completion rate (%)",ylabel="Number of records",title=f"Distribution of completion rates (n={len(analysis)})"); ax.set_xlim(0,100); plt.tight_layout(); plt.show()
'''),
   md('l41-ja-label','## タイトル、軸、単位、凡例、色をデータの意味に合わせる\n\nタイトルには比較対象、軸には変数名と単位を記します。複数系列だけ凡例を付け、色だけに意味を依存せず、十分なコントラストと直接ラベルも検討します。3D、過剰な装飾、面積で量を誇張する図は避けます。'),
   md('l41-ja-scale','## 軸範囲と並び順で差を誇張しない\n\n棒グラフの軸を70%から始めると、72%と74%の差が実際以上に大きく見えます。割合軸は0〜100%が基準です。一方、折れ線で小さな変化を見る狭い軸を使う場合は、範囲を明示し、差の絶対量も文章で示します。並び順も結論を変えない一貫した規則にします。'),
   md('l41-ja-evidence','## 根拠文は観察、数値、範囲、限界を含める\n\n「Python Foundationsの全体修了率は72.5%、Digital Skillsは約73.1%で、差は約0.6ポイントだった」のように、比較値と差を示します。続けて「分析可能な22センター月に限られ、コース難易度や参加者構成を調整していないため、原因や優劣は判断できない」と範囲と限界を書きます。グラフだけで因果関係やデータ品質を証明できません。'),
   md('l41-ja-save','## 描画に使った表を検証し、図を再生成できる形で保存する\n\nグラフの元表を表示し、3.4の合計と照合します。Notebookに集計コード、描画コード、根拠文を残し、必要なら`savefig()`で同じ図を出力します。画像だけを成果物にすると、数値や条件を再確認できません。'),
   code('l41-ja-save-code','''assert course["registered"].sum()==analysis["registered"].sum()
assert course["completed"].sum()==analysis["completed"].sum()
fig,ax=plt.subplots(figsize=(7,4)); ax.barh(course["course"],course["completion_rate"]); ax.set(xlabel="Completion rate (%)",title="Overall completion rate by course"); ax.set_xlim(0,100); plt.tight_layout()
# fig.savefig("completion-rate-by-course.png",dpi=150,bbox_inches="tight")
plt.show()
'''),
   md('l41-ja-transfer','## 応用練習\n\n月別登録者数の折れ線、コース別一人修了当たり教材費の棒グラフ、センター月の登録者数と修了率の散布図を作ってください。各図の問い、元表の粒度、軸と単位、件数を示し、最も適切な図を一つ選んで「観察＋数値＋範囲＋限界」の4文を書きます。誤解を招く軸範囲の版も一度作り、なぜ採用しないか説明してください。'),code('l41-ja-work','# ここに応用練習の解答を書きます。\n'),
   md('l41-ja-complete','## このレッスンで到達したこと\n\n問いと粒度に合わせて棒・折れ線・散布・ヒストグラムを選び、集計後に描画し、軸・単位・範囲・並び順・色・件数を明示できるようになりました。図の元表を照合し、観察を数値で支え、因果を断定せず範囲と限界を書ければ理解度チェックへ進みます。')]
 else:
  cells=[
   md('l41-en-title','# 4.1 — Visualisation and evidence\n\nChoose a chart for the question and support an observation with axes, units, counts, numbers, scope, and a limitation.'),
   md('l41-en-question','## Define the question and plotted grain before chart type\n\nA chart is not decoration. Aggregate detail to the same grain as the question, inspect the plotted table, and only then draw. Category comparison needs category summaries; a time trend needs ordered time summaries.'),code('l41-en-setup',SETUP),
   md('l41-en-bar','## Bar charts compare category magnitudes\n\nUse bars for a small number of unordered categories such as course completion. Calculate total completed divided by total registered, sort deliberately, and normally begin the quantitative axis at zero because length encodes magnitude.'),
   code('l41-en-bar-code','''course=analysis.groupby("course").agg(registered=("registered","sum"),completed=("completed","sum"),records=("centre_id","size")).reset_index()
course["completion_rate"]=course["completed"]/course["registered"]*100
course=course.sort_values("completion_rate")
fig,ax=plt.subplots(figsize=(7,4)); ax.barh(course["course"],course["completion_rate"],color="#356a9a"); ax.set(xlabel="Completion rate (%)",ylabel="Course",title="Overall completion rate by course"); ax.set_xlim(0,100); ax.grid(axis="x",alpha=.25); plt.tight_layout(); plt.show(); course
'''),
   md('l41-en-line','## Line charts show change over ordered time\n\nConvert month to dates and sort chronologically. If a month is missing, reindex to the expected calendar and show the gap; directly joining observed points can imply continuous observation. A line across unordered categories invents an in-between path.'),
   code('l41-en-line-code','''monthly=analysis.assign(month_date=pd.to_datetime(analysis["month"]+"-01")).groupby("month_date").agg(registered=("registered","sum"),completed=("completed","sum")).reset_index().sort_values("month_date")
monthly["completion_rate"]=monthly["completed"]/monthly["registered"]*100
fig,ax=plt.subplots(figsize=(7,4)); ax.plot(monthly["month_date"],monthly["completion_rate"],marker="o"); ax.set(xlabel="Month",ylabel="Completion rate (%)",title="Monthly overall completion rate"); ax.set_ylim(0,100); ax.grid(alpha=.25); plt.xticks(rotation=30); plt.tight_layout(); plt.show()
'''),
   md('l41-en-scatter','## Scatter plots examine two quantitative variables\n\nOne point links two values from one record, exposing clusters and unusual points. Association is not causation: month, centre size, course, or another factor may affect both variables.'),
   code('l41-en-scatter-code','''fig,ax=plt.subplots(figsize=(6,4)); ax.scatter(analysis["completed"],analysis["material_cost"],alpha=.75); ax.set(xlabel="Completed learners",ylabel="Material cost",title="Material cost and completions per centre-month"); ax.grid(alpha=.2); plt.tight_layout(); plt.show()
'''),
   md('l41-en-hist','## Histograms show the distribution of one quantitative variable\n\nA histogram counts values within adjacent numeric intervals; a bar chart compares separate categories. Bin width changes the visible pattern, so inspect alternatives and state sample size.'),
   code('l41-en-hist-code','''fig,ax=plt.subplots(figsize=(6,4)); ax.hist(analysis["completion_rate"],bins=[50,60,70,80,90,100],edgecolor="white"); ax.set(xlabel="Centre-month completion rate (%)",ylabel="Number of records",title=f"Distribution of completion rates (n={len(analysis)})"); ax.set_xlim(0,100); plt.tight_layout(); plt.show()
'''),
   md('l41-en-label','## Match titles, axes, units, legends, and colour to meaning\n\nName the comparison in the title and put variable names and units on axes. Use legends only for multiple series, do not rely on colour alone, and ensure contrast or direct labels. Avoid 3D and decoration that distorts area or length.'),
   md('l41-en-scale','## Do not exaggerate differences with scale or order\n\nStarting a bar axis at 70% makes 72% and 74% look far apart. Zero to 100% is a sound default for rate bars. A narrow line-chart range can show small change, but disclose it and state the absolute difference. Apply a consistent ordering rule.'),
   md('l41-en-evidence','## Evidence writing includes observation, numbers, scope, and limitation\n\n“Overall completion was 72.5% for Python Foundations and about 73.1% for Digital Skills, a difference of about 0.6 percentage points.” Then qualify: only 22 analysis-ready centre-months are included, course difficulty and learner mix are uncontrolled, so the chart does not establish cause or superiority. A chart does not validate its source.'),
   md('l41-en-save','## Validate the plotted table and preserve reproducibility\n\nDisplay and reconcile the table feeding the chart. Keep aggregation, plotting, and evidence text in the notebook; optionally use `savefig()` for a reproducible image. An image alone cannot reveal filters or calculations.'),
   code('l41-en-save-code','''assert course["registered"].sum()==analysis["registered"].sum()
assert course["completed"].sum()==analysis["completed"].sum()
fig,ax=plt.subplots(figsize=(7,4)); ax.barh(course["course"],course["completion_rate"]); ax.set(xlabel="Completion rate (%)",title="Overall completion rate by course"); ax.set_xlim(0,100); plt.tight_layout()
# fig.savefig("completion-rate-by-course.png",dpi=150,bbox_inches="tight")
plt.show()
'''),
   md('l41-en-transfer','## Transfer exercise\n\nCreate a monthly registrations line chart, a course cost-per-completion bar chart, and a scatter plot of registrations versus completion rate. State each question, plotted grain, axes, units, and n. Select one chart and write four sentences: observation, numbers, scope, and limitation. Also create one misleading axis version and explain why you reject it.'),code('l41-en-work','# Write the transfer solution here.\n'),
   md('l41-en-complete','## Completion check\n\nYou can select bar, line, scatter, or histogram from the question; aggregate before plotting; label axes and units; disclose scale, order, colour, and counts; reconcile plotted data; and write a numbered observation without claiming causation. Save and continue to the learning check.')]
 return {'cells':cells,'metadata':{'kernelspec':{'display_name':'Python 3 (ipykernel)','language':'python','name':'python3'},'language_info':{'name':'python','version':'3'},'pyai':{'lesson':'4.1','language':lang,'concepts':[f'E{i:02d}' for i in range(1,11)],'revision':26}},'nbformat':4,'nbformat_minor':5}
def main():
 for lang,p in {'en':T/'11_visualisation_evidence.ipynb','ja':T/'ja/11_visualisation_evidence.ipynb'}.items():p.parent.mkdir(parents=True,exist_ok=True);p.write_text(json.dumps(notebook(lang),ensure_ascii=False,indent=1)+'\n',encoding='utf-8');print('wrote',p.relative_to(ROOT))
 ds=['define question and plotted grain before choosing a chart','use zero-based ordered bar charts for category magnitudes','use chronological line charts and expose missing periods','use scatter plots for two quantitative variables without claiming causation','distinguish histograms and bin choices from categorical bars','label title axes units legends colour and sample count accessibly','avoid misleading scales ordering area and decorative encodings','qualify small groups missingness scope association and causation','write evidence using observation numbers scope and limitation','reconcile plotted tables and preserve reproducible chart code']
 m={'schema_version':1,'lesson':'4.1 Visualisation and evidence','canonical_language':'en','adaptations':['ja'],'concepts':[{'id':f'E{i:02d}','description':d,'lesson':True,'notebook':True,'question':f'L41R-{i:02d}','teacher':False} for i,d in enumerate(ds,1)],'notebooks':{'en':'sample-content/introduction-to-python/python-lab/templates/11_visualisation_evidence.ipynb','ja':'sample-content/introduction-to-python/python-lab/templates/ja/11_visualisation_evidence.ipynb'},'implementation':'scripts/upgrade-python-lesson41-v26.php'}
 p=ROOT/'sample-content/introduction-to-python/localization/lesson-4-1-concept-map-v1.json';p.write_text(json.dumps(m,ensure_ascii=False,indent=2)+'\n',encoding='utf-8');print('wrote',p.relative_to(ROOT))
if __name__=='__main__':main()
