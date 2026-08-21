#!/bin/sh
workdir="${HOME}/work"
marker="${workdir}/.python-lab-materials-v3"

mkdir -p "${workdir}"
if [ ! -e "${marker}" ]; then
    # Add newly released course files without overwriting learner work.
    cp -R --update=none /opt/python-lab/course-materials/. "${workdir}/"
    touch "${marker}"
fi
