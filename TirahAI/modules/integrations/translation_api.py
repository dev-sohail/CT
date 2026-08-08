import logging
from typing import Optional, Dict, List

try:
    from deep_translator import GoogleTranslator
except ImportError:
    GoogleTranslator = None

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)


class TranslationService:
    def __init__(self):
        self._client = GoogleTranslator if GoogleTranslator else None

    def translate_text(self, text: str, src_lang: str = "auto", dest_lang: str = "en") -> Optional[str]:
        """
        Translate text from source language to destination language.
        Args:
            text (str): Text to be translated.
            src_lang (str): Source language code (default is auto detection).
            dest_lang (str): Target language code (default is English).
        Returns:
            str: Translated text.
        """
        if not self._client:
            logger.warning("deep-translator not installed")
            return None
        try:
            result = self._client(source=src_lang, target=dest_lang).translate(text)
            logger.info(f"Translated text: {result}")
            return result
        except Exception as e:
            logger.error(f"Error during translation: {str(e)}")
            return None

    def get_supported_languages(self) -> Optional[List[str]]:
        if not self._client:
            return []
        try:
            return list(self._client().get_supported_languages().values())
        except Exception as e:
            logger.error(f"Error getting supported languages: {str(e)}")
            return []

    def detect_language(self, text: str) -> Optional[str]:
        if not self._client:
            return None
        try:
            return self._client().detect(text)
        except Exception as e:
            logger.error(f"Error detecting language: {str(e)}")
            return None


TranslationAPI = TranslationService

if __name__ == "__main__":
    translation_api = TranslationAPI()
    text_to_translate = "Hola, ¿cómo estás?"
    translated_text = translation_api.translate_text(text_to_translate, dest_lang='en')
    print(f"Translated text: {translated_text}")
    print(f"Supported Languages: {translation_api.get_supported_languages()}")
