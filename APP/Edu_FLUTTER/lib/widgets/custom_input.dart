import 'package:flutter/material.dart';
import 'package:ct_mobile/utils/theme.dart';

/// A modern, animated text input field with various customization options
class CustomInput extends StatefulWidget {
  /// Optional label text displayed above the input
  final String? label;

  /// Optional initial value
  final String? value;

  /// Callback when text changes
  final ValueChanged<String>? onChanged;

  /// Placeholder text when field is empty
  final String? placeholder;

  /// Whether to obscure the text (for passwords)
  final bool obscureText;

  /// Input keyboard type
  final TextInputType? keyboardType;

  /// Text capitalization mode
  final TextCapitalization textCapitalization;

  /// Maximum lines for multi-line inputs
  final int maxLines;

  /// Whether the input is enabled
  final bool enabled;

  /// Optional suffix widget
  final Widget? suffixIcon;

  /// Optional prefix widget
  final Widget? prefixIcon;

  /// Optional text validator
  final String? Function(String?)? validator;

  /// Optional autocorrect setting
  final bool autocorrect;

  const CustomInput({
    super.key,
    this.label,
    this.value,
    this.onChanged,
    this.placeholder,
    this.obscureText = false,
    this.keyboardType,
    this.textCapitalization = TextCapitalization.none,
    this.maxLines = 1,
    this.enabled = true,
    this.suffixIcon,
    this.prefixIcon,
    this.validator,
    this.autocorrect = true,
  });

  @override
  State<CustomInput> createState() => _CustomInputState();
}

class _CustomInputState extends State<CustomInput> {
  late final TextEditingController _controller;
  final FocusNode _focusNode = FocusNode();
  bool _isFocused = false;
  bool _obscureText = true;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.value);
    _focusNode.addListener(_onFocusChange);
    _obscureText = widget.obscureText;
  }

  @override
  void didUpdateWidget(CustomInput oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.value != widget.value && _controller.text != widget.value) {
      _controller.text = widget.value ?? '';
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  void _onFocusChange() {
    if (mounted) {
      setState(() {
        _isFocused = _focusNode.hasFocus;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (widget.label != null)
            AnimatedPadding(
              duration: const Duration(milliseconds: 200),
              padding: EdgeInsets.only(bottom: _isFocused ? 10 : 8),
              child: AnimatedDefaultTextStyle(
                duration: const Duration(milliseconds: 200),
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: _isFocused ? FontWeight.w600 : FontWeight.w500,
                  color: _isFocused ? AppTheme.primary : AppTheme.text,
                  letterSpacing: 0.2,
                ),
                child: Text(widget.label!),
              ),
            ),
          AnimatedContainer(
            duration: const Duration(milliseconds: 300),
            curve: Curves.easeOutQuart,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: _isFocused ? AppTheme.primary : AppTheme.border,
                width: _isFocused ? 2 : 1.5,
              ),
              color: widget.enabled ? AppTheme.inputBg : AppTheme.border.withOpacity(0.3),
              boxShadow: _isFocused && widget.enabled
                  ? [
                      BoxShadow(
                        color: AppTheme.primary.withOpacity(0.15),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ]
                  : [],
            ),
            child: TextField(
              controller: _controller,
              focusNode: _focusNode,
              onChanged: widget.onChanged,
              obscureText: widget.obscureText ? _obscureText : false,
              keyboardType: widget.keyboardType,
              textCapitalization: widget.textCapitalization,
              maxLines: widget.maxLines,
              enabled: widget.enabled,
              autocorrect: widget.autocorrect,
              style: TextStyle(
                fontSize: 16,
                color: widget.enabled ? AppTheme.text : AppTheme.textSecondary,
                fontWeight: FontWeight.w500,
                height: 1.4,
              ),
              decoration: InputDecoration(
                hintText: widget.placeholder,
                hintStyle: TextStyle(
                  color: AppTheme.textSecondary,
                  fontWeight: FontWeight.w400,
                ),
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 18,
                ),
                prefixIcon: widget.prefixIcon,
                suffixIcon: widget.obscureText
                    ? IconButton(
                        icon: Icon(
                          _obscureText
                              ? Icons.visibility_off_outlined
                              : Icons.visibility_outlined,
                          color: AppTheme.textSecondary,
                          size: 22,
                        ),
                        onPressed: () {
                          if (mounted) {
                            setState(() {
                              _obscureText = !_obscureText;
                            });
                          }
                        },
                        splashRadius: 24,
                      )
                    : widget.suffixIcon,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
