import 'package:flutter/material.dart';
import 'package:ct_mobile/utils/theme.dart';

/// A modern, animated primary button with gradient effects
class PrimaryButton extends StatelessWidget {
  /// Callback when button is pressed
  final VoidCallback? onPressed;

  /// Button title text
  final String title;

  /// Whether to show a loading indicator instead of title
  final bool isLoading;

  /// Whether to use danger color scheme
  final bool isDanger;

  /// Whether the button is enabled
  final bool isEnabled;

  /// Optional custom height
  final double? height;

  const PrimaryButton({
    super.key,
    required this.onPressed,
    required this.title,
    this.isLoading = false,
    this.isDanger = false,
    this.isEnabled = true,
    this.height,
  });

  @override
  Widget build(BuildContext context) {
    final color = isDanger ? AppTheme.error : AppTheme.primary;
    final isDisabled = isLoading || !isEnabled || onPressed == null;

    return SizedBox(
      width: double.infinity,
      height: height ?? 56,
      child: AnimatedScale(
        scale: isDisabled ? 1.0 : 0.98,
        duration: const Duration(milliseconds: 150),
        curve: Curves.easeInOutCubic,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
          decoration: BoxDecoration(
            gradient: isDisabled
                ? null
                : LinearGradient(
                    colors: isDanger
                        ? [AppTheme.error, const Color(0xFFDC2626)]
                        : [AppTheme.primary, AppTheme.primaryDark],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
            borderRadius: BorderRadius.circular(16),
            boxShadow: isDisabled
                ? []
                : [
                    BoxShadow(
                      color: color.withOpacity(0.4),
                      blurRadius: 16,
                      offset: const Offset(0, 6),
                      spreadRadius: -2,
                    ),
                  ],
          ),
          child: ElevatedButton(
            onPressed: isDisabled ? null : onPressed,
            style: ElevatedButton.styleFrom(
              backgroundColor: isDisabled ? color.withOpacity(0.5) : Colors.transparent,
              foregroundColor: Colors.white,
              elevation: 0,
              shadowColor: Colors.transparent,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              overlayColor: Colors.white.withOpacity(0.15),
              splashFactory: InkRipple.splashFactory,
            ),
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 250),
              transitionBuilder: (child, animation) => ScaleTransition(
                scale: animation,
                child: FadeTransition(opacity: animation, child: child),
              ),
              child: isLoading
                  ? const SizedBox(
                      key: ValueKey('loading'),
                      height: 24,
                      width: 24,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2.5,
                      ),
                    )
                  : Text(
                      title,
                      key: ValueKey(title),
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.3,
                      ),
                    ),
            ),
          ),
        ),
      ),
    );
  }
}

/// An animated role selector with smooth transitions
class RoleSelector extends StatelessWidget {
  /// List of role options to display
  final List<String> options;

  /// Currently selected role
  final String selected;

  /// Callback when a role is selected
  final ValueChanged<String> onSelected;

  const RoleSelector({
    super.key,
    required this.options,
    required this.selected,
    required this.onSelected,
  });

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 12,
      runSpacing: 12,
      children: options.map((option) {
        final isActive = option == selected;
        return TweenAnimationBuilder<double>(
          duration: const Duration(milliseconds: 350),
          curve: Curves.easeOutBack,
          tween: Tween(begin: 1.0, end: isActive ? 1.05 : 1.0),
          builder: (context, scale, child) {
            return Transform.scale(
              scale: scale,
              child: GestureDetector(
                onTap: () => onSelected(option),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  curve: Curves.easeOutQuart,
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                  decoration: BoxDecoration(
                    color: isActive ? AppTheme.primary : AppTheme.inputBg,
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(
                      color: isActive ? AppTheme.primary : AppTheme.border,
                      width: isActive ? 2 : 1.5,
                    ),
                    boxShadow: isActive
                        ? [
                            BoxShadow(
                              color: AppTheme.primary.withOpacity(0.3),
                              blurRadius: 12,
                              offset: const Offset(0, 4),
                            ),
                          ]
                        : [],
                  ),
                  child: AnimatedDefaultTextStyle(
                    duration: const Duration(milliseconds: 300),
                    style: TextStyle(
                      fontSize: 14,
                      color: isActive ? Colors.white : AppTheme.textSecondary,
                      fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
                    ),
                    child: Text(
                      Helpers.capitalize(option),
                    ),
                  ),
                ),
              ),
            );
          },
        );
      }).toList(),
    );
  }
}

/// Utility helper methods for UI components
class Helpers {
  /// Capitalizes the first letter of a string
  static String capitalize(String s) {
    if (s.isEmpty) return s;
    if (s.length == 1) return s.toUpperCase();
    return s[0].toUpperCase() + s.substring(1);
  }

  /// Formats an error message for display
  static String formatErrorMessage(dynamic error) {
    if (error is String) return error;
    return error.toString().replaceAll('Exception: ', '');
  }
}
