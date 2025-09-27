#!/bin/bash
# This script corrects the nested directory structures created by the move scripts.

set -e
echo "🚀 Correcting nested directory paths..."

# Helper function to fix a duplicated directory
fix_duplicate_dir() {
    local parent_dir=$1
    if [ -d "$parent_dir" ]; then
        # Find child directories with the same name as their parent
        find "$parent_dir" -mindepth 1 -maxdepth 1 -type d -exec bash -c '
            child_dir="$1"
            parent_name=$(basename "$(dirname "$child_dir")")
            child_name=$(basename "$child_dir")
            if [ "$parent_name" = "$child_name" ]; then
                echo "Fixing path: $child_dir"
                # Move contents of child up to parent
                mv "$child_dir"/* "$(dirname "$child_dir")/"
                # Remove the now-empty child directory
                rmdir "$child_dir"
            fi
        ' _ {} \;
    fi
}

# Fix duplicated paths from the error log
fix_duplicate_dir "Modules/Inventory/tests/Feature/Adjustments"
fix_duplicate_dir "Modules/Inventory/tests/Feature/Inventory"
fix_duplicate_dir "Modules/HR/tests/Feature/HumanResources"
fix_duplicate_dir "Modules/Foundation/tests/Feature/PaymentTerms"

echo "✅ Directory path cleanup complete."
