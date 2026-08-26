#!/bin/sh
workdir="${HOME}/work"
sourcedir="/opt/python-lab/course-materials"
marker="${workdir}/.python-lab-materials-v9"

mkdir -p "${workdir}"
if [ ! -e "${marker}" ]; then
    # Add newly released course files without overwriting learner work.
    cp -R --update=none /opt/python-lab/course-materials/. "${workdir}/"
    touch "${marker}"
fi

# Submission helpers are course infrastructure, not learner work. Keep these
# small managed files current while preserving weekly_support.py and notebooks.
for relative in \
    "projects/weekly-support/submit_weekly_support.py" \
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
