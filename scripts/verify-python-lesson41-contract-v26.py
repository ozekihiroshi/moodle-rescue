#!/usr/bin/env python3
import json,re
from pathlib import Path
R=Path(__file__).resolve().parents[1];m=json.loads((R/'sample-content/introduction-to-python/localization/lesson-4-1-concept-map-v1.json').read_text(encoding='utf-8'));s=(R/m['implementation']).read_text(encoding='utf-8')
assert len(m['concepts'])==10 and [x['id'] for x in m['concepts']]==[f'E{i:02d}' for i in range(1,11)] and [x['question'] for x in m['concepts']]==[f'L41R-{i:02d}' for i in range(1,11)]
assert re.findall(r"v26q\('(L41R-\d{2})'",s)==[f'L41R-{i:02d}' for i in range(1,11)]*2
for lang,p in m['notebooks'].items():
 d=json.loads((R/p).read_text(encoding='utf-8'));assert d['metadata']['pyai']=={'lesson':'4.1','language':lang,'concepts':[f'E{i:02d}' for i in range(1,11)],'revision':26};src=''.join(''.join(c.get('source',[])) for c in d['cells'])
 for n in ['plt.subplots','barh(','plot(','scatter(','hist(','set_xlim','set_ylim','savefig','assert ','limitation' if lang=='en' else '限界']:assert n in src,(lang,n)
 for n in ['Naledi','ナレディ','AI checkpoint','AI利用','Teacher guide','教師用ガイド']:assert n not in src
print(json.dumps({'verified':True,'concepts':10,'questions':10,'languages':2,'revision':26},indent=2))
