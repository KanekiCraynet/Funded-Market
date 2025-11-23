# PHASE 2 - SECURITY HARDENING
## Implementation Plan & Roadmap

**Priority**: 🔴 CRITICAL  
**Timeline**: Week 3 (estimated 1 week)  
**Status**: 🚧 PLANNING

---

## 🎯 OBJECTIVES

Transform the application from "functional" to "production-ready" by addressing all critical security vulnerabilities and implementing industry-standard security practices.

---

## 🔍 SECURITY ASSESSMENT

### **Current State Analysis**:

#### 🔴 **CRITICAL Issues** (Must Fix):
1. ❌ API keys stored in plain text config files
2. ❌ Sanctum incompatibility (PHP 8.4) - using SimpleTokenAuth workaround
3. ❌ No input sanitization layer
4. ❌ No rate limiting implemented
5. ❌ No API key rotation mechanism

#### 🟡 **HIGH Priority** (Should Fix):
6. ⚠️ No security headers (CSP, HSTS, etc.)
7. ⚠️ CSRF protection not verified
8. ⚠️ Session security not hardened
9. ⚠️ File upload endpoints not secured
10. ⚠️ No intrusion detection

#### 🟢 **MEDIUM Priority** (Nice to Have):
11. 💡 No security monitoring/alerting
12. 💡 No security audit logging
13. 💡 No WAF (Web Application Firewall)
14. 💡 No DDoS protection

---

## 📋 IMPLEMENTATION TASKS

### **TASK 1: API Key Encryption** 🔴 CRITICAL
**Priority**: P0 - Must have  
**Estimated Time**: 3-4 hours

#### Current Problem:
```php
// .env file (INSECURE!)
NEWSAPI_KEY=your_plain_text_key_here
GEMINI_API_KEY=your_plain_text_key_here
```

#### Solutions (Choose One):

**Option A: Database Encryption** (Recommended for Laravel)
- Store encrypted keys in database
- Use Laravel's encryption (AES-256)
- Key rotation support
- Audit trail
- Easy to implement

**Option B: AWS Secrets Manager** (Best for production)
- Centralized secret management
- Automatic rotation
- IAM integration
- Audit logging
- More complex setup

**Option C: HashiCorp Vault** (Enterprise grade)
- Dynamic secrets
- Encryption as a service
- PKI management
- Most complex

#### Implementation Steps:
1. Create `api_keys` table with encryption
2. Create `ApiKeyService` for encrypted storage
3. Migrate keys from `.env` to database
4. Update all services to use `ApiKeyService`
5. Add key rotation mechanism
6. Test all integrations

#### Files to Create/Modify:
- `database/migrations/XXXX_create_api_keys_table.php` (NEW)
- `app/Models/ApiKey.php` (NEW)
- `app/Services/ApiKeyService.php` (NEW)
- `app/Domain/Sentiment/Services/SentimentEngine.php` (UPDATE)
- `app/Domain/LLM/Services/LLMOrchestrator.php` (UPDATE)
- `.env.example` (UPDATE - remove sensitive keys)

---

### **TASK 2: Sanctum PHP 8.4 Compatibility** 🔴 CRITICAL
**Priority**: P0 - Must have  
**Estimated Time**: 2-3 hours

#### Current Problem:
```php
// SimpleTokenAuth workaround due to Sanctum incompatibility
// Not production-ready!
```

#### Investigation Steps:
1. Check Sanctum version compatibility
2. Review PHP 8.4 breaking changes
3. Check for community patches
4. Test Sanctum with PHP 8.4

#### Solutions (Choose One):

**Option A: Update Sanctum**
- Check for compatible version
- Update `composer.json`
- Test thoroughly

**Option B: Fix SimpleTokenAuth**
- Make it production-ready
- Add proper token hashing
- Implement token rotation
- Add token expiration

**Option C: Switch to JWT**
- Use `tymon/jwt-auth`
- More complex setup
- Better for APIs

#### Implementation Steps:
1. Investigate compatibility
2. Choose solution
3. Implement fix
4. Update authentication tests
5. Verify all endpoints work

#### Files to Modify:
- `composer.json` (UPDATE)
- `app/Http/Middleware/SimpleTokenAuth.php` (UPDATE or REMOVE)
- `config/sanctum.php` (UPDATE or CREATE)
- `app/Models/User.php` (UPDATE)
- Tests (UPDATE)

---

### **TASK 3: Input Sanitization Layer** 🔴 CRITICAL
**Priority**: P0 - Must have  
**Estimated Time**: 4-5 hours

#### Current Problem:
- No centralized input validation
- XSS vulnerabilities possible
- SQL injection risk (though Eloquent helps)
- Command injection possible

