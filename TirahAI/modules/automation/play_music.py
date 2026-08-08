"""
Music playback assistant for TirahAi.
Note: Heavy optional dependencies (spotipy, pygame, apscheduler) are
imported lazily so missing packages do not break module import.
"""

import os
import logging
from typing import Optional

from utils.logger import get_logger

logger = get_logger(__name__)


def _get_recognizer():
    try:
        import speech_recognition as sr
        return sr.Recognizer()
    except Exception as e:
        logger.warning(f"Speech recognition unavailable: {e}")
        return None


def _get_tts_engine():
    try:
        import pyttsx3
        engine = pyttsx3.init()
        return engine
    except Exception as e:
        logger.warning(f"TTS engine unavailable: {e}")
        return None


class MusicAssistant:
    def __init__(self):
        self.recognizer = _get_recognizer()
        self.engine = _get_tts_engine()

    def speak(self, text: str) -> None:
        if not self.engine:
            logger.info(f"TTS fallback: {text}")
            return
        try:
            self.engine.say(text)
            self.engine.runAndWait()
        except Exception as e:
            logger.error(f"TTS error: {e}")

    def play_local_music(self, song_name: str) -> str:
        try:
            import pygame

            music_dir = os.environ.get("TIRAH_MUSIC_DIR", "")
            if not music_dir:
                return "Music directory not configured."

            song_path = os.path.join(music_dir, song_name + ".mp3")
            if not os.path.exists(song_path):
                return f"Song not found: {song_name}"

            if not pygame.mixer.get_init():
                pygame.mixer.init()
            pygame.mixer.music.load(song_path)
            pygame.mixer.music.play()
            self.speak(f"Playing {song_name}")
            return f"Playing {song_name}"
        except Exception as e:
            logger.error(f"Local music error: {e}")
            return f"Failed to play music: {e}"

    def play_youtube_music(self, query: str) -> str:
        try:
            import yt_dlp as ytdl

            self.speak("Searching for the song on YouTube.")
            ydl_opts = {
                "format": "bestaudio/best",
                "noplaylist": True,
                "quiet": True,
                "outtmpl": "downloads/%(id)s.%(ext)s",
            }
            with ytdl.YoutubeDL(ydl_opts) as ydl:
                info = ydl.extract_info(f"ytsearch:{query}", download=False)
                url = info["entries"][0]["url"]
                self.speak(f"Playing {query} from YouTube.")
                return f"Playing {query} from YouTube: {url}"
        except Exception as e:
            logger.error(f"YouTube music error: {e}")
            return f"Failed to play music from YouTube: {e}"

    def play_spotify_music(self, song_name: str) -> str:
        try:
            import spotipy
            from spotipy.oauth2 import SpotifyOAuth

            client_id = os.environ.get("SPOTIFY_CLIENT_ID", "")
            client_secret = os.environ.get("SPOTIFY_CLIENT_SECRET", "")
            if not client_id or not client_secret:
                return "Spotify credentials not configured."

            sp = spotipy.Spotify(auth_manager=SpotifyOAuth(
                client_id=client_id,
                client_secret=client_secret,
                redirect_uri="http://localhost:8888/callback",
                scope=["user-library-read", "playlist-read-private"],
            ))
            results = sp.search(q=song_name, limit=1, type="track")
            if results["tracks"]["items"]:
                track = results["tracks"]["items"][0]
                track_url = track["external_urls"]["spotify"]
                self.speak(f"Playing {track['name']} from Spotify.")
                return f"Playing {track['name']} from Spotify: {track_url}"
            return "Song not found on Spotify."
        except Exception as e:
            logger.error(f"Spotify music error: {e}")
            return f"Failed to play music from Spotify: {e}"

    def handle_play_music(self, command: str) -> str:
        if "play" not in command.lower():
            return "Please say 'play <song>' to play music."

        song_name = command.lower().replace("play", "").strip()
        if "youtube" in command.lower():
            return self.play_youtube_music(song_name)
        if "spotify" in command.lower():
            return self.play_spotify_music(song_name)
        return self.play_local_music(song_name)

    def listen_for_command(self) -> Optional[str]:
        if not self.recognizer:
            return None
        try:
            import speech_recognition as sr
            with sr.Microphone() as source:
                self.recognizer.adjust_for_ambient_noise(source)
                audio = self.recognizer.listen(source)
                return self.recognizer.recognize_google(audio).lower()
        except Exception as e:
            logger.error(f"Music listen error: {e}")
            return None
