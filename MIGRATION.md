# Migration Guide: Complete Class-Based Refactoring

## ✅ Completed Tasks

### 1. New Class Files Created
All new class-based implementations in `src/web-file-browser-api/`:

- ✅ `RequestHandler.php` - Abstract base class for HTTP endpoints
- ✅ `PathSecurity.php` - Path resolution, filename validation, sequential naming
- ✅ `DirectoryScanner.php` - Directory scanning with ItemType enum
- ✅ `FileOperations.php` - File move and rename operations
- ✅ `UploadValidator.php` - Upload validation and file handling
- ✅ `Exceptions.php` - Custom exception definitions

### 2. New Test Files Created
All tests now target the new class-based API:

- ✅ `test/PathSecurity.test.php` (replaces `filepath_utils.test.php`)
- ✅ `test/DirectoryScanner.test.php` (replaces `get_directory_structure.test.php`)
- ✅ `test/FileOperations.move.test.php` (replaces `move_file.test.php`)
- ✅ `test/FileOperations.rename.test.php` (replaces `rename_file.test.php`)
- ✅ `test/UploadValidator.test.php` (new)

### 3. Refactored Endpoint Files
All endpoints now use the new `RequestHandler` base class:

- ✅ `public/web-file-browser-api/list/index.new.php`
- ✅ `public/web-file-browser-api/upload/index.new.php`
- ✅ `public/web-file-browser-api/rename/index.new.php`
- ✅ `public/web-file-browser-api/upload-images/index.new.php`

### 4. Removed Backward Compatibility
All global function wrappers have been removed from the new classes. The classes now use pure static methods and instance methods.

---

## 🚀 Migration Steps

### Step 1: Run New Tests (If PHP is available locally)

```bash
# Run all new tests
./run-tests.sh

# Or run individually
php test/PathSecurity.test.php
php test/DirectoryScanner.test.php
php test/FileOperations.move.test.php
php test/FileOperations.rename.test.php
php test/UploadValidator.test.php
```

### Step 2: Deploy to Test Environment

Since PHP is not available locally, deploy to your test server and run tests there:

```bash
# Upload new files to test server
# Then SSH into test server and run:
cd /path/to/web-file-browser-api
php test/PathSecurity.test.php
php test/DirectoryScanner.test.php
php test/FileOperations.move.test.php
php test/FileOperations.rename.test.php
php test/UploadValidator.test.php
```

### Step 3: Test New Endpoints

Test each refactored endpoint:

```bash
# On test server, temporarily rename to test:
cd public/web-file-browser-api/list
mv index.php index.old.php
mv index.new.php index.php

# Test via HTTP requests
# Then rollback if issues:
mv index.php index.new.php
mv index.old.php index.php
```

### Step 4: Switchover (When Confident)

Once all tests pass and endpoints work correctly:

```bash
cd public/web-file-browser-api

# List endpoint
cd list
mv index.php index.old.php
mv index.new.php index.php

# Upload endpoint
cd ../upload
mv index.php index.old.php
mv index.new.php index.php

# Rename endpoint
cd ../rename
mv index.php index.old.php
mv index.new.php index.php

# Upload-images endpoint
cd ../upload-images
mv index.php index.old.php
mv index.new.php index.php
```

### Step 5: Update copilot-instructions.md

Update the documentation to reflect the new class-based architecture:

```markdown
## Architecture Overview

PHP-based REST API with class-based architecture:

- **`public/web-file-browser-api/`** - HTTP endpoints using RequestHandler base class
- **`src/web-file-browser-api/`** - Core classes:
  - `RequestHandler.php` - Abstract base for endpoints
  - `PathSecurity` - Path validation and security
  - `DirectoryScanner` - Directory listing
  - `FileOperations` - File move/rename
  - `UploadValidator` - Upload validation
  - `Exceptions.php` - Custom exceptions

## Class Usage Examples

### Path Security
```php
PathSecurity::resolveSafePath($baseDir, $userPath);
PathSecurity::validateFileName($fileName);
PathSecurity::constructSequentialFilePath($dir, $filename);
```

### Directory Operations
```php
$items = DirectoryScanner::scan($path);
foreach ($items as $item) {
    echo $item->type->value . ': ' . $item->name;
}
```

### File Operations
```php
$newPath = FileOperations::move($filePath, $destDir);
$renamedPath = FileOperations::rename($dir, $oldName, $newName);
```

### Upload Validation
```php
$validator = new UploadValidator(
    allowedMimeTypes: ['image/jpeg', 'image/png'],
    maxFileSize: 10 * 1024 * 1024
);
$validator->validate($_FILES['file']);
$destPath = $validator->uploadFile($targetDir, $_FILES['file']);
```

### Creating New Endpoints
```php
final class DeleteHandler extends RequestHandler
{
    protected array $allowedMethods = ['POST'];
    
