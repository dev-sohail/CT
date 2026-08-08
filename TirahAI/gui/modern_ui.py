"""
Modern UI for TirahAi using Flet.
Provides dashboard panels: Chat, Study, Code, System, Hardware, Settings.
"""

from __future__ import annotations

from config.settings import GUI_CONFIG, DEBUG_MODE
from core.agent import AgenticModel
from utils.logger import get_logger

logger = get_logger(__name__)


def main(page: "ft.Page"):
    import flet as ft
    page.title = GUI_CONFIG.get("app_name", "TirahAi")
    page.theme_mode = ft.ThemeMode.DARK if GUI_CONFIG.get("theme_mode", "dark") == "dark" else ft.ThemeMode.LIGHT
    page.window_width = 1100
    page.window_height = 720
    page.padding = 0
    page.bgcolor = ft.Colors.BLUE_GREY_900

    agent = AgenticModel()

    nav = ft.NavigationRail(
        selected_index=0,
        label_type=ft.NavigationRailLabelType.ALL,
        min_width=80,
        min_extended_width=200,
        destinations=[
            ft.NavigationRailDestination(icon=ft.Icons.CHAT, label_content=ft.Text("Chat")),
            ft.NavigationRailDestination(icon=ft.Icons.SCHOOL, label_content=ft.Text("Study")),
            ft.NavigationRailDestination(icon=ft.Icons.CODE, label_content=ft.Text("Code")),
            ft.NavigationRailDestination(icon=ft.Icons.COMPUTER, label_content=ft.Text("System")),
            ft.NavigationRailDestination(icon=ft.Icons.HARDWARE, label_content=ft.Text("Hardware")),
            ft.NavigationRailDestination(icon=ft.Icons.SETTINGS, label_content=ft.Text("Settings")),
        ],
    )

    chat_history = ft.ListView(expand=True, spacing=8, auto_scroll=True)
    chat_input = ft.TextField(
        hint_text="Ask TirahAi anything...",
        expand=True,
        border_color=ft.Colors.TRANSPARENT,
        bgcolor=ft.Colors.BLUE_GREY_800,
    )

    def add_message(role: str, text: str):
        prefix = "You: " if role == "user" else "TirahAi: "
        color = ft.Colors.BLUE_200 if role == "user" else ft.Colors.GREEN_200
        chat_history.controls.append(
            ft.Container(
                content=ft.Text(f"{prefix}{text}", color=color),
                bgcolor=ft.Colors.BLUE_GREY_800,
                padding=12,
                border_radius=12,
            )
        )
        page.update()

    def on_send(e=None):
        text = chat_input.value.strip()
        if not text:
            return
        add_message("user", text)
        chat_input.value = ""
        page.update()
        result = agent.process(text)
        add_message("assistant", result.get("response", ""))

    send_btn = ft.IconButton(icon=ft.Icons.SEND, on_click=on_send)
    chat_tab = ft.Column(
        [
            ft.Container(chat_history, expand=True, padding=12),
            ft.Row([chat_input, send_btn], padding=12),
        ],
        expand=True,
    )

    study_text = ft.TextField(
        hint_text="Enter a topic to explain, quiz, or summarize...",
        multiline=True,
        min_lines=3,
        expand=True,
        bgcolor=ft.Colors.BLUE_GREY_800,
    )
    study_output = ft.ListView(expand=True, spacing=8, auto_scroll=True)

    def on_study(e=None):
        topic = study_text.value.strip()
        if not topic:
            return
        result = agent.process(f"explain {topic}")
        study_output.controls.clear()
        study_output.controls.append(ft.Text(result.get("response", ""), color=ft.Colors.GREEN_200))
        page.update()

    study_tab = ft.Column(
        [
            ft.Row([study_text, ft.IconButton(icon=ft.Icons.PLAY_ARROW, on_click=on_study)], spacing=8),
            ft.Container(study_output, expand=True, padding=12),
        ],
        expand=True,
    )

    code_input = ft.TextField(
        hint_text="Paste or type code here...",
        multiline=True,
        min_lines=6,
        expand=True,
        bgcolor=ft.Colors.BLUE_GREY_800,
        font_family="Consolas",
    )
    code_output = ft.ListView(expand=True, spacing=8, auto_scroll=True)

    def on_analyze(e=None):
        code = code_input.value.strip()
        if not code:
            return
        result = agent.process(f"analyze code: {code}")
        code_output.controls.clear()
        code_output.controls.append(ft.Text(result.get("response", ""), color=ft.Colors.GREEN_200))
        page.update()

    code_tab = ft.Column(
        [
            ft.Row([code_input], expand=True),
            ft.Row([ft.ElevatedButton("Analyze", on_click=on_analyze)], padding=8),
            ft.Container(code_output, expand=True, padding=12),
        ],
        expand=True,
    )

    sys_info_text = ft.Text("", selectable=True, color=ft.Colors.GREEN_200)

    def load_system_info(e=None):
        try:
            result = agent.process("system info")
            sys_info_text.value = result.get("response", "")
        except Exception as exc:
            sys_info_text.value = f"Error: {exc}"
        page.update()

    system_tab = ft.Column(
        [
            ft.ElevatedButton("Refresh System Info", on_click=load_system_info),
            ft.Container(sys_info_text, expand=True, padding=12),
        ],
        expand=True,
    )

    hardware_text = ft.Text("Select a hardware action:", color=ft.Colors.GREEN_200)

    def on_hardware_action(e=None):
        action = hardware_dropdown.value
        if not action:
            return
        result = agent.process(action)
        hardware_output.value = result.get("response", "")
        page.update()

    hardware_dropdown = ft.Dropdown(
        options=[
            ft.dropdown.Option("camera capture"),
            ft.dropdown.Option("arduino status"),
            ft.dropdown.Option("iot devices"),
        ],
        width=300,
        bgcolor=ft.Colors.BLUE_GREY_800,
    )
    hardware_output = ft.Text("", color=ft.Colors.GREEN_200, selectable=True)

    hardware_tab = ft.Column(
        [
            hardware_text,
            ft.Row([hardware_dropdown, ft.IconButton(icon=ft.Icons.PLAY_ARROW, on_click=on_hardware_action)]),
            ft.Container(hardware_output, expand=True, padding=12),
        ],
        expand=True,
    )

    user_name_field = ft.TextField(
        label="User Name",
        value=GUI_CONFIG.get("user_name", "User"),
        bgcolor=ft.Colors.BLUE_GREY_800,
    )
    voice_toggle = ft.Switch(label="Voice Enabled", value=bool(GUI_CONFIG.get("voice_enabled")))

    def save_settings(e=None):
        GUI_CONFIG.set("user_name", user_name_field.value)
        GUI_CONFIG.set("voice_enabled", bool(voice_toggle.value))
        page.snack_bar = ft.SnackBar(ft.Text("Settings saved"))
        page.snack_bar.open = True
        page.update()

    settings_tab = ft.Column(
        [
            user_name_field,
            voice_toggle,
            ft.ElevatedButton("Save Settings", on_click=save_settings),
        ],
        spacing=12,
        expand=True,
    )

    tabs = {
        0: chat_tab,
        1: study_tab,
        2: code_tab,
        3: system_tab,
        4: hardware_tab,
        5: settings_tab,
    }

    content = ft.Container(tabs[0], expand=True, bgcolor=ft.Colors.BLUE_GREY_900)

    def on_nav_change(e):
        idx = nav.selected_index
        content.content = tabs[idx]
        page.update()

    nav.on_change = on_nav_change

    page.add(
        ft.Row(
            [
                nav,
                ft.VerticalDivider(width=1),
                content,
            ],
            expand=True,
        )
    )

    page.update()
