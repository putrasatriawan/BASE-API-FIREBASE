# Test Cases - Auto Create Profile

## Test Scenario 1: New User Login (No User, No Profile)

**Setup:**
- Firebase UID: `new-user-12345`
- User tidak ada di database
- Profile tidak ada di database

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_uid": "new-user-12345"
  }'
```

**Expected Result:**
1. User baru dibuat di table `users`
2. Profile baru dibuat di table `profiles`
3. Username auto-generated (contoh: `john.doe`)
4. Response include user + profile data

**Expected Response:**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Login success"
  },
  "data": {
    "token": "1|...",
    "expires_at": "2026-02-13T...",
    "user": {
      "id": 19,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": null,
      "roles": ["user"]
    },
    "profile": {
      "full_name": "John Doe",
      "username": "john.doe",
      "phone": null,
      "avatar": null,
      "birth_date": null,
      "gender": null
    },
    "session_info": { ... }
  }
}
```

---

## Test Scenario 2: Existing User Without Profile

**Setup:**
- User ID 17 sudah ada di database
- Firebase UID: `test-uid-123`
- Profile TIDAK ada untuk user ini

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_uid": "test-uid-123"
  }'
```

**Expected Result:**
1. User existing digunakan (tidak create baru)
2. Profile baru dibuat untuk user ini
3. Username auto-generated dari user.name
4. Response include user + profile data

**Expected Response:**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Login success"
  },
  "data": {
    "token": "2|...",
    "user": {
      "id": 17,
      "name": "Test User",
      "email": "testuser@example.com",
      "roles": ["user"]
    },
    "profile": {
      "full_name": "Test User",
      "username": "test.user",
      "phone": null,
      "avatar": null,
      "birth_date": null,
      "gender": null
    }
  }
}
```

---

## Test Scenario 3: Existing User With Profile

**Setup:**
- User ID 16 sudah ada di database
- Firebase UID: `wvJHFGlerTUmhPgQpRdxWbTAnBV2`
- Profile SUDAH ada untuk user ini

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_uid": "wvJHFGlerTUmhPgQpRdxWbTAnBV2"
  }'
```

**Expected Result:**
1. User existing digunakan
2. Profile existing digunakan (TIDAK create baru)
3. Response include existing profile data
4. Phone number tetap ada (tidak di-reset)

**Expected Response:**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Login success"
  },
  "data": {
    "token": "3|...",
    "user": {
      "id": 16,
      "name": "Xorix Admin",
      "email": "xorixgroup@gmail.com",
      "roles": ["admin"]
    },
    "profile": {
      "full_name": "Xorix Admin",
      "username": "xorix.admin",
      "phone": "6281563939539",
      "avatar": null,
      "birth_date": null,
      "gender": null
    }
  }
}
```

---

## Test Scenario 4: Username Collision

**Setup:**
- User A: name = "John Doe", username akan jadi `john.doe`
- User B: name = "John Doe" (sama), username harus jadi `john.doe1`
- User C: name = "John Doe" (sama lagi), username harus jadi `john.doe2`

**Test Steps:**

### Step 1: Create first user
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase \
  -H "Content-Type: application/json" \
  -d '{"firebase_uid": "john-doe-1"}'
```
**Expected username:** `john.doe`

### Step 2: Create second user with same name
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase \
  -H "Content-Type: application/json" \
  -d '{"firebase_uid": "john-doe-2"}'
```
**Expected username:** `john.doe1`

### Step 3: Create third user with same name
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase \
  -H "Content-Type: application/json" \
  -d '{"firebase_uid": "john-doe-3"}'
```
**Expected username:** `john.doe2`

---

## Test Scenario 5: Single Name User

**Setup:**
- User name: "Ahmad" (hanya 1 kata)
- Firebase UID: `ahmad-123`

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_uid": "ahmad-123"
  }'
```

**Expected Result:**
- Username: `ahmad` (tidak ada titik karena hanya 1 kata)

**Expected Response:**
```json
{
  "data": {
    "profile": {
      "full_name": "Ahmad",
      "username": "ahmad",
      "phone": null
    }
  }
}
```

---

## Test Scenario 6: Three Word Name

**Setup:**
- User name: "John Doe Smith" (3 kata)
- Firebase UID: `john-smith-123`

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_uid": "john-smith-123"
  }'
```

**Expected Result:**
- Username: `john.doe` (hanya ambil 2 kata pertama)

---

## Test Scenario 7: Check /me Endpoint

**Setup:**
- User sudah login dan punya token
- Profile sudah dibuat otomatis

**Request:**
```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer {token}"
```

**Expected Response:**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "User data retrieved"
  },
  "data": {
    "id": 17,
    "name": "Test User",
    "email": "testuser@example.com",
    "roles": ["user"],
    "profile": {
      "full_name": "Test User",
      "username": "test.user",
      "phone": null,
      "avatar": null,
      "birth_date": null,
      "gender": null
    },
    "token_expires_at": "2026-02-13T..."
  }
}
```

---

## Test Scenario 8: Integration with Update Phone

**Flow:**
1. Login → Profile auto-created
2. Update phone → Profile updated

**Step 1: Login**
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase \
  -H "Content-Type: application/json" \
  -d '{"firebase_uid": "test-uid-123"}'
```

**Step 2: Update Phone**
```bash
curl -X PUT http://localhost:8000/api/v1/user/update-phone \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token-from-step-1}" \
  -d '{"phone": "628123456789"}'
```

**Expected:**
- Profile phone updated dari `null` ke `628123456789`
- Username tetap sama
- Full name tetap sama

---

## Verification Queries

### Check if profile was created
```sql
SELECT u.id, u.name, u.firebase_uid, p.full_name, p.username, p.phone
FROM users u
LEFT JOIN profiles p ON u.id = p.user_id
WHERE u.firebase_uid = 'test-uid-123';
```

### Check username uniqueness
```sql
SELECT username, COUNT(*) as count
FROM profiles
GROUP BY username
HAVING count > 1;
```
**Expected:** No results (all usernames should be unique)

### Check users without profile
```sql
SELECT u.id, u.name, u.email
FROM users u
LEFT JOIN profiles p ON u.id = p.user_id
WHERE p.id IS NULL;
```
**Expected:** No results after login (all users should have profile)

---

## Error Cases

### Invalid Firebase UID
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase \
  -H "Content-Type: application/json" \
  -d '{"firebase_uid": "invalid-uid-xxx"}'
```

**Expected:**
- Error 500
- Message: "Login gagal: ..."

---

## Performance Test

**Scenario:** 100 concurrent logins

```bash
# Using Apache Bench
ab -n 100 -c 10 -p firebase_login.json -T application/json \
  http://localhost:8000/api/v1/auth/firebase
```

**Expected:**
- All requests successful
- No duplicate usernames
- All profiles created
- Response time < 500ms per request

---

## Summary Checklist

- [ ] New user → User + Profile created
- [ ] Existing user without profile → Profile created
- [ ] Existing user with profile → Profile not recreated
- [ ] Username unique and auto-generated
- [ ] Username collision handled (counter added)
- [ ] Single name handled correctly
- [ ] Multi-word name handled (max 2 words)
- [ ] /me endpoint returns profile
- [ ] Integration with update phone works
- [ ] No duplicate usernames in database
- [ ] All users have profile after login