    protected function process(): void
    {
        $path = $this->getInput(INPUT_POST, 'path', '');
        $target = $this->resolvePath($path);
        
        if (!is_file($target)) {
            throw new RuntimeException('File not found.');
        }
        
        if (!unlink($target)) {
            throw new RuntimeException('Failed to delete file.');
        }
        
        $this->sendSuccess();
    }
}

(new DeleteHandler())->handle();
```
```

### Step 6: Clean Up Old Files (After Confirmation Period)

After 1-2 weeks of successful operation:

```bash
cd src/web-file-browser-api

# Remove old utility files (replaced by classes)
rm filepath_utils.php
rm get_directory_structure.php
rm move_file.php
rm rename_file.php
rm common_utils.php  # sendJson now in RequestHandler

cd ../../public/web-file-browser-api

# Remove old endpoint backups
rm list/index.old.php
rm upload/index.old.php
rm rename/index.old.php
rm upload-images/index.old.php

cd ../../test

# Remove old tests
rm filepath_utils.test.php
rm get_directory_structure.test.php
rm move_file.test.php
rm rename_file.test.php
```

---

## 📊 Benefits Achieved

### Code Reduction
- **List endpoint**: 56 → 31 lines (-45%)
- **Upload endpoint**: 82 → 35 lines (-57%)
- **Rename endpoint**: 56 → 42 lines (-25%)
- **Upload-images endpoint**: 100 → 64 lines (-36%)
- **Average reduction**: 41%

### Improved Maintainability
- ✅ Single source of truth for validation logic
- ✅ Clear separation of concerns
- ✅ Consistent error handling across all endpoints
- ✅ Easy to test with static methods
- ✅ No global function pollution

### Enhanced Development Velocity
- ✅ New endpoints can be created in ~20 lines
- ✅ Reusable validation and security logic
- ✅ Type safety with PHP 8.1+ features
- ✅ IDE autocomplete support for all classes

---

## 🔍 Testing Checklist

Before going live, verify:

- [ ] All new tests pass (`PathSecurity`, `DirectoryScanner`, `FileOperations`, `UploadValidator`)
- [ ] List endpoint works with data and trash directories
- [ ] Upload endpoint handles JPEG, PNG, PDF correctly
- [ ] Rename endpoint validates filenames properly
- [ ] Upload-images handles batch uploads with size limits
- [ ] Error messages are consistent and user-friendly
- [ ] MIME type validation works correctly
- [ ] Sequential file naming prevents overwrites
- [ ] Path traversal attacks are blocked
- [ ] Cross-device file moves work (copy + unlink fallback)

---

## 🆘 Rollback Plan

If issues are discovered after deployment:

### Quick Rollback (Per Endpoint)
```bash
cd public/web-file-browser-api/[endpoint]
mv index.php index.broken.php
mv index.old.php index.php
```

### Full Rollback (All Endpoints)
```bash
cd public/web-file-browser-api
for dir in list upload rename upload-images; do
    cd $dir
    mv index.php index.broken.php
    mv index.old.php index.php
    cd ..
done
```

The old files (`filepath_utils.php`, etc.) remain in place, so the old endpoints will work immediately.

---

## 📝 Next Steps

1. **Deploy to test server** and run all tests
2. **Test each endpoint** via HTTP requests
3. **Monitor error logs** for any issues
4. **Switch over** when confident
5. **Update documentation** (copilot-instructions.md)
6. **Clean up old files** after confirmation period

---

## 💡 Future Enhancements

With the new class-based architecture, it's now easy to add:

- **Authentication middleware** (extend RequestHandler)
- **Rate limiting** (add to RequestHandler::handle())
- **Logging middleware** (add to RequestHandler)
- **Response caching** (add to specific endpoints)
- **File compression** (add to UploadValidator)
- **Image resizing** (add to UploadValidator for images)
- **Webhook notifications** (add to FileOperations)

All without modifying existing endpoints!
