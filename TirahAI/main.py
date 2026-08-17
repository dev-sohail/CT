"""
TirahAi - AI Assistant
Main entry point for the application

Features:
- Voice recognition and natural language processing
- Code helper and analysis
- Study guide and educational assistance
- Hardware I/O control (Arduino, cameras, sensors)
- OS automation and system control
- On-screen wake button assistant
"""

import sys
import os

# Set up logging first
from utils.logger import setup_logger, get_logger
logger = setup_logger('TirahAi')

# Run validation checks
try:
    from utils.validation import run_startup_checks
    if not run_startup_checks():
        logger.warning("Some validation checks failed - continuing with caution")
except Exception as e:
    logger.warning(f"Could not run validation checks: {e}")

# Import remaining modules
from config.settings import DEBUG_MODE, FEATURES, LOGS_DIR

def run_modern_gui():
    """Run the modern Flet GUI with global error handling."""
    import flet as ft
    from gui.modern_ui import main as modern_main
    
    def safe_main(page: ft.Page):
        try:
            modern_main(page)
        except Exception as e:
            logger.critical(f"Critical UI Error: {e}", exc_info=True)
            page.clean()
            page.bgcolor = ft.Colors.BLUE_GREY_900
            page.add(
                ft.Container(
                    content=ft.Column([
                        ft.Icon(ft.Icons.ERROR_OUTLINE, color=ft.Colors.RED_400, size=64),
                        ft.Text("Critical Error Occurred", size=24, weight="bold", color=ft.Colors.RED_400),
                        ft.Container(height=20),
                        ft.Text(f"An unexpected error caused the application to crash:", color=ft.Colors.WHITE),
                        ft.Container(
                            content=ft.Text(f"{str(e)}", font_family="Consolas", color=ft.Colors.RED_200),
                            bgcolor=ft.Colors.BLACK,
                            padding=10,
                            border_radius=5
                        ),
                        ft.Container(height=20),
                        ft.Row([
                            ft.ElevatedButton("Attempt Restart", icon=ft.Icons.REFRESH, on_click=lambda _: page.go("/")),
                            ft.ElevatedButton("Open Logs", icon=ft.Icons.FOLDER_OPEN, on_click=lambda _: os.startfile(str(LOGS_DIR)))
                        ], spacing=10),
                        ft.Text("If the error persists, please check the logs.", size=12, color=ft.Colors.GREY_500)
                    ], alignment=ft.MainAxisAlignment.CENTER, horizontal_alignment=ft.CrossAxisAlignment.CENTER),
                    alignment=ft.alignment.center,
                    expand=True
                )
            )
            page.update()

    logger.info("Starting Modern UI (Flet)...")
    ft.app(target=safe_main)
    return 0

def main():
    """Main entry point for TirahAi."""
    logger.info("="*60)
    logger.info("Starting TirahAi - AI Assistant")
    logger.info("="*60)
    
    # Log enabled features
    logger.info("Enabled Features:")
    for feature, enabled in FEATURES.items():
        if enabled:
            logger.info(f"  [+] {feature.replace('_', ' ').title()}")
    
    try:
        # Check for arguments to force UI mode
        mode = "modern" # Default to modern
        if len(sys.argv) > 1:
            if "--cli" in sys.argv:
                mode = "cli"
                
        if mode == "cli":
            return cli_mode()
        else:
            return run_modern_gui()
        
    except Exception as e:
        logger.critical(f"Fatal error: {e}", exc_info=True)
        return 1


def cli_mode():
    """Run TirahAi in CLI mode (no GUI)."""
    logger.info("Starting TirahAi in CLI mode")
    
    from modules.voice import VoiceAssistant
    from modules.nlp_brain import NLPBrain
    
    voice = VoiceAssistant()
    brain = NLPBrain()
    
    voice.speak("TirahAi activated. How can I help you?")
    
    while True:
        try:
            # Listen for command
            command = voice.listen()
            
            if not command:
                continue
            
            logger.info(f"Command: {command}")
            
            # Check for exit
            if any(word in command for word in ['exit', 'quit', 'goodbye']):
                voice.speak("Goodbye! Have a great day.")
                break
            
            # Process command
            response = brain.process_command(command)
            
            # Speak response
            voice.speak(response)
            
        except KeyboardInterrupt:
            logger.info("Interrupted by user")
            voice.speak("Shutting down. Goodbye!")
            break
            
        except Exception as e:
            logger.error(f"Error: {e}")
            voice.speak("An error occurred. Please try again.")
    
    logger.info("TirahAi CLI session ended")


if __name__ == "__main__":
    # Check for CLI mode flag
    if len(sys.argv) > 1 and sys.argv[1] in ['--cli', '-c']:
        cli_mode()
    else:
        sys.exit(main())

