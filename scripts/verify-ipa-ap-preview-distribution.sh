#!/bin/sh
set -eu

repo_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
dist_dir="$repo_dir/sample-content/ap-written-practice-ja-preview/distribution"
artifact="$dist_dir/ipa-ap-written-practice-ja-preview-2025-spring-0.1.0-alpha.1.mbz"

(
    cd "$dist_dir"
    sha256sum -c SHA256SUMS
)

python3 "$repo_dir/scripts/verify-ipa-ap-preview-distribution.py" "$artifact"
echo 'IPA AP public preview course distribution verified.'
