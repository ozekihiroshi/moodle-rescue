from pathlib import Path


root = Path(__file__).resolve().parents[1]
paths = [
    root / "sample-content/introduction-to-python/python-lab/10-python-lab-materials.sh",
    root.parent / "python-lab-rescue/singleuser/start-notebook.d/10-python-lab-materials.sh",
]
managed = '''

# Submission helpers are course infrastructure, not learner work. Keep these
# small managed files current while preserving weekly_support.py and notebooks.
for relative in \\
    "projects/weekly-support/submit_weekly_support.py" \\
    "ja/projects/weekly-support/submit_weekly_support.py"
do
    sourcepath="${sourcedir}/${relative}"
    destination="${workdir}/${relative}"
    if [ -f "${sourcepath}" ]; then
        mkdir -p "$(dirname "${destination}")"
        cp -f "${sourcepath}" "${destination}"
        chmod u+rw "${destination}"
    fi
done
'''

for path in paths:
    text = path.read_text(encoding="utf-8")
    text = text.replace(".python-lab-materials-v6", ".python-lab-materials-v7")
    if "Submission helpers are course infrastructure" not in text:
        if 'sourcedir="/opt/python-lab/course-materials"' not in text:
            text = text.replace(
                'workdir="${HOME}/work"\n',
                'workdir="${HOME}/work"\nsourcedir="/opt/python-lab/course-materials"\n',
                1,
            )
        text = text.rstrip() + managed
    path.write_text(text, encoding="utf-8", newline="\n")
    print(path)
