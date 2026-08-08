# TirahAi User Guide

Welcome to **TirahAi**, your AI assistant designed to boost productivity, learning, and wellness. This guide will help you get started and make the most of its features.

## Table of Contents
1. [Getting Started](#getting-started)
2. [Features Overview](#features-overview)
3. [Using the Modules](#using-the-modules)
   - [Dashboard](#dashboard)
   - [Chat Assistant](#chat-assistant)
   - [Code Editor](#code-editor)
   - [Study Guide](#study-guide)
   - [Productivity Apps](#productivity-apps)
   - [System Tools](#system-tools)
4. [Settings & Customization](#settings-customization)
5. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Installation
Ensure you have Python 3.10+ installed.
1. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```
2. Run the application:
   Double-click `run.bat` or execute:
   ```bash
   python main.py
   ```

### First Run
Upon launching, TirahAi will initialize its databases in the `data/` directory. You will be greeted by the voice assistant (if enabled) and presented with the Dashboard.

---

## Features Overview
TirahAi integrates multiple tools into a single interface:
- **AI Chat**: Natural language assistant with voice support.
- **Productivity**: Task manager, Journal, Pomodoro Timer, Mind Map.
- **Wellness**: Mood tracking and breathing exercises.
- **Learning**: Flashcards and study resources.
- **Coding**: Python code editor with execution capability.
- **System**: PC monitoring, file organization, and automation.
- **IoT**: Smart home device control (simulated or MQTT).

---

## Using the Modules

### Dashboard
The central hub displaying:
- **Greeting**: Personalized welcome based on time of day.
- **Summary Cards**: Quick stats on pending tasks, journal entries, and system health.
- **Quick Actions**: One-click access to common tasks.
- **System Monitor**: Real-time CPU, Memory, and Battery usage.

### Chat Assistant
- **Text & Voice**: Type your query or click the Microphone icon to speak.
- **Global Search**: Click the Search icon in the sidebar to find apps or ask questions.
- **Commands**: Try "Open Pomodoro", "Open Journal", or "What time is it?".
- **Offline Fallback**: Basic queries work without internet; complex ones use OpenAI (if configured).

### Code Editor
- **Write & Run**: Edit Python code with syntax highlighting.
- **Execute**: Click "Run Code" to execute Python scripts securely.
- **File Operations**: Open and Save files directly from your system.

### Study Guide
- **Flashcards**: Create and review flashcards for active recall.
- **Resources**: Access curated links and videos.
- **Notes**: Take persistent notes for your subjects.

### Productivity Apps
Located in the "Apps" view:
- **Tasks**: robust todo list with due dates and priority filtering.
- **Journal**: Daily diary with sentiment analysis charts.
- **Pomodoro**: Focus timer with customizable work/break intervals and background sounds.
- **Mind Map**: Visual brainstorming tool (nodes persist automatically).
- **Wellness**: Track your mood and view trends over time.

### System Tools
Located in the "System" view:
- **System Monitor**: Detailed hardware specs and usage.
- **File Organizer**: Select a folder to automatically sort files into categories (Images, Docs, Audio, etc.).
- **IoT Control**: Manage smart devices (Lights, Thermostats, Fans).

---

## Settings & Customization
Navigate to the **Settings** tab to personalize TirahAi:
- **User Profile**: Update your name.
- **Theme**: Toggle between Dark and Light mode.
- **Voice**: Enable/Disable voice responses.
- **Startup Greeting**: Toggle the "Good Morning" voice welcome.
- **API Keys**: Enter your OpenAI or Weather API keys for enhanced functionality.

---

## Troubleshooting

### Common Issues
- **Voice not working**: Ensure your microphone is set as default. Check "Enable Voice Response" in Settings.
- **App Crashes**: Check `logs/tirah.log` for error details.
- **Database Errors**: If data isn't saving, ensure the `data/` directory is writable.

### Resetting
To reset the application to default state:
1. Close the app.
2. Delete the `config.json` file in the root directory.
3. Restart the app.

---
*TirahAi v2.0 - Empowering your digital life.*
