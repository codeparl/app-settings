# TODO - Fix Group Isolation & Cache Key Generation Bug + Docs + Flush Enhancement

## Bug Fix Steps
- [x] Add `cacheKey()` helper in `SettingsResolver` to incorporate `$scope->group()` into cache keys
- [x] Update `get()`, `put()`, and `forget()` to use `cacheKey()`
- [x] Run the test suite (`vendor/bin/pest`) to confirm all tests pass

## Documentation Steps
- [x] Verify readme accuracy against actual source code
- [x] Write full documentation on how to use the package and its APIs
- [x] Document the group cache isolation behavior and cache key generation format

## Flush Enhancement Steps
- [x] Update `SettingsResolver::flush()` to clear both database AND cache for the given scope
- [x] Add tests verifying flush clears cache+DB for a context, only affects targeted context, and only affects targeted group
- [x] Run the full test suite to confirm all tests pass
- [x] Update `docs/usage.md` and `readme.md` to document the cache-aware flush behavior
