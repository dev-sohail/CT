import 'package:flutter_dotenv/flutter_dotenv.dart';

class Constants {
  static String get defaultBaseUrl {
    final fromEnv = const String.fromEnvironment('API_BASE_URL');
    if (fromEnv.isNotEmpty) return fromEnv;
    return dotenv.env['API_BASE_URL'] ?? 'http://10.0.2.2:8000';
  }
  
  static const List<String> roleOptions = ['teacher', 'student', 'parent', 'staff'];
  static const List<String> attendanceStatus = ['present', 'absent', 'late'];

  static const String dummyUsername = 'u*uuu314';
  static const String dummyPassword = 'p*ppp314';
  static const String dummyTokenPrefix = 'dummy_token_';

  static const String storageKeyBaseUrl = 'ct.baseUrl';
  static const String storageKeyToken = 'ct.token';
  static const String storageKeyUser = 'ct.user';

  static const Duration requestTimeout = Duration(seconds: 15);
}
