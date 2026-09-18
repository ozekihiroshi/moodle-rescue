"""Package four user-free backups, removing local tool configuration.

Only packaging metadata, portable links and deployment configuration change;
lesson text, questions, code and assignment contracts remain unchanged.
"""
import hashlib
import io
import json
import re
import sys
import tarfile
import xml.etree.ElementTree as ET
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DEST = ROOT / 'sample-content/python-duallearning/distribution'
VERSION = '0.1.0-alpha.1'
VARIANTS = ('en-path', 'en-guided', 'ja-path', 'ja-guided')
TOKENS = {m: m.upper()+'VIEWBYID' for m in ('lessonmark','quiz','assign','lti','forum')}
LINK = re.compile(r'(?<![\w/])(?:https?://localhost:8083)?/mod/(lessonmark|quiz|assign|lti|forum)/view\.php\?id=(\d+)')

def read_archive(path):
    with tarfile.open(path,'r:gz') as src:
        return {m.name: src.extractfile(m).read() for m in src if m.isfile()}

def validate(files, key):
    assert not any(n.endswith('users.xml') for n in files), 'User archive present'
    meta=ET.fromstring(files['moodle_backup.xml'])
    settings={s.findtext('name'):s.findtext('value') for s in meta.findall('.//setting')}
    assert settings['users']=='0'
    for name in ('role_assignments','comments','userscompletion','logs','grade_histories'):
        assert settings.get(name,'0')=='0', name
    counts={}
    for a in meta.findall('./information/contents/activities/activity'):
        mod=a.findtext('modulename'); counts[mod]=counts.get(mod,0)+1
    expected={'lessonmark':83 if key.endswith('guided') else 37,'quiz':23,'assign':8,'lti':32,'forum':8 if key.endswith('guided') else 1,'subsection':31}
    assert counts==expected,(key,counts)
    for name,data in files.items():
        if not name.endswith('.xml'): continue
        text=data.decode('utf-8'); tree=ET.fromstring(data)
        assert 'localhost:' not in text, name
        assert 'encrypted="true"' not in text, name
        assert not LINK.search(text), name
        for tag in ('user_enrolment','attempt','submission','grade_grade','completion','discussion','post'):
            # User-free archives have empty containers, never actual records.
            assert not any(n.attrib.get('id') for n in tree.findall('.//'+tag)), (name,tag)
        for node in tree.iter():
            if node.tag in ('resourcekey','password','clientid','privatekey'):
                assert node.text in (None,'','$@NULL@$'), (name,node.tag)
    return counts

def main():
    if '--verify' in sys.argv:
        manifest=json.loads((DEST/'manifest.json').read_text(encoding='utf-8'))
        for item in manifest['artifacts']:
            path=DEST/item['file']
            assert hashlib.sha256(path.read_bytes()).hexdigest()==item['sha256'], path
            validate(read_archive(path),item['variant'])
        print('PASS: four archives, checksums and user-free portable contents')
        return
    DEST.mkdir(parents=True,exist_ok=True)
    manifest={'release':VERSION,'status':'alpha','canonical_language':'en','moodle':'5.2.2',
        'required_plugins':{'mod_lessonmark':'0.3.0-alpha4 (2026091800)','format_duallearning':'0.1.0-alpha5 (2026091803)'},
        'requires_python_lab':True,'includes_users':False,'artifacts':[]}
    checks=[]
    for key in VARIANTS:
        files=read_archive(ROOT/'build/python-dual-raw'/f'{key}.mbz')
        moduleids=set()
        meta=ET.fromstring(files['moodle_backup.xml'])
        for a in meta.findall('./information/contents/activities/activity'): moduleids.add(a.findtext('moduleid'))
        for name,data in list(files.items()):
            if not name.endswith('.xml'): continue
            tree=ET.fromstring(data)
            for node in tree.iter():
                if node.text:
                    def token(m):
                        assert m[2] in moduleids,(name,'Excluded link',m[0])
                        return '$@'+TOKENS[m[1]]+'*'+m[2]+'@$'
                    node.text=LINK.sub(token,node.text)
                    node.text=node.text.replace('http://localhost:8083','https://moodle.example.invalid').replace('http://localhost:8086','https://python-lab.example.invalid')
                if node.tag in ('resourcekey','password','clientid','privatekey'):
                    node.text=''; node.attrib.pop('encrypted',None)
                if node.tag=='original_site_identifier_hash': node.text=hashlib.md5(('python-dual-'+key).encode()).hexdigest()
                if name=='questions.xml' and node.tag=='stamp':
                    node.text='python-dual-'+key+'-'+hashlib.sha256((VERSION+str(node.text)).encode()).hexdigest()[:32]
            if name.endswith('/lti.xml'):
                lti=tree.find('lti')
                lti.find('typeid').text='0'
                for field in ('resourcekey','password'):
                    if lti.find(field) is not None: lti.find(field).text='$@NULL@$'
                for typ in list(lti.findall('ltitype')): lti.remove(typ)
                for field in ('instructorcustomparameters','securetoolurl'):
                    if lti.find(field) is not None: lti.find(field).text='$@NULL@$'
            if name=='course/course.xml':
                tree.find('visible').text='0'  # Admin configures LTI and enrolment before opening.
                tree.find('theme').text=''
            if name=='course/enrolments.xml':
                enrols=tree.find('enrols')
                for enrol in list(enrols):
                    if enrol.findtext('enrol')!='manual': enrols.remove(enrol)
            files[name]=b'<?xml version="1.0" encoding="UTF-8"?>\n'+ET.tostring(tree,encoding='utf-8')
        counts=validate(files,key)
        filename=f'python-foundations-dual-{key}-{VERSION}.mbz'
        output=DEST/filename
        with tarfile.open(output,'w:gz') as dst:
            for name,data in files.items():
                info=tarfile.TarInfo(name); info.size=len(data); info.mode=0o644
                dst.addfile(info,io.BytesIO(data))
        validate(read_archive(output),key)
        digest=hashlib.sha256(output.read_bytes()).hexdigest()
        checks.append(f'{digest}  {filename}')
        manifest['artifacts'].append({'variant':key,'file':filename,'sha256':digest,'bytes':output.stat().st_size,'activities':counts})
    (DEST/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n',encoding='utf-8')
    (DEST/'SHA256SUMS').write_text('\n'.join(checks)+'\n',encoding='utf-8')
    (DEST/'LICENSE.txt').write_bytes((ROOT/'sample-content/introduction-to-python/distribution/LICENSE.txt').read_bytes())
    print(json.dumps(manifest,indent=2))

if __name__=='__main__': main()
