import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:ct_mobile/providers/app_provider.dart';
import 'package:ct_mobile/utils/theme.dart';
import 'package:ct_mobile/utils/constants.dart';
import 'package:ct_mobile/widgets/custom_input.dart';
import 'package:ct_mobile/widgets/primary_button.dart';
import 'package:ct_mobile/widgets/screen_container.dart';
import 'package:ct_mobile/screens/main_screen.dart';
import 'package:ct_mobile/services/biometric_service.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _usernameController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  String _selectedRole = '';

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    final provider = Provider.of<AppProvider>(context, listen: false);
    if (_selectedRole.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select a role before signing in.'),
          backgroundColor: AppTheme.error,
        ),
      );
      return;
    }

    try {
      await provider.login(
        _usernameController.text.trim(),
        _passwordController.text.trim(),
        _selectedRole,
      );
      if (!mounted) return;
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const MainScreen()),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.toString().replaceAll('Exception: ', '')),
          backgroundColor: AppTheme.error,
        ),
      );
    }
  }

  Future<void> _handleBiometricLogin() async {
    final provider = Provider.of<AppProvider>(context, listen: false);
    final success = await BiometricService().authenticate(
      reason: 'Authenticate to sign in to CTEdu',
    );
    if (!success || !mounted) return;
    if (_selectedRole.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select a role before signing in.'),
          backgroundColor: AppTheme.error,
        ),
      );
      return;
    }
    try {
      await provider.login(
        _usernameController.text.trim(),
        _passwordController.text.trim(),
        _selectedRole,
      );
      if (!mounted) return;
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const MainScreen()),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.toString().replaceAll('Exception: ', '')),
          backgroundColor: AppTheme.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<AppProvider>(context);
    final isButtonEnabled = _usernameController.text.isNotEmpty &&
        _passwordController.text.isNotEmpty &&
        _selectedRole.isNotEmpty;

    return Scaffold(
      backgroundColor: AppTheme.primary,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Center(
                    child: FaIcon(
                      FontAwesomeIcons.school,
                      color: Colors.white,
                      size: 40,
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  'CTEdu',
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Sign in to continue',
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.white.withOpacity(0.85),
                  ),
                ),
                const SizedBox(height: 32),
                Card(
                  elevation: 8,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        if (provider.error != null)
                          ErrorBanner(
                            message: provider.error!,
                            onDismiss: () => provider.clearError(),
                          ),
                        const Text(
                          'Select your role',
                          style: TextStyle(
                            fontSize: 14,
                            color: AppTheme.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 10),
                        RoleSelector(
                          options: Constants.roleOptions,
                          selected: _selectedRole,
                          onSelected: (role) {
                            setState(() {
                              _selectedRole = role;
                            });
                          },
                        ),
                        const SizedBox(height: 16),
                        CustomInput(
                          label: 'Username',
                          value: _usernameController.text,
                          onChanged: (value) {
                            setState(() {});
                            _usernameController.text = value;
                          },
                          placeholder: 'Enter your username',
                        ),
                        CustomInput(
                          label: 'Password',
                          value: _passwordController.text,
                          onChanged: (value) {
                            setState(() {});
                            _passwordController.text = value;
                          },
                          placeholder: 'Enter your password',
                          obscureText: true,
                        ),
                        const SizedBox(height: 8),
                        PrimaryButton(
                          title: 'Sign In',
                          onPressed: provider.isLoading ? null : _handleLogin,
                          isLoading: provider.isLoading,
                          isEnabled: isButtonEnabled,
                        ),
                        FutureBuilder<bool>(
                          future: BiometricService().isAvailable,
                          builder: (context, snapshot) {
                            if (!(snapshot.data ?? false)) return const SizedBox.shrink();
                            return TextButton.icon(
                              onPressed: provider.isLoading ? null : _handleBiometricLogin,
                              icon: const FaIcon(FontAwesomeIcons.fingerprint, size: 20),
                              label: const Text('Sign in with Biometrics'),
                              style: TextButton.styleFrom(foregroundColor: Colors.white),
                            );
                          },
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Use dummy credentials: ${Constants.dummyUsername} / ${Constants.dummyPassword}',
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.75),
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
