
import os
import shutil
from pathlib import Path
import logging

# Setup logger
logger = logging.getLogger(__name__)

class FileOrganizer:
    """
    Module to organize files in a directory based on their extensions.
    """
    
    FILE_CATEGORIES = {
        "Images": [".jpg", ".jpeg", ".png", ".gif", ".bmp", ".svg", ".tiff", ".webp"],
        "Documents": [".pdf", ".doc", ".docx", ".txt", ".rtf", ".odt", ".xls", ".xlsx", ".ppt", ".pptx", ".csv", ".md"],
        "Audio": [".mp3", ".wav", ".aac", ".flac", ".ogg", ".m4a"],
        "Video": [".mp4", ".avi", ".mkv", ".mov", ".wmv", ".flv", ".webm"],
        "Archives": [".zip", ".rar", ".7z", ".tar", ".gz"],
        "Code": [".py", ".js", ".html", ".css", ".java", ".cpp", ".c", ".php", ".json", ".xml", ".sql", ".sh", ".bat"],
        "Executables": [".exe", ".msi", ".apk", ".app"],
        "Fonts": [".ttf", ".otf", ".woff", ".woff2"]
    }

    def __init__(self):
        self.dry_run = False

    def organize_directory(self, directory_path: str) -> str:
        """
        Organizes files in the specified directory into subfolders based on file type.
        
        Args:
            directory_path (str): The absolute path to the directory to organize.
            
        Returns:
            str: A summary message of the operation.
        """
        path = Path(directory_path)
        
        if not path.exists():
            return f"Error: Directory '{directory_path}' does not exist."
        
        if not path.is_dir():
            return f"Error: '{directory_path}' is not a directory."

        moved_count = 0
        errors = []

        try:
            # Iterate over all files in the directory
            for file_path in path.iterdir():
                if file_path.is_file():
                    # Skip hidden files and the script itself if present
                    if file_path.name.startswith('.'):
                        continue
                        
                    file_ext = file_path.suffix.lower()
                    destination_folder = "Others" # Default folder
                    
                    # Determine category
                    for category, extensions in self.FILE_CATEGORIES.items():
                        if file_ext in extensions:
                            destination_folder = category
                            break
                    
                    # Create category folder if it doesn't exist
                    target_dir = path / destination_folder
                    target_dir.mkdir(exist_ok=True)
                    
                    # Move file
                    try:
                        target_path = target_dir / file_path.name
                        # Handle duplicate names
                        if target_path.exists():
                            base = target_path.stem
                            suffix = target_path.suffix
                            counter = 1
                            while target_path.exists():
                                target_path = target_dir / f"{base}_{counter}{suffix}"
                                counter += 1
                                
                        if self.dry_run or os.environ.get("FILE_ORGANIZER_DRY_RUN") == "1":
                            pass
                        else:
                            shutil.move(str(file_path), str(target_path))
                        moved_count += 1
                    except Exception as e:
                        errors.append(f"Failed to move {file_path.name}: {str(e)}")
                        
            summary = f"Organization Complete. Moved {moved_count} files."
            if errors:
                summary += f"\nEncountered {len(errors)} errors."
                
            logger.info(summary)
            return summary

        except Exception as e:
            return f"Critical Error during organization: {str(e)}"
