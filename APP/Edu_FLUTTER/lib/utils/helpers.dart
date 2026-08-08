import 'package:ct_mobile/utils/constants.dart';

class Helpers {
  static String normalizeBaseUrl(String? input) {
    final trimmed = (input ?? '').trim();
    if (trimmed.isEmpty) return Constants.defaultBaseUrl;
    return trimmed.replaceAll(RegExp(r'/+$'), '');
  }

  static String joinUrl(String baseUrl, String path) {
    final base = normalizeBaseUrl(baseUrl);
    final p = (path ?? '').trim();
    if (p.isEmpty) return base;
    if (p.startsWith('http://') || p.startsWith('https://')) return p;
    return '$base${p.startsWith('/') ? '' : '/'}$p';
  }

  static String capitalize(String s) {
    if (s.isEmpty) return s;
    return s[0].toUpperCase() + s.substring(1);
  }
}

