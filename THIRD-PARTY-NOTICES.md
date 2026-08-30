# Third-party notices

Moodle Rescue does not change the licenses of software, images, datasets, or
other material supplied by third parties.

## Moodle

Moodle LMS is distributed under the GNU General Public License. See the
[official Moodle license information](https://moodledev.io/general/license).
Moodle and associated logos are trademarks of Moodle Pty Ltd or its related
affiliates. Use of the word Moodle in this project's descriptive name does not
imply sponsorship or endorsement.

## Container images, plugins, and dependencies

Container base images, Moodle plugins, Python packages, and other dependencies
retain their respective licenses and notices. They are referenced or obtained
by the build process and are not relicensed by this repository's GPL or CC BY
declarations. Operators and redistributors should review the exact versions
selected by the Compose files, Dockerfiles, and `plugins.lock`.

## Sample-course data

`learning-centres-practice.csv` and data produced by
`generate-learning-centre-data.py` are fictional project-authored teaching
data. They do not describe real learners or learning centres and are covered
by the educational-content license in [`LICENSE-CONTENT.md`](LICENSE-CONTENT.md).

No third-party open dataset is included in sample-course release 1.0.0.

Future third-party datasets or media must be listed here with their title,
source URL, author or publisher, exact license, access date, and any changes
made. Material without adequate redistribution permission must not be bundled
in a course release.

## IPA examination material

The course under `sample-content/ap-written-practice-ja/` links to past
questions, official answer examples, and grading commentary published by the
Information-technology Promotion Agency, Japan (IPA). Copyright in those
materials remains with IPA; they are not covered by this repository's CC BY
license. Exact source URLs and editorial notices are recorded in the course's
`THIRD-PARTY-NOTICES.md`.
