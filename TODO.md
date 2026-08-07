# TODO - Fix Group Islandess in SettingsRepository::query()

## Bug Fix Steps
- [x] Update `SettingsRepository::query()` to enforce `group IS NULL` when no group is scoped
- [x] Run the test suite (`vendor/bin/pest`) to confirm all tests pass
