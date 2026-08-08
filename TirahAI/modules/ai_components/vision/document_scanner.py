import pytesseract
from PIL import Image

class DocumentScanner:
    def __init__(self):
        pytesseract.pytesseract.tesseract_cmd = r"C:\Program Files\Tesseract-OCR\tesseract.exe"

    def scan(self, image_path):
        img = Image.open(image_path)
        text = pytesseract.image_to_string(img)
        return text
