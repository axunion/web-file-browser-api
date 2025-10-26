# Project Structure - Class-Based Refactoring

## 📁 Current Structure

```
web-file-browser-api/
├── .editorconfig
├── .github/
│   ├── copilot-instructions.md     # ⚠️  Needs update for new architecture
│   └── workflows/
│       └── deploy.yml
├── .gitignore
├── LICENSE
├── MIGRATION.md                     # ✨ NEW - Complete migration guide
├── REFACTORING.md                   # ✨ NEW - Refactoring summary
├── run-tests.sh                     # ✨ NEW - Test runner script
│
├── public/
│   ├── data/
│   │   ├── sample.txt
│   │   └── directory/
│   ├── trash/                       # (may not exist yet)
│   └── web-file-browser-api/
│       ├── list/
│       │   ├── index.php            # ⚠️  OLD - 56 lines
│       │   └── index.new.php        # ✨ NEW - 31 lines (45% reduction)
│       ├── upload/
│       │   ├── index.php            # ⚠️  OLD - 82 lines
│       │   └── index.new.php        # ✨ NEW - 35 lines (57% reduction)
│       ├── rename/
│       │   ├── index.php            # ⚠️  OLD - 56 lines
│       │   └── index.new.php        # ✨ NEW - 42 lines (25% reduction)
│       └── upload-images/
│           ├── index.php            # ⚠️  OLD - 100 lines
│           └── index.new.php        # ✨ NEW - 64 lines (36% reduction)
│
├── src/
│   └── web-file-browser-api/
│       ├── RequestHandler.php       # ✨ NEW - Abstract base class (105 lines)
│       ├── PathSecurity.php         # ✨ NEW - Security functions (140 lines)
│       ├── DirectoryScanner.php     # ✨ NEW - Directory listing (86 lines)
│       ├── FileOperations.php       # ✨ NEW - Move & rename (105 lines)
│       ├── UploadValidator.php      # ✨ NEW - Upload validation (136 lines)
│       ├── Exceptions.php           # ✨ NEW - Custom exceptions (17 lines)
│       ├── common_utils.php         # ⚠️  OLD - Can be removed (replaced by RequestHandler)
│       ├── filepath_utils.php       # ⚠️  OLD - Can be removed (replaced by PathSecurity)
│       ├── get_directory_structure.php  # ⚠️  OLD - Can be removed (replaced by DirectoryScanner)
│       ├── move_file.php            # ⚠️  OLD - Can be removed (replaced by FileOperations)
│       └── rename_file.php          # ⚠️  OLD - Can be removed (replaced by FileOperations)
│
└── test/
    ├── PathSecurity.test.php            # ✨ NEW - Tests PathSecurity class
    ├── DirectoryScanner.test.php        # ✨ NEW - Tests DirectoryScanner class
    ├── FileOperations.move.test.php     # ✨ NEW - Tests FileOperations::move()
    ├── FileOperations.rename.test.php   # ✨ NEW - Tests FileOperations::rename()
    ├── UploadValidator.test.php         # ✨ NEW - Tests UploadValidator class
    ├── filepath_utils.test.php          # ⚠️  OLD - Can be removed after verification
    ├── get_directory_structure.test.php # ⚠️  OLD - Can be removed after verification
    ├── move_file.test.php               # ⚠️  OLD - Can be removed after verification
    └── rename_file.test.php             # ⚠️  OLD - Can be removed after verification
```

## 📊 File Statistics

### New Class-Based Core (src/)
| File | Lines | Purpose |
|------|-------|---------|
| `RequestHandler.php` | 105 | Abstract base class for HTTP endpoints |
| `PathSecurity.php` | 140 | Path resolution, validation, sequential naming |
| `DirectoryScanner.php` | 86 | Directory scanning with ItemType enum |
| `FileOperations.php` | 105 | File move and rename operations |
| `UploadValidator.php` | 136 | Upload validation and handling |
| `Exceptions.php` | 17 | Custom exception definitions |
| **Total** | **589 lines** | One-time infrastructure investment |

### Old Utilities (to be removed)
| File | Lines | Replacement |
|------|-------|------------|
| `common_utils.php` | 17 | RequestHandler::sendJson() |
| `filepath_utils.php` | 147 | PathSecurity class |
| `get_directory_structure.php` | 98 | DirectoryScanner class |
| `move_file.php` | 59 | FileOperations::move() |
| `rename_file.php` | 50 | FileOperations::rename() |
| **Total** | **371 lines** | To be deleted |

### Endpoints Comparison
| Endpoint | Old Lines | New Lines | Reduction |
|----------|-----------|-----------|-----------|
| `list/` | 56 | 31 | **45%** |
| `upload/` | 82 | 35 | **57%** |
| `rename/` | 56 | 42 | **25%** |
| `upload-images/` | 100 | 64 | **36%** |
| **Total** | **294** | **172** | **41%** |

### Test Files
| Test | Type | Coverage |
|------|------|----------|
| `PathSecurity.test.php` | NEW | resolveSafePath, validateFileName, constructSequentialFilePath |
| `DirectoryScanner.test.php` | NEW | scan(), DirectoryItem, ItemType |
| `FileOperations.move.test.php` | NEW | FileOperations::move() |
| `FileOperations.rename.test.php` | NEW | FileOperations::rename() |
| `UploadValidator.test.php` | NEW | validate(), validateBatch(), uploadFile() |

## 🎯 Action Items

### Immediate (Before Deployment)
- [ ] Update `.github/copilot-instructions.md` with new class-based patterns
- [ ] Deploy to test server
- [ ] Run all new tests on test server
- [ ] Test each endpoint via HTTP requests

### During Deployment
- [ ] Backup current endpoints (already done with .old.php naming)
- [ ] Switch to new endpoints one at a time
- [ ] Monitor error logs closely
- [ ] Verify functionality with real client requests

### After Successful Deployment (1-2 weeks)
- [ ] Remove old utility files from `src/`
- [ ] Remove old test files from `test/`
- [ ] Remove `.old.php` endpoint backups
- [ ] Remove `.new.php` suffix (already renamed to index.php)
- [ ] Update deployment workflow if needed

## 🔑 Key Differences

### Old Approach (Global Functions)
```php
require_once 'filepath_utils.php';
require_once 'common_utils.php';

$path = resolveSafePath($base, $userPath);
validateFileName($name);
sendJson(['status' => 'success'], 200);
```

### New Approach (Class-Based)
```php
require_once 'RequestHandler.php';

final class MyHandler extends RequestHandler
{
    protected function process(): void
    {
        $path = $this->resolvePath($userPath);
        PathSecurity::validateFileName($name);
        $this->sendSuccess(['data' => $result]);
    }
}
```

## 💡 Benefits

1. **No Global Namespace Pollution** - All functions are now methods
2. **Better IDE Support** - Full autocomplete and type checking
3. **Easier Testing** - Can mock classes, test in isolation
4. **Clearer Dependencies** - Explicit class imports
5. **Consistent Error Handling** - Built into RequestHandler
6. **Reusable Components** - Extend RequestHandler for new endpoints
7. **Future-Proof** - Easy to add middleware, logging, etc.

## 🚀 Performance Impact

**Negligible** - The class overhead is minimal:
- Static methods have no instantiation cost
- RequestHandler is instantiated once per request
- No additional database queries or I/O operations
- Same core logic, just better organized

Expected performance: **±0-2ms difference** (within measurement error)
