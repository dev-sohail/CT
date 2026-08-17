"""
Logging Utilities for TirahAi
Centralized logging configuration and utilities
"""

import logging
import logging.handlers
from pathlib import Path
from config.settings import LOGGING_CONFIG, LOGS_DIR


def setup_logger(name: str = 'TirahAi', log_file: str = None) -> logging.Logger:
    """
    Set up and configure a logger with file and console handlers.
    
    Args:
        name: Name of the logger
        log_file: Optional custom log file path
        
    Returns:
        Configured logger instance
    """
    logger = logging.getLogger(name)
    logger.setLevel(getattr(logging, LOGGING_CONFIG['level']))
    
    # Avoid adding handlers multiple times
    if logger.handlers:
        return logger
    
    # Console Handler
    console_handler = logging.StreamHandler()
    console_handler.setLevel(logging.DEBUG)
    console_formatter = logging.Formatter('%(levelname)s - %(name)s - %(message)s')
    console_handler.setFormatter(console_formatter)
    
    # File Handler with Rotation
    if log_file is None:
        log_file = LOGGING_CONFIG['file']
    
    # Ensure log directory exists
    Path(log_file).parent.mkdir(parents=True, exist_ok=True)
    
    file_handler = logging.handlers.RotatingFileHandler(
        log_file,
        maxBytes=LOGGING_CONFIG['max_bytes'],
        backupCount=LOGGING_CONFIG['backup_count']
    )
    file_handler.setLevel(getattr(logging, LOGGING_CONFIG['level']))
    if LOGGING_CONFIG.get('json'):
        file_formatter = logging.Formatter('{"time":"%(asctime)s","name":"%(name)s","level":"%(levelname)s","message":"%(message)s"}')
    else:
        file_formatter = logging.Formatter(LOGGING_CONFIG['format'])
    file_handler.setFormatter(file_formatter)
    
    # Add handlers
    logger.addHandler(console_handler)
    logger.addHandler(file_handler)
    
    return logger


def get_logger(name: str) -> logging.Logger:
    """
    Get or create a logger with the given name.
    
    Args:
        name: Name of the logger
        
    Returns:
        Logger instance
    """
    return logging.getLogger(name)


# Initialize main logger
main_logger = setup_logger()

