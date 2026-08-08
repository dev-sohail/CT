import os
import shutil

def cleanup_pycache():
    print("Cleaning up __pycache__ directories...")
    count = 0
    for root, dirs, files in os.walk("."):
        if "__pycache__" in dirs:
            pycache_path = os.path.join(root, "__pycache__")
            try:
                shutil.rmtree(pycache_path)
                print(f"Deleted: {pycache_path}")
                count += 1
            except Exception as e:
                print(f"Error deleting {pycache_path}: {e}")
    
    print(f"Cleanup complete. Removed {count} directories.")

if __name__ == "__main__":
    cleanup_pycache()