#### Implementation:
1. Create `SanitizationService`
2. Create `SanitizeInput` middleware
3. Add FormRequest classes for all endpoints
4. Implement XSS prevention
5. Add SQL injection tests
6. Add command injection prevention

#### Features:
- HTML entity encoding
- Strip dangerous tags
- Validate data types
- Whitelist allowed characters
- File upload validation
- JSON payload validation

#### Files to Create:
- `app/Services/SanitizationService.php` (NEW)
- `app/Http/Middleware/SanitizeInput.php` (NEW)
- `app/Http/Requests/Quant/IndicatorsRequest.php` (NEW)
- `app/Http/Requests/Quant/TrendsRequest.php` (NEW)
- `app/Http/Requests/Quant/VolatilityRequest.php` (NEW)
- `app/Http/Requests/Sentiment/SentimentRequest.php` (NEW)
- `app/Http/Requests/Sentiment/NewsRequest.php` (NEW)

---

### **TASK 4: Rate Limiting** 🔴 CRITICAL
**Priority**: P0 - Must have  
**Estimated Time**: 2-3 hours

#### Current Problem:
- No rate limiting = API abuse possible
- DDoS vulnerability
- Cost explosion from external APIs

#### Implementation:
1. Configure Laravel rate limiting
2. Add per-user limits
3. Add per-IP limits
4. Add per-endpoint limits
5. Add custom rate limit responses
6. Add rate limit headers

#### Rate Limit Strategy:
```
Tier 1 (Anonymous): 10 requests/minute
Tier 2 (Authenticated): 60 requests/minute
Tier 3 (Premium): 300 requests/minute
```

#### Special Limits:
- Analysis generation: 5/hour per user
- LLM calls: 10/hour per user
- News fetching: 30/hour per user

#### Files to Create/Modify:
- `app/Http/Middleware/ThrottleRequests.php` (CUSTOMIZE)
- `routes/api.php` (UPDATE - add throttle middleware)
- `app/Providers/RouteServiceProvider.php` (UPDATE)
- `config/cache.php` (UPDATE for rate limit storage)

---

### **TASK 5: Security Headers** 🟡 HIGH
**Priority**: P1 - Should have  
**Estimated Time**: 2 hours

#### Headers to Add:
1. **Content-Security-Policy (CSP)**
   ```
   Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';
   ```

2. **X-Frame-Options**
   ```
   X-Frame-Options: DENY
   ```

3. **X-Content-Type-Options**
   ```
   X-Content-Type-Options: nosniff
   ```

4. **Strict-Transport-Security (HSTS)**
   ```
   Strict-Transport-Security: max-age=31536000; includeSubDomains
   ```

5. **X-XSS-Protection**
   ```
   X-XSS-Protection: 1; mode=block
   ```

6. **Referrer-Policy**
   ```
   Referrer-Policy: strict-origin-when-cross-origin
   ```

7. **Permissions-Policy**
   ```
   Permissions-Policy: geolocation=(), microphone=(), camera=()
   ```

#### Files to Create:
- `app/Http/Middleware/SecurityHeaders.php` (NEW)
- `app/Http/Kernel.php` (UPDATE)

---

### **TASK 6: CSRF Protection** 🟡 HIGH
**Priority**: P1 - Should have  
**Estimated Time**: 1 hour

#### Current State:
- API routes typically exempt from CSRF
- But verify web routes are protected

#### Implementation:
1. Verify `VerifyCsrfToken` middleware
2. Add CSRF to web routes
3. Exempt API routes (stateless)
4. Add CSRF testing

#### Files to Review:
- `app/Http/Middleware/VerifyCsrfToken.php`
- `app/Http/Kernel.php`
- `routes/web.php`

---

### **TASK 7: Session Security** 🟡 HIGH
**Priority**: P1 - Should have  
**Estimated Time**: 1 hour

#### Hardening Steps:
1. Secure session cookies
2. HTTPOnly flag
3. Secure flag (HTTPS only)
4. SameSite attribute
5. Session timeout
6. Session regeneration on login

#### Files to Modify:
- `config/session.php` (UPDATE)

---

### **TASK 8: File Upload Security** 🟡 HIGH
**Priority**: P1 - Should have  
**Estimated Time**: 2 hours

#### Security Checks:
1. File type validation (whitelist)
2. File size limits
3. File extension validation
4. MIME type verification
5. Virus scanning (if applicable)
6. Store outside web root
7. Generate random filenames

#### Files to Check:
- Search for file upload endpoints
- Add validation

---

### **TASK 9: SQL Injection Prevention** 🟡 HIGH
**Priority**: P1 - Should have  
**Estimated Time**: 1 hour

#### Audit:
1. Review all raw queries
2. Ensure all use parameter binding
3. Test with SQL injection payloads
4. Add automated tests

---

### **TASK 10: API Key Rotation** 🟡 HIGH
**Priority**: P1 - Should have  
**Estimated Time**: 2 hours

