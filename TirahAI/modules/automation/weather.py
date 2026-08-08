"""
Weather Module
Fetch and display weather information
"""

import logging
from typing import Optional, Dict, Any
import requests
from config.settings import WEATHER_API_KEY
from utils.logger import get_logger

logger = get_logger(__name__)


class WeatherService:
    """Weather service for fetching weather data."""

    def __init__(self):
        """Initialize weather service."""
        self.api_key = WEATHER_API_KEY
        self.base_url = "http://api.openweathermap.org/data/2.5/weather"
        self.forecast_url = "http://api.openweathermap.org/data/2.5/forecast"

    def is_configured(self) -> bool:
        """
        Check if weather API is configured.

        Returns:
            bool: True if configured
        """
        return bool(self.api_key and self.api_key != "<your_weather_api_key>")

    def fetch_weather(
        self,
        city: str,
        units: str = 'metric'
    ) -> Optional[Dict[str, Any]]:
        """
        Fetch current weather for a city.

        Args:
            city (str): City name
            units (str): Units ('metric' for Celsius, 'imperial' for Fahrenheit)

        Returns:
            Optional[Dict[str, Any]]: Weather data or None
        """
        if not self.is_configured():
            logger.warning("Weather API key not configured")
            return None

        try:
            params = {
                'q': city,
                'appid': self.api_key,
                'units': units
            }

            response = requests.get(
                self.base_url,
                params=params,
                timeout=10
            )
            response.raise_for_status()
            data = response.json()

            if data.get('cod') != 200:
                logger.error(f"Weather API error: {data.get('message')}")
                return None

            return data

        except requests.exceptions.RequestException as e:
            logger.error(f"Error fetching weather: {e}")
            return None
        except Exception as e:
            logger.error(f"Unexpected error: {e}")
            return None

    def format_weather_data(
        self,
        data: Dict[str, Any]
    ) -> str:
        """
        Format weather data for display.

        Args:
            data (Dict[str, Any]): Raw weather data

        Returns:
            str: Formatted weather information
        """
        try:
            city_name = data['name']
            temp = data['main']['temp']
            desc = data['weather'][0]['description']
            humidity = data['main']['humidity']
            wind_speed = data['wind']['speed']
            
            return f"Weather in {city_name}: {temp}°C, {desc}. Humidity: {humidity}%, Wind: {wind_speed} m/s"
        except KeyError as e:
            logger.error(f"Error formatting weather data: {e}")
            return "Error formatting weather data"
