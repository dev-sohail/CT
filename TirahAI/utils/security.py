"""
Security Utilities for TirahAi
Encryption, injection detection, path validation, input sanitization, rate limiting, audit logging
"""

import os
import re
import json
import time
import hashlib
from datetime import datetime, timedelta
from typing import Optional, List, Dict, Callable
from cryptography.fernet import Fernet
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2HMAC
from cryptography.hazmat.backends import default_backend
from passlib.hash import bcrypt
import base64

SANITIZE_ALLOW = re.compile(r'^[a-zA-Z0-9 \.\-\_@\/\:\(\)\[\]\{\}\+\=\%\#\!\?\~\`\|\;\:\'\"\,]+$')
SHELL_METACHARS = re.compile(r'[;&|`$(){}\[\]<>*?~!#\\\n\r]')
SQL_PATTERNS = re.compile(
    r"(\b(union|select|insert|update|delete|drop|alter|create|truncate|exec|execute|--|#)\b)",
    re.IGNORECASE
)
PATH_TRAVERSAL = re.compile(r'(\.\.[/\\])|~[/\\]|^/|^[a-zA-Z]:[/\\]')
COMMAND_INJECTION = re.compile(
    r'(\b(rm|wget|curl|chmod|chown|mkfs|dd|format|shutdown|reboot|sudo|su)\b'
    r'|`[^`]+`'
    r'|\$\s*\('
    r'|\|.*\|)',
    re.IGNORECASE
)


class SecurityManager:
    def __init__(self, key: Optional[bytes] = None):
        self.key = key or self._generate_key()
        self.cipher = Fernet(self.key)

    @staticmethod
    def _generate_key() -> bytes:
        return Fernet.generate_key()

    @staticmethod
    def derive_key_from_password(password: str, salt: Optional[bytes] = None) -> tuple:
        if salt is None:
            salt = os.urandom(16)
        kdf = PBKDF2HMAC(
            algorithm=hashes.SHA256(),
            length=32,
            salt=salt,
            iterations=100000,
            backend=default_backend()
        )
        key = base64.urlsafe_b64encode(kdf.derive(password.encode()))
        return key, salt

    def encrypt(self, data: str) -> str:
        try:
            return self.cipher.encrypt(data.encode()).decode()
        except Exception as e:
            raise ValueError(f"Encryption failed: {e}")

    def decrypt(self, encrypted_data: str) -> str:
        try:
            return self.cipher.decrypt(encrypted_data.encode()).decode()
        except Exception as e:
            raise ValueError(f"Decryption failed: {e}")

    def save_key(self, filepath: str) -> bool:
        try:
            with open(filepath, 'wb') as f:
                f.write(self.key)
            return True
        except Exception as e:
            print(f"Failed to save key: {e}")
            return False

    @staticmethod
    def load_key(filepath: str) -> bytes:
        with open(filepath, 'rb') as f:
            return f.read()

    @staticmethod
    def rotate_key(old_key: bytes) -> tuple:
        new_key = Fernet.generate_key()
        return new_key, old_key


def encrypt_data(data: str, key: Optional[bytes] = None) -> str:
    return SecurityManager(key).encrypt(data)


def decrypt_data(encrypted_data: str, key: Optional[bytes] = None) -> str:
    return SecurityManager(key).decrypt(encrypted_data)


def hash_password(password: str) -> str:
    return bcrypt.hash(password)


def verify_password(password: str, hashed: str) -> bool:
    return bcrypt.verify(password, hashed)


def sanitize_input(user_input: str, strict: bool = False) -> str:
    if not isinstance(user_input, str):
        return ""
    s = user_input.replace("\0", "")
    if strict:
        s = SANITIZE_ALLOW.sub('', s)
        s = re.sub(r'[^\w\s\.\-@\/]', '', s)
    s = s.strip()
    return s[:10000]


def detect_injection(input_str: str) -> Optional[Dict]:
    if not isinstance(input_str, str):
        return None
    issues = []
    if SHELL_METACHARS.search(input_str):
        issues.append({"type": "shell_metachar", "severity": "high",
                       "match": SHELL_METACHARS.findall(input_str)})
    if SQL_PATTERNS.search(input_str):
        issues.append({"type": "sql_keyword", "severity": "medium",
                       "match": SQL_PATTERNS.findall(input_str)})
    if COMMAND_INJECTION.search(input_str):
        issues.append({"type": "command_injection", "severity": "critical",
                       "match": COMMAND_INJECTION.findall(input_str)})
    if len(input_str) > 5000:
        issues.append({"type": "oversized_input", "severity": "low",
                       "size": len(input_str)})
    return {"safe": len(issues) == 0, "issues": issues} if issues else None


def validate_file_path(filepath: str, allowed_dirs: Optional[List[str]] = None) -> bool:
    if not filepath or not isinstance(filepath, str):
        return False
    if PATH_TRAVERSAL.search(filepath):
        return False
    try:
        abs_path = os.path.abspath(os.path.normpath(filepath))
    except Exception:
        return False
    if allowed_dirs:
        for d in allowed_dirs:
            try:
                allowed_abs = os.path.abspath(os.path.normpath(d))
                if abs_path.startswith(allowed_abs + os.sep) or abs_path == allowed_abs:
                    return True
            except Exception:
                continue
        return False
    return True


