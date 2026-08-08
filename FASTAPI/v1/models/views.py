from sqlalchemy import Column, Integer, String, DateTime, func, text
from sqlalchemy.sql import text as sql_text
from .base import Base


class DashboardView(Base):
    __tablename__ = "dashboard_stats_view"
    __table_args__ = {"sqlite_view": True}
    __view__ = True

    id = Column("id", Integer, primary_key=True)
    total_users = Column("total_users", Integer)
    active_users = Column("active_users", Integer)
    total_interactions = Column("total_interactions", Integer)
    today_interactions = Column("today_interactions", Integer)
    last_updated = Column("last_updated", DateTime, server_default=func.now())

    @classmethod
    def refresh_sql(cls):
        return sql_text(f"""
            CREATE VIEW IF NOT EXISTS {cls.__tablename__} AS
            SELECT 
                1 as id,
                COALESCE((SELECT COUNT(*) FROM users), 0) as total_users,
                COALESCE((SELECT COUNT(*) FROM users WHERE is_active = true), 0) as active_users,
                COALESCE((SELECT COUNT(*) FROM audit_logs), 0) as total_interactions,
                COALESCE((SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = DATE('now')), 0) as today_interactions,
                CURRENT_TIMESTAMP as last_updated
        """)


class SystemStatsView(Base):
    __tablename__ = "system_stats_view"
    __table_args__ = {"sqlite_view": True}
    __view__ = True

    id = Column("id", Integer, primary_key=True)
    cpu_usage_percent = Column("cpu_usage_percent", String(50))
    memory_usage_percent = Column("memory_usage_percent", String(50))
    disk_usage_percent = Column("disk_usage_percent", String(50))
    active_connections = Column("active_connections", Integer)
    cache_hit_rate = Column("cache_hit_rate", String(50))
    last_updated = Column("last_updated", DateTime, server_default=func.now())


class UserActivityView(Base):
    __tablename__ = "user_activity_view"
    __table_args__ = {"sqlite_view": True}
    __view__ = True

    id = Column("id", Integer, primary_key=True)
    user_id = Column("user_id", Integer)
    email = Column("email", String(255))
    last_action = Column("last_action", String(255))
    last_action_time = Column("last_action_time", DateTime)
    session_count = Column("session_count", Integer)
    total_requests = Column("total_requests", Integer)