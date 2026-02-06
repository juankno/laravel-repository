# Security Policy

## Security Improvements - v1.7.1

This document outlines the security improvements implemented in this package to protect against common vulnerabilities.

## Implemented Security Fixes

### 1. SQL Injection Prevention ✅

**Vulnerability**: Column names and ORDER BY directions were not validated, allowing potential SQL injection attacks.

**Fix**: 
- Added `SecurityHelper` class to validate column names and ORDER BY directions
- Column names are now validated against a regex pattern: `/^[a-zA-Z0-9_.*]+$/`
- ORDER BY directions are validated against a whitelist: `['asc', 'desc', 'ASC', 'DESC']`
- All query methods now throw `InvalidArgumentException` when invalid column names are detected

**Configuration**:
```php
'security' => [
    'validate_column_names' => true, // Enable/disable column name validation
    'allowed_order_directions' => ['asc', 'desc', 'ASC', 'DESC'],
]
```

**Example**:
```php
// ❌ This will now throw an exception
$repository->findWhere(['id; DROP TABLE--' => 1]);

// ✅ This works correctly
$repository->findWhere(['id' => 1]);
```

---

### 2. DoS Protection - Query Limits ✅

**Vulnerability**: The `all()` method could return millions of records, potentially exhausting server memory.

**Fix**:
- Added configurable maximum results limit
- Default limit: 10,000 records
- Automatically applied to `all()` method
- Can be disabled by setting limit to 0

**Configuration**:
```php
'security' => [
    'max_results_limit' => 10000, // 0 = no limit
]
```

**Example**:
```php
// Will return maximum 10,000 records even if there are millions
$allUsers = $repository->all();
```

---

### 3. Path Traversal Protection ✅

**Vulnerability**: The `make:repository` command could potentially create files outside the expected directory.

**Fix**:
- Enhanced validation to prevent directory traversal attacks
- Added checks for `..` in repository names
- Validates each path component individually
- Verifies resolved path is within allowed directory

**Example**:
```bash
# ❌ These will now be rejected
php artisan make:repository "../../../evil"
php artisan make:repository "../../config/database"

# ✅ This works correctly
php artisan make:repository "Admin/UserRepository"
```

---

### 4. Sensitive Data Exposure in Logs ✅

**Vulnerability**: SQL queries with sensitive data were logged without sanitization.

**Fix**:
- Added `sanitizeSqlForLogging()` method to redact sensitive values
- SQL bindings are replaced with `[REDACTED]` in logs
- Only enabled when `repository.security.sanitize_logs` is true

**Configuration**:
```php
'security' => [
    'sanitize_logs' => true, // Sanitize SQL queries in logs
]
```

**Example**:
```php
// Before: [Repository N+1 Detection] Query: SELECT * FROM users WHERE email = 'user@example.com'
// After: [Repository N+1 Detection] Query: SELECT * FROM users WHERE email = '[REDACTED]'
```

---

### 5. Updated Dependencies ✅

**Vulnerability**: PHPUnit was using an outdated version (^9.0).

**Fix**:
- Updated to PHPUnit `^10.0|^11.0`
- Supports latest PHP versions and security patches

---

## Configuration Options

All security features can be configured in `config/repository.php`:

```php
'security' => [
    // Validate column names to prevent SQL injection
    'validate_column_names' => true,
    
    // Maximum number of results for all() method (0 = no limit)
    'max_results_limit' => 10000,
    
    // Allowed ORDER BY directions
    'allowed_order_directions' => ['asc', 'desc', 'ASC', 'DESC'],
    
    // Sanitize SQL queries in logs
    'sanitize_logs' => true,
    
    // Strict column validation (only allow $fillable columns)
    'strict_column_validation' => false,
],
```

---

## Developer Responsibilities

While this package implements security measures, developers using the package should also:

### 1. Mass Assignment Protection

**Your Responsibility**: Always define `$fillable` or `$guarded` in your Eloquent models.

```php
class User extends Model
{
    protected $fillable = ['name', 'email']; // Whitelist approach
    // OR
    protected $guarded = ['id', 'is_admin']; // Blacklist approach
}
```

### 2. Input Validation

**Your Responsibility**: Validate user input before passing to repository methods.

```php
// ❌ Don't do this
$repository->create($request->all());

// ✅ Do this
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
]);
$repository->create($validated);
```

### 3. Scope Security

**Your Responsibility**: Never pass user-controlled callables to scope parameters.

```php
// ❌ NEVER do this
$scopes = [$request->input('scope')]; // User could inject malicious code

// ✅ Do this
$allowedScopes = ['active', 'verified', 'premium'];
$scope = $request->input('scope');
if (in_array($scope, $allowedScopes)) {
    $scopes = [$scope];
}
```

### 4. Authorization

**Your Responsibility**: Implement proper authorization checks.

```php
// Always check if user can perform the action
if (!auth()->user()->can('update', $model)) {
    abort(403);
}

$repository->update($id, $validatedData);
```

---

## Security Best Practices

### 1. Use Pagination

Instead of using `all()`, prefer pagination methods:

```php
// ❌ Potentially dangerous with large datasets
$allRecords = $repository->all();

// ✅ Better approach
$records = $repository->paginate(50);
```

### 2. Limit Relations

Be careful with eager loading to prevent N+1 queries and memory issues:

```php
// ✅ Good - controlled relations
$users = $repository->all(['*'], ['profile', 'posts']);

// ⚠️ Be careful with nested relations
$users = $repository->all(['*'], ['posts.comments.author']);
```

### 3. Use Transactions

For operations that modify multiple records:

```php
$repository->transaction(function() use ($repository, $data) {
    $repository->create($data['user']);
    $repository->create($data['profile']);
});
```

### 4. Enable Cache Carefully

If using cache, ensure proper cache key generation to prevent data leakage:

```php
'cache' => [
    'enabled' => true,
    'key_prefix' => 'repo_' . auth()->id() . '_', // Include user context
],
```

---

## Reporting Security Vulnerabilities

If you discover a security vulnerability in this package, please email:

**Email**: tu-email@example.com

Please do not create public GitHub issues for security vulnerabilities.

---

## Changelog

### v1.7.1 (2026-02-06)
- ✅ Added SQL injection prevention for column names
- ✅ Implemented DoS protection with query limits
- ✅ Fixed path traversal vulnerability in command
- ✅ Added sensitive data sanitization in logs
- ✅ Updated PHPUnit to latest version
- ✅ Added comprehensive security configuration options

---

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
