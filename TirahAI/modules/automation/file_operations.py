import os
import shutil
import logging
from datetime import datetime
from cryptography.fernet import Fernet
from pathlib import Path
import hashlib
import zipfile
import json
from typing import List
import requests

# Setting up logging
logging.basicConfig(
    filename='file_operations.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

class FileOperations:
    def __init__(self, directory: str, cloud_api_url: str = None):
        """
        Initialize with the directory to perform file operations
        :param directory: The base directory for file operations
        :param cloud_api_url: Optional cloud API URL for backup or syncing
        """
        self.directory = Path(directory)
        self.cloud_api_url = cloud_api_url

    def file_exists(self, filename: str) -> bool:
        """Check if a file exists in the directory"""
        file_path = self.directory / filename
        exists = file_path.exists()
        if exists:
            logging.info(f"File exists: {file_path}")
        else:
            logging.warning(f"File not found: {file_path}")
        return exists

    def read_file(self, filename: str) -> str:
        """Read the contents of a file"""
        if self.file_exists(filename):
            with open(self.directory / filename, 'r') as file:
                content = file.read()
            logging.info(f"File read successfully: {filename}")
            return content
        return None

    def write_to_file(self, filename: str, content: str, mode='w') -> None:
        """Write content to a file"""
        with open(self.directory / filename, mode) as file:
            file.write(content)
        logging.info(f"Content written to file: {filename}")

    def append_to_file(self, filename: str, content: str) -> None:
        """Append content to an existing file"""
        self.write_to_file(filename, content, mode='a')
        logging.info(f"Content appended to file: {filename}")

    def rename_file(self, old_filename: str, new_filename: str) -> bool:
        """Rename a file"""
        old_file = self.directory / old_filename
        new_file = self.directory / new_filename
        if old_file.exists():
            old_file.rename(new_file)
            logging.info(f"File renamed from {old_filename} to {new_filename}")
            return True
        logging.warning(f"Failed to rename. File {old_filename} not found.")
        return False

    def delete_file(self, filename: str) -> bool:
        """Delete a file"""
        file_path = self.directory / filename
        if file_path.exists():
            file_path.unlink()
            logging.info(f"File deleted: {filename}")
            return True
        logging.warning(f"Failed to delete. File {filename} not found.")
        return False

    def move_file(self, filename: str, destination: str) -> bool:
        """Move a file to a new directory"""
        file_path = self.directory / filename
        destination_path = Path(destination) / filename
        if file_path.exists():
            shutil.move(file_path, destination_path)
            logging.info(f"File moved from {file_path} to {destination_path}")
            return True
        logging.warning(f"Failed to move. File {filename} not found.")
        return False

    def copy_file(self, filename: str, destination: str) -> bool:
        """Copy a file to a new directory"""
        file_path = self.directory / filename
        destination_path = Path(destination) / filename
        if file_path.exists():
            shutil.copy(file_path, destination_path)
            logging.info(f"File copied from {file_path} to {destination_path}")
            return True
        logging.warning(f"Failed to copy. File {filename} not found.")
        return False

    def list_files(self, extension: str = None) -> list:
        """List all files in the directory, with an optional filter for file extension"""
        if extension:
            files = [f for f in self.directory.iterdir() if f.is_file() and f.suffix == extension]
        else:
            files = [f for f in self.directory.iterdir() if f.is_file()]
        files_list = [str(file) for file in files]
        logging.info(f"Files listed: {files_list}")
        return files_list

    def search_file(self, search_term: str) -> list:
        """Search for files containing a specific term"""
        files = self.list_files()
        matching_files = []
        for file in files:
            if search_term.lower() in self.read_file(file).lower():
                matching_files.append(file)
        logging.info(f"Search term '{search_term}' found in: {matching_files}")
        return matching_files

    def create_directory(self, dir_name: str) -> bool:
        """Create a new directory"""
        dir_path = self.directory / dir_name
        if not dir_path.exists():
            dir_path.mkdir(parents=True)
            logging.info(f"Directory created: {dir_path}")
            return True
        logging.warning(f"Directory already exists: {dir_path}")
        return False

    def delete_directory(self, dir_name: str) -> bool:
        """Delete a directory"""
        dir_path = self.directory / dir_name
        if dir_path.exists() and dir_path.is_dir():
            shutil.rmtree(dir_path)
            logging.info(f"Directory deleted: {dir_path}")
            return True
        logging.warning(f"Failed to delete. Directory {dir_name} not found.")
        return False

    def encrypt_file(self, filename: str, key: str) -> bool:
        """Encrypt a file using a symmetric key"""
        file_path = self.directory / filename
        if self.file_exists(filename):
            cipher = Fernet(key)
            with open(file_path, 'rb') as file:
                file_data = file.read()
            encrypted_data = cipher.encrypt(file_data)
            encrypted_file = file_path.with_suffix('.enc')
            with open(encrypted_file, 'wb') as file:
                file.write(encrypted_data)
            logging.info(f"File encrypted: {filename}")
            return True
        return False

    def decrypt_file(self, filename: str, key: str) -> bool:
        """Decrypt a previously encrypted file"""
        file_path = self.directory / filename
        if self.file_exists(filename) and filename.endswith('.enc'):
            cipher = Fernet(key)
            with open(file_path, 'rb') as file:
                encrypted_data = file.read()
            decrypted_data = cipher.decrypt(encrypted_data)
            decrypted_file = file_path.with_suffix('')
            with open(decrypted_file, 'wb') as file:
                file.write(decrypted_data)
            logging.info(f"File decrypted: {filename}")
            return True
        logging.warning(f"File decryption failed. File not found or incorrect extension.")
        return False

    def backup_files(self, backup_dir: str) -> bool:
        """Backup all files to a specific directory"""
        backup_path = Path(backup_dir)
        if not backup_path.exists():
            backup_path.mkdir(parents=True)
        files = self.list_files()
        for file in files:
            shutil.copy(file, backup_path)
        logging.info(f"Files backed up to {backup_dir}")
        return True

    def compress_files(self, zip_filename: str, file_list: List[str]) -> bool:
        """Compress a list of files into a zip archive"""
        zip_path = self.directory / zip_filename
        with zipfile.ZipFile(zip_path, 'w') as zipf:
            for file in file_list:
                file_path = self.directory / file
                if file_path.exists():
                    zipf.write(file_path, arcname=file)
                    logging.info(f"File {file} added to archive")
                else:
                    logging.warning(f"File {file} not found for compression.")
        return True

    def extract_zip(self, zip_filename: str, extract_dir: str) -> bool:
        """Extract files from a zip archive"""
        zip_path = self.directory / zip_filename
        extract_path = self.directory / extract_dir
        if zip_path.exists():
            with zipfile.ZipFile(zip_path, 'r') as zipf:
                zipf.extractall(extract_path)
            logging.info(f"Files extracted from {zip_filename} to {extract_dir}")
            return True
        logging.warning(f"Zip file {zip_filename} not found.")
        return False

    def file_integrity_check(self, filename: str, checksum: str) -> bool:
        """Check the integrity of a file by comparing its checksum"""
        file_path = self.directory / filename
        if file_path.exists():
            sha256_hash = hashlib.sha256()
            with open(file_path, 'rb') as file:
                while chunk := file.read(8192):
                    sha256_hash.update(chunk)
            calculated_checksum = sha256_hash.hexdigest()
            if calculated_checksum == checksum:
                logging.info(f"File integrity check passed for {filename}")
                return True
            else:
                logging.warning(f"File integrity check failed for {filename}")
        else:
            logging.warning(f"File {filename} not found for integrity check.")
        return False

    def sync_to_cloud(self, filename: str) -> bool:
        """Sync a file to the cloud storage (Example using API)"""
        if self.cloud_api_url:
            file_path = self.directory / filename
            if file_path.exists():
                with open(file_path, 'rb') as file:
                    file_data = file.read()
                response = requests.post(self.cloud_api_url, files={filename: file_data})
                if response.status_code == 200:
                    logging.info(f"File {filename} successfully synced to cloud.")
                    return True
                else:
                    logging.warning(f"Failed to sync {filename} to cloud. Status code: {response.status_code}")
            else:
                logging.warning(f"File {filename} not found for cloud sync.")
        else:
            logging.warning("Cloud API URL not provided for sync.")
        return False


# Example usage:
if __name__ == "__main__":
    # Initialize with the base directory where files reside and cloud API URL (if applicable)
    file_ops = FileOperations('/path/to/your/directory', cloud_api_url="https://your-cloud-api.com/upload")

    # Example operations
    file_ops.write_to_file('example.txt', 'This is a test file.')
    file_ops.encrypt_file('example.txt', Fernet.generate_key())
    file_ops.backup_files('/path/to/backup/directory')
    file_ops.compress_files('archive.zip', ['example.txt'])
    file_ops.extract_zip('archive.zip', 'extracted_files')

