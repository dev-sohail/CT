import sqlite3
from pathlib import Path
from typing import Callable, Dict

from config.settings import DATA_DIR

_conns: Dict[str, sqlite3.Connection] = {}
_migrations: Dict[str, Dict[int, Callable[[sqlite3.Connection], None]]] = {}

def get_conn(name: str) -> sqlite3.Connection:
    path = Path(DATA_DIR) / f"{name}.db"
    key = str(path)
    conn = _conns.get(key)
    if conn is None:
        conn = sqlite3.connect(path, check_same_thread=False)
        conn.row_factory = sqlite3.Row
        try:
            conn.execute("PRAGMA journal_mode=WAL")
        except:
            pass
        _conns[key] = conn
        run_migrations(name, conn)
    return conn

def register_migration(db_name: str, version: int, fn: Callable[[sqlite3.Connection], None]):
    _migrations.setdefault(db_name, {})[version] = fn

def get_db_version(conn: sqlite3.Connection) -> int:
    try:
        conn.execute("CREATE TABLE IF NOT EXISTS __meta (k TEXT PRIMARY KEY, v INTEGER)")
        cur = conn.execute("SELECT v FROM __meta WHERE k='schema_version'")
        row = cur.fetchone()
        return int(row[0]) if row else 0
    except:
        return 0

def set_db_version(conn: sqlite3.Connection, v: int):
    try:
        conn.execute("INSERT OR REPLACE INTO __meta (k, v) VALUES ('schema_version', ?)", (int(v),))
        conn.commit()
    except:
        pass

def run_migrations(db_name: str, conn: sqlite3.Connection):
    migs = _migrations.get(db_name, {})
    if not migs:
        return
    current = get_db_version(conn)
    for version in sorted(migs.keys()):
        if version > current:
            try:
                migs[version](conn)
                set_db_version(conn, version)
            except:
                break

# default migrations
def _migrate_tasks_v1(conn: sqlite3.Connection):
    conn.execute(
        """
        CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            due_date TEXT,
            completed INTEGER DEFAULT 0
        )
        """
    )
    conn.commit()

def _migrate_journal_v1(conn: sqlite3.Connection):
    conn.execute(
        """
        CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content TEXT NOT NULL,
            timestamp TEXT DEFAULT CURRENT_TIMESTAMP
        )
        """
    )
    conn.commit()

register_migration("tasks", 1, _migrate_tasks_v1)
register_migration("journal", 1, _migrate_journal_v1)
