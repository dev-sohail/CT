"""
OpenAI API Integration Module
Wrapper for OpenAI API interactions using openai>=1.0 SDK
"""

from typing import Optional, Dict, List, Any

from config.settings import OPENAI_API_KEY
from utils.logger import get_logger

try:
    from openai import OpenAI
except ImportError:
    OpenAI = None

logger = get_logger(__name__)


class OpenAIAPI:
    """OpenAI API integration wrapper."""

    def __init__(self, api_key: Optional[str] = None):
        self.api_key = api_key or OPENAI_API_KEY
        self.client = None

        if not self.api_key or self.api_key in ("<your_llm_api_key>", ""):
            logger.warning("OpenAI API key not configured")
        elif OpenAI is None:
            logger.warning("openai package not installed")
        else:
            try:
                self.client = OpenAI(api_key=self.api_key)
                logger.info("OpenAI API client initialized")
            except Exception as e:
                logger.error(f"Failed to initialize OpenAI client: {e}")

        self.model = "gpt-3.5-turbo"

    def is_configured(self) -> bool:
        return bool(self.client and self.api_key)

    def ask(
        self,
        prompt: str,
        temperature: float = 0.7,
        max_tokens: int = 150
    ) -> Optional[str]:
        if not self.is_configured():
            logger.warning("OpenAI API not configured")
            return None

        try:
            logger.info(f"Sending prompt to OpenAI: {prompt[:50]}...")
            response = self.client.chat.completions.create(
                model=self.model,
                messages=[{"role": "user", "content": prompt}],
                temperature=temperature,
                max_tokens=max_tokens,
            )
            text = response.choices[0].message.content.strip()
            logger.info("Received response from OpenAI")
            return text
        except Exception as e:
            logger.error(f"OpenAI API error: {e}")
            return None

    def chat(
        self,
        messages: List[Dict[str, str]],
        temperature: float = 0.7,
        max_tokens: int = 500
    ) -> Optional[str]:
        if not self.is_configured():
            logger.warning("OpenAI API not configured")
            return None

        try:
            response = self.client.chat.completions.create(
                model=self.model,
                messages=messages,
                temperature=temperature,
                max_tokens=max_tokens,
            )
            return response.choices[0].message.content.strip()
        except Exception as e:
            logger.error(f"Error in chat: {e}")
            return None

    def get_models(self) -> Optional[List[str]]:
        if not self.is_configured():
            return None

        try:
            models = self.client.models.list()
            return [m.id for m in models.data]
        except Exception as e:
            logger.error(f"Error fetching models: {e}")
            return None


class OpenAIWrapper(OpenAIAPI):
    """Alias for backwards compatibility."""
    pass