class RateLimiter:
    def __init__(self, max_requests: int = 60, window_seconds: int = 60):
        self.max_requests = max_requests
        self.window = window_seconds
        self._buckets: Dict[str, List[float]] = {}

    def check(self, key: str) -> Dict:
        now = time.time()
        cutoff = now - self.window
        if key not in self._buckets:
            self._buckets[key] = []
        self._buckets[key] = [t for t in self._buckets[key] if t > cutoff]
        if len(self._buckets[key]) >= self.max_requests:
            retry_after = int(self._buckets[key][0] + self.window - now)
            return {"allowed": False, "retry_after": max(1, retry_after), "remaining": 0}
        self._buckets[key].append(now)
        return {"allowed": True, "remaining": self.max_requests - len(self._buckets[key]), "retry_after": 0}

    def get_remaining(self, key: str) -> int:
        if key not in self._buckets:
            return self.max_requests
        now = time.time()
        cutoff = now - self.window
        active = sum(1 for t in self._buckets[key] if t > cutoff)
        return max(0, self.max_requests - active)


class AuditLogger:
    def __init__(self, log_dir: Optional[str] = None):
        self.log_dir = log_dir or os.environ.get("AUDIT_LOG_DIR", "logs/audit")
        os.makedirs(self.log_dir, exist_ok=True)

    def log(self, event: str, user: Optional[str] = None, details: Optional[Dict] = None):
        entry = {
            "timestamp": datetime.utcnow().isoformat(),
            "event": event,
            "user": user or "anonymous",
            "details": details or {},
        }
        log_file = os.path.join(self.log_dir, f"audit_{datetime.utcnow().strftime('%Y%m%d')}.jsonl")
        try:
            with open(log_file, "a") as f:
                f.write(json.dumps(entry) + "\n")
        except Exception:
            pass

    def query(self, event: Optional[str] = None, user: Optional[str] = None,
              since: Optional[datetime] = None, limit: int = 100) -> List[Dict]:
        results = []
        log_dir = Path(self.log_dir) if 'Path' in dir() else Path(self.log_dir)
        for f in sorted(Path(self.log_dir).glob("audit_*.jsonl"), reverse=True):
            if len(results) >= limit:
                break
            try:
                with open(f) as fh:
                    for line in fh:
                        entry = json.loads(line.strip())
                        if event and entry.get("event") != event:
                            continue
                        if user and entry.get("user") != user:
                            continue
                        if since and datetime.fromisoformat(entry["timestamp"]) < since:
                            continue
                        results.append(entry)
                        if len(results) >= limit:
                            break
            except Exception:
                continue
        return results


class JWTManager:
    def __init__(self, secret: Optional[str] = None, algorithm: str = "HS256"):
        self.secret = secret or os.getenv("JWT_SECRET", "change-this-secret-in-production")
        self.algorithm = algorithm

    def create_token(self, payload: Dict, expiry_hours: int = 24) -> str:
        import jwt as pyjwt
        data = payload.copy()
        data["iat"] = int(time.time())
        data["exp"] = int(time.time()) + expiry_hours * 3600
        data["jti"] = hashlib.md5(f"{time.time()}{os.urandom(8)}".encode()).hexdigest()
        return pyjwt.encode(data, self.secret, algorithm=self.algorithm)

    def verify_token(self, token: str) -> Optional[Dict]:
        import jwt as pyjwt
        try:
            return pyjwt.decode(token, self.secret, algorithms=[self.algorithm])
        except pyjwt.ExpiredSignatureError:
            return None
        except pyjwt.InvalidTokenError:
            return None

    def refresh_token(self, token: str, expiry_hours: int = 24) -> Optional[str]:
        payload = self.verify_token(token)
        if not payload:
            return None
        payload.pop("exp", None)
        payload.pop("iat", None)
        return self.create_token(payload, expiry_hours)


rate_limiter = RateLimiter()
audit_logger = AuditLogger()
jwt_manager = JWTManager()


def secure_wrapper(func: Callable) -> Callable:
    def wrapper(*args, **kwargs):
        self = args[0] if args else None
        request = kwargs.get("request") or (getattr(self, "request", None) if self else None)
        user_id = kwargs.get("user_id") or (getattr(self, "user_id", None) if self else None)
        client_ip = "unknown"
        if request:
            client_ip = getattr(request, "client", {}).get("host", "unknown") if hasattr(request, "client") else "unknown"
        ratelimit = rate_limiter.check(client_ip)
        if not ratelimit["allowed"]:
            import fastapi
            raise fastapi.HTTPException(status_code=429, detail=f"Rate limit exceeded. Retry after {ratelimit['retry_after']}s")
        audit_logger.log("api_call", user=str(user_id), details={"endpoint": func.__name__, "ip": client_ip})
        return func(*args, **kwargs)
    return wrapper


def validate_api_key(key: str) -> bool:
    if not key or not isinstance(key, str):
        return False
    key = key.strip()
    prefixes = {"sk-": 20, "AIza": 20, "api_": 10}
    for prefix, min_len in prefixes.items():
        if key.startswith(prefix) and len(key) >= min_len:
            return True
    if len(key) >= 8:
        return True
    return False
