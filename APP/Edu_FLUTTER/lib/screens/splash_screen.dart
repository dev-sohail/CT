import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:ct_mobile/providers/app_provider.dart';
import 'package:ct_mobile/screens/login_screen.dart';
import 'package:ct_mobile/screens/main_screen.dart';
import 'package:ct_mobile/utils/theme.dart';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<AppProvider>(context);

    if (!provider.isReady) {
      return const Scaffold(
        backgroundColor: AppTheme.primary,
        body: Center(
          child: CircularProgressIndicator(color: Colors.white),
        ),
      );
    }

    if (provider.isAuthenticated) {
      return const MainScreen();
    } else {
      return const LoginScreen();
    }
  }
}
