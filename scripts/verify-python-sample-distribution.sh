#!/bin/sh
set -eu

repo_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
dist_dir="$repo_dir/sample-content/introduction-to-python/distribution"
artifact="$dist_dir/python-for-data-foundations-ai-era-0.1.0-alpha.1.mbz"

(
    cd "$dist_dir"
    sha256sum -c SHA256SUMS
)

python3 "$repo_dir/scripts/verify-python-course-distribution.py" \
    "$artifact" --shortname PYAI-INTRO --language en

echo 'English Python course distribution verified.'
