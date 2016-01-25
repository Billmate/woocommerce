#!/bin/bash

echo "[Info] Replacing $1 with $2"
ack --ignore-file=is:README.md --ignore-dir={languages,library} -l --print0 "$1" | xargs -0 perl -pi -e "/\since/ || s/$1/$2/g"

git status
git diff