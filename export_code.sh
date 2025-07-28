#!/bin/bash

# Remove old markdown files before regenerating
rm -f all_tests.md all_models.md all_services.md all_observers.md all_listeners.md all_exceptions.md all_events.md all_casts.md all_migrations.md all_factories.md all_seeders.md

# 1. Tests
find tests -type f | while read -r file; do
  echo "### ${file}" >> all_tests.md
  cat "$file" >> all_tests.md
  echo -e "\n" >> all_tests.md
done

# 2. Models
find app/models -type f | while read -r file; do
  echo "### ${file}" >> all_models.md
  cat "$file" >> all_models.md
  echo -e "\n" >> all_models.md
done

# 3. Services
find app/services -type f | while read -r file; do
  echo "### ${file}" >> all_services.md
  cat "$file" >> all_services.md
  echo -e "\n" >> all_services.md
done

# 4. Observers
find app/observers -type f | while read -r file; do
  echo "### ${file}" >> all_observers.md
  cat "$file" >> all_observers.md
  echo -e "\n" >> all_observers.md
done

# 5. Listeners
find app/listeners -type f | while read -r file; do
  echo "### ${file}" >> all_listeners.md
  cat "$file" >> all_listeners.md
  echo -e "\n" >> all_listeners.md
done

# 6. Exceptions
find app/exceptions -type f | while read -r file; do
  echo "### ${file}" >> all_exceptions.md
  cat "$file" >> all_exceptions.md
  echo -e "\n" >> all_exceptions.md
done

# 7. Events
find app/events -type f | while read -r file; do
  echo "### ${file}" >> all_events.md
  cat "$file" >> all_events.md
  echo -e "\n" >> all_events.md
done

# 8. Casts
find app/casts -type f | while read -r file; do
  echo "### ${file}" >> all_casts.md
  cat "$file" >> all_casts.md
  echo -e "\n" >> all_casts.md
done

# 9. Migrations
find database/migrations -type f | while read -r file; do
  echo "### ${file}" >> all_migrations.md
  cat "$file" >> all_migrations.md
  echo -e "\n" >> all_migrations.md
done

# 10. Factories
find database/factories -type f | while read -r file; do
  echo "### ${file}" >> all_factories.md
  cat "$file" >> all_factories.md
  echo -e "\n" >> all_factories.md
done

# 11. Seeders
find database/seeders -type f | while read -r file; do
  echo "### ${file}" >> all_seeders.md
  cat "$file" >> all_seeders.md
  echo -e "\n" >> all_seeders.md
done

echo "✅ All markdown files generated successfully!"
