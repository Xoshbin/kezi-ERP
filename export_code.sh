#!/bin/bash

# Remove old markdown files before regenerating
rm -f all_tests.md all_migrations.md all_factories.md all_seeders.md

# 1. app directory and subdirectories
# Go to the app directory (adjust path if needed)
cd app || exit

# Remove old markdown files created in previous runs
rm -f ../all_*.md

# Loop through all subdirectories inside app
for dir in */ ; do
  dirname=$(basename "$dir")
  lowername=$(echo "$dirname" | tr '[:upper:]' '[:lower:]')
  outfile="../all_${lowername}.md"

  # Find all files inside this directory (recursively)
  find "$dir" -type f | while read -r file; do
    echo "### ${file}" >> "$outfile"
    cat "$file" >> "$outfile"
    echo -e "\n" >> "$outfile"
  done

  echo "Generated $outfile"
done

cd ..

# 2. Migrations
find database/migrations -type f | while read -r file; do
  echo "### ${file}" >> all_migrations.md
  cat "$file" >> all_migrations.md
  echo -e "\n" >> all_migrations.md
done

# 3. Factories
find database/factories -type f | while read -r file; do
  echo "### ${file}" >> all_factories.md
  cat "$file" >> all_factories.md
  echo -e "\n" >> all_factories.md
done

# 4. Seeders
find database/seeders -type f | while read -r file; do
  echo "### ${file}" >> all_seeders.md
  cat "$file" >> all_seeders.md
  echo -e "\n" >> all_seeders.md
done

# 5. Tests
find tests -type f | while read -r file; do
  echo "### ${file}" >> all_tests.md
  cat "$file" >> all_tests.md
  echo -e "\n" >> all_tests.md
done

echo "✅ All markdown files generated successfully!"
