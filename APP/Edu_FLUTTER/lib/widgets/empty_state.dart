import 'package:flutter/material.dart';
import 'package:ct_mobile/utils/theme.dart';
import 'package:ct_mobile/widgets/primary_button.dart';

class EmptyState extends StatelessWidget {
  final Widget icon;
  final String title;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

  EmptyState({
    super.key,
    this.icon = const SizedBox.shrink(),
    required this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              height: 48,
              width: 48,
              child: Center(
                child: IconTheme(
                  data: const IconThemeData(size: 48, color: AppTheme.textSecondary),
                  child: icon,
                ),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              title,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: AppTheme.text,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 14,
                color: AppTheme.textSecondary,
              ),
            ),
            if (onAction != null && actionLabel != null)
              Padding(
                padding: const EdgeInsets.only(top: 16),
                child: PrimaryButton(
                  title: actionLabel!,
                  onPressed: onAction,
                ),
              ),
          ],
        ),
      ),
    );
  }
}
