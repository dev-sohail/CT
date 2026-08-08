# CT Mobile - Flutter Version

A complete rewrite of the CTEdu Mobile app from React Native to Flutter.

## Features

- 🚀 Full login system with role selection (teacher, student, parent, staff)
- 📊 Dashboard with user profile and quick actions
- 📅 Attendance tracking and marking
- ⚙️ Settings with server URL configuration
- 💾 Local storage with secure persistence
- 📱 Beautiful Material Design UI

## Getting Started

### Prerequisites

- Flutter SDK (3.7.0 or higher)
- Dart SDK
- Android Studio / Xcode for mobile development

### Installation

1. Install dependencies:
```bash
flutter pub get
```

2. Run the app:
```bash
flutter run
```

## Project Structure

```
lib/
├── main.dart                 # App entry point
├── providers/               # State management
│   └── app_provider.dart
├── models/                  # Data models
│   └── user.dart
├── services/                # API services
│   └── api_service.dart
├── screens/                 # UI screens
│   ├── splash_screen.dart
│   ├── login_screen.dart
│   ├── dashboard_screen.dart
│   ├── attendance_screen.dart
│   ├── settings_screen.dart
│   └── main_screen.dart
├── widgets/                 # Reusable components
│   ├── screen_container.dart
│   ├── custom_input.dart
│   ├── primary_button.dart
│   └── empty_state.dart
└── utils/                   # Utilities
    ├── theme.dart
    ├── constants.dart
    └── helpers.dart
```

## Tech Stack

- **Flutter** - Cross-platform UI framework
- **Provider** - State management
- **http** - API requests
- **shared_preferences** - Local storage
- **font_awesome_flutter** - Icons
- **intl** - Date formatting

## License

MIT
