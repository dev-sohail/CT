"""
PostgreSQL Database Service for TirahAi
Replaces SQLite with PostgreSQL for production use
"""

import os
import psycopg2
from psycopg2.extras import RealDictCursor
from psycopg2.pool import ThreadedConnectionPool
from typing import Callable, Dict, Optional
from contextlib import contextmanager

# Connection pool
_pool: Optional[ThreadedConnectionPool] = None
_migrations: Dict[str, Dict[int, Callable]] = {}

def init_pool():
    """Initialize PostgreSQL connection pool"""
    global _pool
    if _pool is not None:
        return _pool
    
    db_config = {
        'host': os.getenv('POSTGRES_HOST', 'localhost'),
        'port': int(os.getenv('POSTGRES_PORT', 5432)),
        'database': os.getenv('POSTGRES_DB', 'tirah_ai'),
        'user': os.getenv('POSTGRES_USER', 'postgres'),
        'password': os.getenv('POSTGRES_PASSWORD', ''),
        'minconn': 1,
        'maxconn': 10
    }
    
    try:
        _pool = ThreadedConnectionPool(
            db_config['minconn'],
            db_config['maxconn'],
            **{k: v for k, v in db_config.items() if k not in ['minconn', 'maxconn']}
        )
        return _pool
    except Exception as e:
        raise ConnectionError(f"Failed to create PostgreSQL connection pool: {e}")

@contextmanager
def get_conn(db_name: str = 'main'):
    """Get a database connection from the pool"""
    pool = init_pool()
    conn = pool.getconn()
    try:
        conn.set_session(autocommit=False)
        yield conn
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        pool.putconn(conn)

def get_db_version(conn) -> int:
    """Get current database schema version"""
    try:
        with conn.cursor() as cur:
            cur.execute("""
                CREATE TABLE IF NOT EXISTS __meta (
                    k VARCHAR(255) PRIMARY KEY,
                    v INTEGER
                )
            """)
            cur.execute("SELECT v FROM __meta WHERE k='schema_version'")
            row = cur.fetchone()
            return int(row[0]) if row else 0
    except Exception as e:
        print(f"Error getting DB version: {e}")
        return 0

def set_db_version(conn, version: int):
    """Set database schema version"""
    try:
        with conn.cursor() as cur:
            cur.execute(
                "INSERT INTO __meta (k, v) VALUES ('schema_version', %s) "
                "ON CONFLICT (k) DO UPDATE SET v = %s",
                (version, version)
            )
            conn.commit()
    except Exception as e:
        print(f"Error setting DB version: {e}")

def register_migration(db_name: str, version: int, fn: Callable):
    """Register a migration function"""
    _migrations.setdefault(db_name, {})[version] = fn

def run_migrations(db_name: str, conn):
    """Run pending migrations"""
    migs = _migrations.get(db_name, {})
    if not migs:
        return
    
    current = get_db_version(conn)
    for version in sorted(migs.keys()):
        if version > current:
            try:
                migs[version](conn)
                set_db_version(conn, version)
                conn.commit()
            except Exception as e:
                print(f"Migration {version} failed: {e}")
                conn.rollback()
                break

# Default migrations
def _migrate_tasks_v1(conn):
    """Create tasks table"""
    with conn.cursor() as cur:
        cur.execute("""
            CREATE TABLE IF NOT EXISTS tasks (
                id SERIAL PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                due_date TIMESTAMP,
                completed BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

def _migrate_journal_v1(conn):
    """Create journal entries table"""
    with conn.cursor() as cur:
        cur.execute("""
            CREATE TABLE IF NOT EXISTS entries (
                id SERIAL PRIMARY KEY,
                content TEXT NOT NULL,
                sentiment VARCHAR(50),
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

def _migrate_pomodoro_v1(conn):
    """Create pomodoro sessions table"""
    with conn.cursor() as cur:
        cur.execute("""
            CREATE TABLE IF NOT EXISTS pomodoro_sessions (
                id SERIAL PRIMARY KEY,
                session_type VARCHAR(50),
                duration INTEGER,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

# Register migrations
register_migration("tasks", 1, _migrate_tasks_v1)
register_migration("journal", 1, _migrate_journal_v1)
register_migration("pomodoro", 1, _migrate_pomodoro_v1)

# Initialize on import
try:
    init_pool()
except Exception as e:
    print(f"Warning: PostgreSQL not available: {e}")


