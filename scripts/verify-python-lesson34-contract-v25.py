#!/usr/bin/env python3
import json,re
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
m=json.loads((ROOT/'sample-content/introduction-to-python/localization/lesson-3-4-concept-map-v1.json').read_text(encoding='utf-8'))
src=(ROOT/m['implementation']).read_text(encoding='utf-8')
assert m['canonical_language']=='en' and m['adaptations']==['ja'] and len(m['concepts'])==10
assert [x['id'] for x in m['concepts']]==[f'D{i:02d}' for i in range(1,11)]
assert [x['question'] for x in m['concepts']]==[f'L34R-{i:02d}' for i in range(1,11)]
assert re.findall(r"v25_q\('(L34R-\d{2})'",src)==[f'L34R-{i:02d}' for i in range(1,11)]*2
needed=['.groupby(','.agg(','"size"','"count"','"nunique"','"mean"','"median"','"std"','overall_completion_rate','transform("sum")','assert ']
for lang,rel in m['notebooks'].items():
 d=json.loads((ROOT/rel).read_text(encoding='utf-8')); assert d['metadata']['pyai']=={'lesson':'3.4','language':lang,'concepts':[f'D{i:02d}' for i in range(1,11)],'revision':25}
 s=''.join(''.join(c.get('source',[])) for c in d['cells'])
 for n in needed: assert n in s,(lang,n)
 for n in ['Naledi','ナレディ','AI checkpoint','AI利用','Teacher guide','教師用ガイド']: assert n not in s,(lang,n)
print(json.dumps({'verified':True,'concepts':10,'questions':10,'languages':2,'revision':25},indent=2))
