import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:ct_mobile/screens/dashboard_screen.dart';
import 'package:ct_mobile/screens/attendance_screen.dart';
import 'package:ct_mobile/screens/settings_screen.dart';
import 'package:ct_mobile/utils/theme.dart';

class MainScreen extends StatefulWidget {
  const MainScreen({super.key});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  int _currentIndex = 0;

  final List<Widget> _screens = const [
    DashboardScreen(),
    AttendanceScreen(),
    SettingsScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (index) => setState(() => _currentIndex = index),
        backgroundColor: AppTheme.card,
        surfaceTintColor: Colors.transparent,
        elevation: 10,
        destinations: const [
          NavigationDestination(
            icon: FaIcon(FontAwesomeIcons.house),
            selectedIcon: FaIcon(FontAwesomeIcons.house),
            label: 'Dashboard',
          ),
          NavigationDestination(
            icon: FaIcon(FontAwesomeIcons.calendar),
            selectedIcon: FaIcon(FontAwesomeIcons.calendar),
            label: 'Attendance',
          ),
          NavigationDestination(
            icon: FaIcon(FontAwesomeIcons.gear),
            selectedIcon: FaIcon(FontAwesomeIcons.gear),
            label: 'Settings',
          ),
        ],
      ),
    );
  }
}
