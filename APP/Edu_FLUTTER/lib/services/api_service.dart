import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:ct_mobile/utils/constants.dart';
import 'package:ct_mobile/utils/helpers.dart';
import 'package:ct_mobile/models/user.dart';
import 'package:hive_flutter/hive_flutter.dart';

class ApiService {
  final String baseUrl;
  final String? token;

  static const String _defaultApiPrefix = '/v1';

  ApiService({required this.baseUrl, this.token});

  bool get isDummy => token != null && token!.startsWith(Constants.dummyTokenPrefix);

  Future<Map<String, dynamic>> _request(
    String path, {
    String method = 'GET',
    Map<String, String>? formData,
    Map<String, dynamic>? jsonBody,
  }) async {
    final isDummy = token != null && token!.startsWith(Constants.dummyTokenPrefix);
    if (isDummy) {
      final role = token!.substring(Constants.dummyTokenPrefix.length);
      if (path == '$_defaultApiPrefix/auth/me') {
        return {
          'user': {
            'username': Constants.dummyUsername,
            'full_name': 'Dummy User',
            'role': role,
          },
        };
      }
      if (path == '$_defaultApiPrefix/assistant/chat') {
        return {
          'response': 'Dummy assistant response',
          'intent': 'agent_general',
          'confidence': 0.8,
          'tool_results': [],
          'session_id': 'dummy-session',
          'success': true,
          'timestamp': DateTime.now().toIso8601String(),
        };
      }
      if (path == '$_defaultApiPrefix/tools/execute') {
        return {'tool': 'get_time', 'result': {'time': DateTime.now().toIso8601String()}, 'success': true};
      }
      if (path == '$_defaultApiPrefix/health') {
        return {'status': 'ok', 'service': 'fastapi-v1', 'version': '0.1.0'};
      }
      if (path == '$_defaultApiPrefix/capabilities') {
        return {'intents': [], 'tools': [], 'status': {'ready': true}};
      }
    }

    final url = Uri.parse(Helpers.joinUrl(baseUrl, '$_defaultApiPrefix$path'));
    final headers = <String, String>{
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };

    try {
      http.Response response;

      switch (method) {
        case 'POST':
          if (formData != null) {
            headers['Content-Type'] = 'application/x-www-form-urlencoded';
            response = await http
                .post(
                  url,
                  headers: headers,
                  body: formData,
                )
                .timeout(Constants.requestTimeout);
          } else {
            headers['Content-Type'] = 'application/json';
            response = await http
                .post(
                  url,
                  headers: headers,
                  body: jsonEncode(jsonBody),
                )
                .timeout(Constants.requestTimeout);
          }
          break;
        case 'GET':
        default:
          response = await http
              .get(url, headers: headers)
              .timeout(Constants.requestTimeout);
          break;
      }

      final contentType = response.headers['content-type'] ?? '';
      Map<String, dynamic> data;

      if (contentType.contains('application/json')) {
        data = jsonDecode(response.body);
      } else {
        data = {'message': response.body};
      }

      if (!response.statusCode.toString().startsWith('2')) {
        throw Exception(data['detail'] ?? data['error'] ?? data['message'] ?? 'Request failed');
      }

      return data;
    } catch (e) {
      if (e.toString().contains('TimeoutException')) {
        throw Exception('Connection timed out. Please check your internet.');
      }
      if (e.toString().contains('Failed host lookup') ||
          e.toString().contains('Connection refused')) {
        throw Exception('Unable to connect to server. Please check your connection.');
      }
      rethrow;
    }
  }

  Future<Map<String, dynamic>> login(
    String username,
    String password,
    String role,
  ) async {
    return await _request(
      '/auth/login',
      method: 'POST',
      jsonBody: {
        'email': username,
        'password': password,
      },
    );
  }

  Future<Map<String, dynamic>> getMe() async {
    if (isDummy) {
      final role = token!.substring(Constants.dummyTokenPrefix.length);
      return {
        'user': {
          'username': Constants.dummyUsername,
          'full_name': 'Dummy User',
          'role': role,
        }
      };
    }
    return await _request('/auth/me');
  }

  Future<Map<String, dynamic>> chat(String message, {String? sessionId, String? context}) async {
    if (isDummy) {
      return {
        'response': 'Dummy response to: $message',
        'intent': 'agent_general',
        'confidence': 0.8,
        'tool_results': [],
        'session_id': sessionId ?? 'dummy-session',
        'success': true,
        'timestamp': DateTime.now().toIso8601String(),
      };
    }
    return await _request(
      '/assistant/chat',
      method: 'POST',
      jsonBody: {
        'message': message,
        if (sessionId != null) 'session_id': sessionId,
        if (context != null) 'context': context,
      },
    );
  }

  Future<Map<String, dynamic>> executeTool(String tool, Map<String, dynamic> kwargs) async {
    return await _request(
      '/tools/execute',
      method: 'POST',
      jsonBody: {
        'tool': tool,
        'kwargs': kwargs,
      },
    );
  }

  Future<List<dynamic>> getAttendance() async {
    if (isDummy) {
      return [
        {'id': 1, 'date': '2026-06-20', 'attendance_status': 'present'},
        {'id': 2, 'date': '2026-06-21', 'attendance_status': 'late'},
        {'id': 3, 'date': '2026-06-22', 'attendance_status': 'absent'},
      ];
    }
    try {
      final box = Hive.box('offline_cache');
      final cached = box.get('attendance');
      if (cached != null && cached is List) {
        return cached;
      }
    } catch (e) {}
    final data = await _request('/attendance');
    final list = data['attendance'] ?? [];
    try {
      final box = Hive.box('offline_cache');
      await box.put('attendance', list);
    } catch (e) {}
    return list;
  }

  Future<void> markAttendance(String date, String status) async {
    if (isDummy) {
      return;
    }
    await _request(
      '/attendance',
      method: 'POST',
      jsonBody: {
        'date': date,
        'status': status,
      },
    );
  }
}
