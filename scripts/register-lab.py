#!/usr/bin/env python3
"""Export a Moodle LTI connection; --apply explicitly permits first registration."""
import argparse
import json
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--lab', choices=['java', 'python'], required=True)
    parser.add_argument('--url', required=True)
    parser.add_argument('--name', required=True)
    parser.add_argument('--container', default='moodle-rescue-local')
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--apply', action='store_true')
    args = parser.parse_args()
    command = ['docker','exec','-i','-u','www-data']
    for key, value in {'LAB_KIND':args.lab,'LAB_URL':args.url,'LAB_TOOL_NAME':args.name,
                        'LAB_APPLY':'yes' if args.apply and not args.output.exists() else 'no'}.items():
        command += ['-e', key+'='+value]
    result = subprocess.run(command+[args.container,'php'],
                            input=(ROOT/'scripts/register-lab-lti.php').read_text(),
                            capture_output=True, text=True, timeout=60)
    if result.returncode:
        raise RuntimeError(result.stderr or result.stdout)
    connection = json.loads(result.stdout.lstrip('\ufeff'))
    args.output.parent.mkdir(parents=True, exist_ok=True)
    if args.output.exists() and json.loads(args.output.read_text()) != connection:
        raise RuntimeError('Output already contains a different connection. Choose another output file.')
    args.output.write_text(json.dumps(connection, indent=2)+'\n')
    args.output.chmod(0o600)
    print(f'Connection exported: {args.output} (tool {connection["tool_type_id"]}); no course or user changes')


if __name__ == '__main__':
    try:
        main()
    except (RuntimeError, ValueError, OSError, subprocess.TimeoutExpired) as error:
        print('Registration stopped: '+str(error), file=sys.stderr)
        sys.exit(1)
