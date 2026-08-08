import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:ct_mobile/utils/constants.dart';
import 'package:ct_mobile/utils/helpers.dart';
import 'package:ct_mobile/models/user.dart';
import 'package:ct_mobile/services/api_service.dart';
import 'package:ct_mobile/services/notification_service.dart';
import 'dart:convert';

/// The main application state provider that manages authentication, user data,
/// and API configuration.
class AppProvider extends ChangeNotifier {
  /// Whether the provider has completed initialization
  bool _isReady = false;

  /// The base URL for API requests
  String _baseUrl = Constants.defaultBaseUrl;

  /// The authentication token for API requests
  String? _token;

  /// The currently authenticated user
  User? _user;

  /// Whether an async operation is in progress
  bool _isLoading = false;

  /// The current error message, if any
  String? _error;

  /// Whether the provider has completed initialization
  bool get isReady => _isReady;

  /// The base URL for API requests
  String get baseUrl => _baseUrl;

  /// The authentication token for API requests
  String? get token => _token;

  /// The currently authenticated user
  User? get user => _user;

  /// Whether an async operation is in progress
  bool get isLoading => _isLoading;

  /// The current error message, if any
  String? get error => _error;

  /// Whether the user is authenticated
  bool get isAuthenticated => _token != null && _user != null;

  /// Creates a new AppProvider instance and initializes it
  AppProvider() {
    _init();
  }

  /// Initializes the provider by loading stored data from SharedPreferences
  Future<void> _init() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final storedBaseUrl = prefs.getString(Constants.storageKeyBaseUrl);
      final storedToken = prefs.getString(Constants.storageKeyToken);
      final storedUser = prefs.getString(Constants.storageKeyUser);

      if (storedBaseUrl != null) {
        _baseUrl = Helpers.normalizeBaseUrl(storedBaseUrl);
      }
      if (storedToken != null) {
        _token = storedToken;
      }
      if (storedUser != null) {
        try {
          _user = User.fromJson(jsonDecode(storedUser));
        } catch (e) {
          await prefs.remove(Constants.storageKeyUser);
        }
      }

      try {
        await NotificationService().initialize();
      } catch (e) {}
    } catch (e) {
    } finally {
      _isReady = true;
      notifyListeners();
    }
  }

  /// Attempts to log in a user with the provided credentials
  ///
  /// [username]: The user's username
  /// [password]: The user's password
  /// [role]: The user's role
  Future<void> login(String username, String password, String role) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      if (username == Constants.dummyUsername && password == Constants.dummyPassword) {
        _token = '${Constants.dummyTokenPrefix}$role';
        _user = User(
          username: username,
          fullName: 'Dummy User',
          role: role,
          extra: {
            'dummy': true,
          },
        );

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(Constants.storageKeyToken, _token!);
        await prefs.setString(Constants.storageKeyUser, jsonEncode(_user!.toJson()));
        return;
      }

      final api = ApiService(baseUrl: _baseUrl);
      final data = await api.login(username, password, role);
      
      _token = data['access_token'];
      _user = User.fromJson(data['user']);

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(Constants.storageKeyToken, _token!);
      await prefs.setString(Constants.storageKeyUser, jsonEncode(_user!.toJson()));
    } catch (e) {
      _error = _formatError(e);
      rethrow;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Logs out the current user and clears all stored data
  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();

    try {
      _token = null;
      _user = null;
      _error = null;

      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(Constants.storageKeyToken);
      await prefs.remove(Constants.storageKeyUser);
    } catch (e) {
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Updates the API base URL
  ///
  /// [newUrl]: The new base URL to use
  Future<void> updateBaseUrl(String newUrl) async {
    try {
      _baseUrl = Helpers.normalizeBaseUrl(newUrl);
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(Constants.storageKeyBaseUrl, _baseUrl);
      notifyListeners();
    } catch (e) {
    }
  }

  /// Clears the current error message
  void clearError() {
    _error = null;
    notifyListeners();
  }

  /// Gets an API service instance with the current base URL and token
  ApiService get api => ApiService(baseUrl: _baseUrl, token: _token);

  Future<Map<String, dynamic>> chat(String message, {String? sessionId}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      final data = await api.chat(message, sessionId: sessionId);
      return data;
    } catch (e) {
      _error = _formatError(e);
      rethrow;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> executeTool(String tool, Map<String, dynamic> kwargs) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    try {
      final data = await api.executeTool(tool, kwargs);
      return data;
    } catch (e) {
      _error = _formatError(e);
      rethrow;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Formats an error object into a user-friendly string
  String _formatError(dynamic error) {
    if (error is String) {
      return error.replaceAll('Exception: ', '');
    }
    final errorStr = error.toString();
    if (errorStr.contains('Exception: ')) {
      return errorStr.replaceAll('Exception: ', '');
    }
    if (errorStr.contains('Failed host lookup')) {
      return 'Could not connect to server. Check your network and server URL.';
    }
    if (errorStr.contains('Connection refused')) {
      return 'Server not responding. Make sure the server is running.';
    }
    if (errorStr.contains('TimeoutException')) {
      return 'Request timed out. Please try again.';
    }
    return 'An error occurred. Please try again.';
  }
}
