#!/usr/bin/env bash

set -e

# Remove unwanted files in distignore.
files=(`cat ".distignore"`)

for item in "${files[@]}"; do
  if [ -e $item ]; then
    rm -frv $item
  fi
done

# Remove files not necessary for composer.
rm -frv vendor/
rm -frv virtual-member.php
