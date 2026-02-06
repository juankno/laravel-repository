# Release Notes - v1.7.1

**Release Date**: February 6, 2026

## 🔒 Security Release

This release focuses on addressing security vulnerabilities and implementing protection mechanisms to make the package more secure by default.

---

## 🛡️ Security Fixes

### Critical Fixes

#### 1. SQL Injection Prevention
- **Issue**: Column names and ORDER BY directions were not validated, allowing potential SQL injection attacks
- **Fix**: 
  - Added `SecurityHelper` class for input validation
  - All column names are now validated against pattern: `/^[a-zA-Z0-9_.*]+$/`
  - ORDER BY directions validated against whitelist
  - Invalid inputs throw `InvalidArgumentException`
- **Files Changed**: 
  - `src/Helpers/SecurityHelper.php` (new)
  - `src/Traits/QueryableTrait.php`
  - `src/Traits/CrudOperationsTrait.php`
  - `src/Traits/PaginationTrait.php`

#### 2. DoS Protection - Query Limits
- **Issue**: `all()` method could return unlimited records, potentially exhausting server memory
- **Fix**: 
  - Added configurable maximum results limit (default: 10,000)
  - Automatically applied to `all()` method
  - Configurable via `repository.security.max_results_limit`
- **Files Changed**: `src/Traits/CrudOperationsTrait.php`

#### 3. Path Traversal Protection
- **Issue**: `make:repository` command could potentially create files outside expected directory
- **Fix**: 
  - Enhanced validation to prevent `..` directory traversal
  - Added path component validation
  - Verifies resolved path is within allowed directory
- **Files Changed**: `src/Commands/MakeRepositoryCommand.php`

#### 4. Sensitive Data Exposure in Logs
- **Issue**: SQL queries with sensitive data were logged without sanitization
- **Fix**: 
  - Added `sanitizeSqlForLogging()` method
  - SQL bindings replaced with `[REDACTED]` in logs
  - Configurable via `repository.security.sanitize_logs`
- **Files Changed**: `src/Traits/ScopableTrait.php`

---

## 📦 Dependency Updates

- Updated PHPUnit from `^9.0` to `^10.0|^11.0`
- Ensures compatibility with latest PHP versions and security patches

---

## ⚙️ New Configuration Options

Added new `security` section in configuration:

```php
'security' => [
    'validate_column_names' => true,
    'max_results_limit' => 10000,
    'allowed_order_directions' => ['asc', 'desc', 'ASC', 'DESC'],
    'sanitize_logs' => true,
    'strict_column_validation' => false,
],
```

---

## 📝 New Files

- `src/Helpers/SecurityHelper.php` - Security validation helper class
- `SECURITY.md` - Comprehensive security documentation
- `RELEASE_NOTES_v1.7.1.md` - This file

---

## 🔄 Breaking Changes

### Exception Handling

Methods now throw `InvalidArgumentException` when invalid column names are detected:

```php
// This will now throw an exception
try {
    $repository->findWhere(['invalid column!' => 'value']);
} catch (InvalidArgumentException $e) {
    // Handle invalid column name
}
```

### Query Limits

The `all()` method now has a default limit of 10,000 records. To get unlimited results:

```php
// In config/repository.php
'security' => [
    'max_results_limit' => 0, // Disable limit
]
```

---

## 📚 Documentation Updates

- Added comprehensive security section to README.es.md
- Created SECURITY.md with detailed security information
- Documented all security configurations
- Added best practices guide for developers

---

## 🔧 Migration Guide

### From v1.7.0 to v1.7.1

1. **Update dependency** (if using dev dependencies):
   ```bash
   composer update juankno/laravel-repository
   ```

2. **Publish updated configuration** (optional):
   ```bash
   php artisan vendor:publish --tag=repository-config --force
   ```

3. **Review security settings** in `config/repository.php`:
   ```php
   'security' => [
       'validate_column_names' => true,
       'max_results_limit' => 10000,
       'sanitize_logs' => true,
   ],
   ```

4. **Update exception handling** if you were passing dynamic column names:
   ```php
   // Before
   $repository->findWhere([$userInputColumn => $value]);
   
   // After - validate column names
   $allowedColumns = ['id', 'name', 'email'];
   if (!in_array($userInputColumn, $allowedColumns)) {
       throw new InvalidArgumentException('Invalid column');
   }
   $repository->findWhere([$userInputColumn => $value]);
   ```

5. **Test your application** to ensure no unexpected exceptions are thrown

---

## ⚠️ Important Notes

### Developer Responsibilities

While this package now includes security protections, developers must still:

1. ✅ Validate user input before passing to repositories
2. ✅ Define `$fillable` or `$guarded` in Eloquent models
3. ✅ Implement proper authorization checks
4. ✅ Use pagination instead of `all()` for large datasets
5. ✅ Never pass user-controlled callables to scope parameters

### Recommended Actions

1. **Review your code** for places where you call repository methods with user input
2. **Add input validation** using Laravel's validation features
3. **Update tests** to account for new security exceptions
4. **Enable strict mode** if you want maximum security:
   ```php
   'security' => [
       'strict_column_validation' => true,
   ],
   ```

---

## 🐛 Bug Fixes

- Fixed potential memory exhaustion in `all()` method
- Fixed path traversal vulnerability in command
- Fixed sensitive data exposure in debug logs

---

## ✨ Improvements

- Better error messages for security-related exceptions
- Improved validation performance
- More configurable security options
- Comprehensive security documentation

---

## 🙏 Credits

Special thanks to the security research community for highlighting these vulnerabilities.

---

## 📞 Support

If you have questions about this security release:

- Read [SECURITY.md](SECURITY.md) for detailed information
- Check [README.es.md](README.es.md) for usage examples
- Open an issue on GitHub (for non-security issues)
- Email for security concerns: tu-email@example.com

---

## 🔜 Next Steps

We recommend:

1. Updating to v1.7.1 as soon as possible
2. Reviewing your code for security best practices
3. Enabling security features in configuration
4. Reading the security documentation

---

## Full Changelog

```
v1.7.1 (2026-02-06)
-------------------
Security:
  - Added SQL injection prevention for column names
  - Implemented DoS protection with query limits
  - Fixed path traversal vulnerability in make:repository command
  - Added sensitive data sanitization in logs
  
Dependencies:
  - Updated phpunit/phpunit to ^10.0|^11.0
  
Features:
  - Added SecurityHelper class for validation
  - Added comprehensive security configuration options
  - Added SECURITY.md documentation
  
Breaking Changes:
  - Methods now throw InvalidArgumentException for invalid column names
  - all() method now has default limit of 10,000 records
```

---

**Thank you for using Laravel Repository Package!**
