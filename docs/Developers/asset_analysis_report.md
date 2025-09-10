# Asset Management Feature: Codebase Analysis and Refined Plan

This document provides a detailed analysis of the existing codebase for the asset management feature, comparing it against the `Asset_implementation_plan.md`. It outlines existing components, identifies missing pieces, and provides a refined, actionable implementation plan for the development team.

## 1. Codebase Analysis Summary

### 1.1. Existing Components

The following components are already present in the codebase and partially align with the implementation plan:

*   **Models:**
    *   [`app/Models/Asset.php`](app/Models/Asset.php): Core model exists with most fields and relationships.
    *   [`app/Models/DepreciationEntry.php`](app/Models/DepreciationEntry.php): Core model exists with all fields and relationships.
*   **Enums:**
    *   [`app/Enums/Assets/AssetStatus.php`](app/Enums/Assets/AssetStatus.php): Exists and matches the plan.
    *   [`app/Enums/Assets/DepreciationEntryStatus.php`](app/Enums/Assets/DepreciationEntryStatus.php): Exists and matches the plan.
*   **Database Migrations:**
    *   [`database/migrations/2025_07_21_144632_create_assets_table.php`](database/migrations/2025_07_21_144632_create_assets_table.php): The `assets` table is created.
    *   [`database/migrations/2025_07_21_144642_create_depreciation_entries_table.php`](database/migrations/2025_07_21_144642_create_depreciation_entries_table.php): The `depreciation_entries` table is created.
*   **Services:**
    *   [`app/Services/AssetService.php`](app/Services/AssetService.php): A service file exists but only contains a basic `runDepreciation` method.
*   **Actions:**
    *   [`app/Actions/Accounting/CreateJournalEntryForDepreciationAction.php`](app/Actions/Accounting/CreateJournalEntryForDepreciationAction.php): This action correctly handles the creation of journal entries from a depreciation entry.
*   **Filament Resources:**
    *   [`app/Filament/Resources/AssetResource.php`](app/Filament/Resources/AssetResource.php): A basic CRUD resource for Assets exists.
*   **Tests:**
    *   [`tests/Feature/Assets/AssetTest.php`](tests/Feature/Assets/AssetTest.php): A basic feature test exists for the `runDepreciation` functionality.

### 1.2. Missing Components

The following components are required by the plan but are completely missing from the codebase:

*   **Data Transfer Objects (DTOs):**
    *   The entire `app/DataTransferObjects/Assets/` directory.
    *   `CreateAssetDTO.php`
    *   `ModifyAssetDTO.php`
    *   `DisposeAssetDTO.php`
*   **Actions:**
    *   The `app/Actions/Assets/` directory.
    *   `CreateAssetAction.php`
    *   `ComputeDepreciationScheduleAction.php`
    *   `ModifyAssetAction.php`
    *   `DisposeAssetAction.php`
    *   `PostDepreciationEntryAction.php`
*   **Automation:**
    *   `app/Console/Commands/ProcessDepreciations.php` (Artisan Command)
    *   `app/Jobs/ProcessDepreciationJob.php` (Queued Job)
*   **Filament Components:**
    *   `app/Filament/Resources/AssetResource/RelationManagers/DepreciationEntryRelationManager.php`

### 1.3. Components to Modify

The following existing components require modification to fully align with the implementation plan:

*   **`app/Models/Asset.php`:**
    *   **Change:** Add the polymorphic `source()` relationship to link the asset to its originating document (e.g., `VendorBill`).
*   **New Migration for `assets` table:**
    *   **Change:** Create a new migration to add the `source_type` (string) and `source_id` (unsignedBigInteger) columns to the `assets` table.
*   **`app/Services/AssetService.php`:**
    *   **Change:** Refactor the entire service. The existing `runDepreciation` logic should be moved into the new `PostDepreciationEntryAction`. The service will then be responsible for orchestrating calls to the various new Actions (`CreateAssetAction`, `ModifyAssetAction`, etc.).
*   **`app/Filament/Resources/AssetResource.php`:**
    *   **Change:**
        *   Add a "Compute Depreciation" `Action` button to the asset view page.
        *   Register the new `DepreciationEntryRelationManager`.
        *   Update the `form()` method to use the new DTOs when creating and updating assets.
*   **`tests/Feature/Assets/AssetTest.php`:**
    *   **Change:** Expand the test suite significantly to cover all the new Actions and the full lifecycle of an asset as described in the implementation plan (creation, depreciation calculation, posting, modification, disposal, and immutability checks).

## 2. Refined Implementation Steps

This revised checklist provides a granular, step-by-step guide for the `code` and `debug` modes to follow.

### Phase 1: Core Data Structure & Logic

*   [ ] **1. Create DTOs:**
    *   [ ] Create `app/DataTransferObjects/Assets/CreateAssetDTO.php`.
    *   [ ] Create `app/DataTransferObjects/Assets/ModifyAssetDTO.php`.
    *   [ ] Create `app/DataTransferObjects/Assets/DisposeAssetDTO.php`.
*   [ ] **2. Update Database:**
    *   [ ] Generate a new migration to add `source_type` and `source_id` to the `assets` table.
    *   [ ] Run the migration.
*   [ ] **3. Update Asset Model:**
    *   [ ] Add the `source()` polymorphic relationship to [`app/Models/Asset.php`](app/Models/Asset.php).
*   [ ] **4. Create Asset Actions:**
    *   [ ] Create `app/Actions/Assets/CreateAssetAction.php`.
    *   [ ] Create `app/Actions/Assets/ComputeDepreciationScheduleAction.php`.
    *   [ ] Create `app/Actions/Assets/PostDepreciationEntryAction.php` (refactoring logic from the old `AssetService`).
    *   [ ] Create `app/Actions/Assets/ModifyAssetAction.php`.
    *   [ ] Create `app/Actions/Assets/DisposeAssetAction.php`.
*   [ ] **5. Refactor AssetService:**
    *   [ ] Rewrite [`app/Services/AssetService.php`](app/Services/AssetService.php) to be a thin orchestration layer that calls the new Actions.

### Phase 2: Automation

*   [ ] **6. Create Scheduled Command:**
    *   [ ] Create the Artisan command `app/Console/Commands/ProcessDepreciations.php`.
*   [ ] **7. Create Queued Job:**
    *   [ ] Create the job `app/Jobs/ProcessDepreciationJob.php`.

### Phase 3: UI & Testing

*   [ ] **8. Create Filament Relation Manager:**
    *   [ ] Create `app/Filament/Resources/AssetResource/RelationManagers/DepreciationEntryRelationManager.php`.
*   [ ] **9. Update Filament Resource:**
    *   [ ] Update [`app/Filament/Resources/AssetResource.php`](app/Filament/Resources/AssetResource.php) with the new action button and relation manager.
*   [ ] **10. Expand Test Suite:**
    *   [ ] Add comprehensive feature tests in [`tests/Feature/Assets/AssetTest.php`](tests/Feature/Assets/AssetTest.php) for all new and modified components, ensuring full coverage of the asset lifecycle.