#### Implementation:
1. Add rotation schedule
2. Add rotation API endpoint
3. Support multiple active keys
4. Graceful key expiration
5. Rotation logging

#### Files to Create:
- `app/Console/Commands/RotateApiKeys.php` (NEW)
- Update `ApiKeyService` (UPDATE)

---

### **TASK 11: Security Audit Logging** 🟢 MEDIUM
**Priority**: P2 - Nice to have  
**Estimated Time**: 2 hours

#### Events to Log:
- Login attempts (success/failure)
- API key usage
- Rate limit exceeded
- Suspicious activity
- Permission denied
- Token expired/invalid

#### Files to Create:
- `app/Services/SecurityAuditLogger.php` (NEW)
- `database/migrations/XXXX_create_security_audit_logs_table.php` (NEW)

---

### **TASK 12: Intrusion Detection** 🟢 MEDIUM
**Priority**: P2 - Nice to have  
**Estimated Time**: 3 hours

#### Detection Rules:
- Multiple failed logins
- Rate limit violations
- Invalid tokens
- SQL injection attempts
- XSS attempts

#### Response:
- Log event
- Notify admin
- Temporary ban (IP/user)

---

## 📊 IMPLEMENTATION PHASES

### **Phase 2A: Critical Security** (Week 3, Days 1-3)
1. ✅ API Key Encryption
2. ✅ Sanctum Fix or SimpleTokenAuth hardening
3. ✅ Input Sanitization
4. ✅ Rate Limiting

**Goal**: Fix all P0 issues

---

### **Phase 2B: High Priority** (Week 3, Days 4-5)
5. ✅ Security Headers
6. ✅ CSRF Verification
7. ✅ Session Security
8. ✅ File Upload Security
9. ✅ SQL Injection Audit

**Goal**: Fix all P1 issues

---

### **Phase 2C: Testing & Validation** (Week 3, Days 6-7)
10. Run security scanner (OWASP ZAP)
11. Penetration testing
12. Code review
13. Documentation

**Goal**: Verify all fixes work

---

## 🧪 TESTING STRATEGY

### **Automated Tests**:
- XSS attack tests
- SQL injection tests
- CSRF tests
- Rate limiting tests
- Authentication tests
- Authorization tests

### **Manual Tests**:
- Burp Suite scan
- OWASP ZAP scan
- Manual penetration testing
- Code review

### **Tools**:
- OWASP ZAP
- Burp Suite Community
- SQLMap (for SQL injection)
- XSStrike (for XSS)

---

## ✅ SUCCESS CRITERIA

- [ ] All API keys encrypted
- [ ] Authentication system production-ready
- [ ] All inputs sanitized
- [ ] Rate limiting functional
- [ ] Security headers present
- [ ] CSRF protection verified
- [ ] No critical vulnerabilities (OWASP Top 10)
- [ ] Security audit passed
- [ ] All tests passing
- [ ] Documentation complete

---

## 📝 DELIVERABLES

1. **Code**:
   - 10+ new files
   - 15+ modified files
   - ~2000 lines of code

2. **Tests**:
   - 30+ security tests
   - Penetration test report

3. **Documentation**:
   - Security audit report
   - API key management guide
   - Rate limiting documentation
   - Security best practices

---

## 🚨 RISKS & MITIGATION

### **Risk 1**: Breaking existing functionality
**Mitigation**: Comprehensive testing after each change

### **Risk 2**: Performance impact from sanitization
**Mitigation**: Optimize sanitization, use caching

### **Risk 3**: Sanctum fix not available
**Mitigation**: Harden SimpleTokenAuth as fallback

---

## 📈 ESTIMATED EFFORT

| Task | Hours | Priority |
|------|-------|----------|
| API Key Encryption | 4h | P0 |
| Sanctum Fix | 3h | P0 |
| Input Sanitization | 5h | P0 |
| Rate Limiting | 3h | P0 |
| Security Headers | 2h | P1 |
| CSRF Verification | 1h | P1 |
| Session Security | 1h | P1 |
| File Upload Security | 2h | P1 |
| SQL Injection Audit | 1h | P1 |
| API Key Rotation | 2h | P1 |
| Testing | 8h | - |
| Documentation | 3h | - |
| **TOTAL** | **35h** | ~1 week |

---

## 🎯 IMMEDIATE NEXT STEPS

1. **TODAY**: Start with API Key Encryption (TASK 1)
2. **TODAY**: Investigate Sanctum compatibility (TASK 2)
3. **TOMORROW**: Input Sanitization (TASK 3)
4. **TOMORROW**: Rate Limiting (TASK 4)

---

**Status**: Ready to begin Phase 2A (Critical Security)  
**First Task**: API Key Encryption

---

*Let's make this application production-ready! 🔒*
