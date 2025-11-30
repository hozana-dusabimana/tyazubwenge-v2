import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';
import '../database/db_helper.dart';

class AuthService extends ChangeNotifier {
  static const String _defaultBaseURL = 'https://tyazubwenge.lanari.rw';
  String _baseURL = _defaultBaseURL;
  String? _token;
  Map<String, dynamic>? _user;

  AuthService() {
    _loadStoredAuth();
  }

  Future<void> _loadStoredAuth() async {
    try {
      final db = await DatabaseHelper.database;

      // Load token
      final tokenResult = await db.query(
        'settings',
        where: 'key = ?',
        whereArgs: ['auth_token'],
      );
      if (tokenResult.isNotEmpty) {
        _token = tokenResult.first['value'] as String?;
      }

      // Load user
      final userResult = await db.query(
        'settings',
        where: 'key = ?',
        whereArgs: ['auth_user'],
      );
      if (userResult.isNotEmpty) {
        final userJson = userResult.first['value'] as String?;
        if (userJson != null) {
          _user = jsonDecode(userJson) as Map<String, dynamic>;
        }
      }

      // Load base URL
      final urlResult = await db.query(
        'settings',
        where: 'key = ?',
        whereArgs: ['api_base_url'],
      );
      if (urlResult.isNotEmpty) {
        _baseURL = urlResult.first['value'] as String? ?? _defaultBaseURL;
      }
    } catch (e) {
      if (kDebugMode) {
        print('Error loading stored auth: $e');
      }
    }
  }

  Future<Map<String, dynamic>> login(
      String username, String password, String apiURL) async {
    _baseURL = apiURL.isEmpty ? _defaultBaseURL : apiURL;

    try {
      final response = await http
          .post(
            Uri.parse('$_baseURL/api/auth.php'),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'username': username,
              'password': password,
            }),
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body) as Map<String, dynamic>;

        if (data['success'] == true) {
          _token = data['token'] as String?;
          _user = data['user'] as Map<String, dynamic>?;

          // Store in database
          final db = await DatabaseHelper.database;
          await db.insert(
            'settings',
            {'key': 'auth_token', 'value': _token},
            conflictAlgorithm: ConflictAlgorithm.replace,
          );
          await db.insert(
            'settings',
            {'key': 'auth_user', 'value': jsonEncode(_user)},
            conflictAlgorithm: ConflictAlgorithm.replace,
          );
          await db.insert(
            'settings',
            {'key': 'api_base_url', 'value': _baseURL},
            conflictAlgorithm: ConflictAlgorithm.replace,
          );

          notifyListeners();

          return {'success': true, 'user': _user, 'token': _token};
        } else {
          return {
            'success': false,
            'message': data['message'] ?? 'Login failed'
          };
        }
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  Future<bool> loginOffline() async {
    if (_token != null && _user != null) {
      return true;
    }
    return false;
  }

  String? getToken() => _token;
  Map<String, dynamic>? getUser() => _user;
  String getBaseURL() => _baseURL;
  bool isAuthenticated() => _token != null && _user != null;

  Future<void> logout() async {
    _token = null;
    _user = null;

    final db = await DatabaseHelper.database;
    await db.delete('settings', where: 'key = ?', whereArgs: ['auth_token']);
    await db.delete('settings', where: 'key = ?', whereArgs: ['auth_user']);

    notifyListeners();
  }
}
