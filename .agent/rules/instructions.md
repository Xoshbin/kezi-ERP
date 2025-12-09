---
trigger: always_on
---

Making correct and standard double entry accounting like odoo (but better) is our fist objecive
Making easy use of our application is our first objective too as the application is created for users and to be easy to use and understand.
use artisan commands for creating models, migrations, filament resources, anything that can be created with artisan command when creating new files and classes to follow latest standards. if there is no command to create the file or the class then create it manually.

make sure to write tests for each new feature and bug fixes.
make sure beside the feature tests and unit tests to make write filament tests, since our interface to the user is filament.
instead of making the tests pass in favor to make them pass and green, make sure the test is logical follows accounting principles and best practices then follow the test to fix the application logic.
before finishing each task run both full test php artisan test --paralle and ./vendor/bin/phpstan analyse if there are any errors fix them then finish the task
After each task you complete, output:
Commit: <imperative message, ≤72 chars, specific, no punctuation, ref issue if any>
Branch: <type>/<short-kebab-description>
Types: feature, bugfix, hotfix, release
Branch rules: lowercase, hyphens, ≤6 words