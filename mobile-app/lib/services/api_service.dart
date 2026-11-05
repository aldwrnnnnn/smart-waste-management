import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../config/api_config.dart';
import '../models/user_model.dart';
import '../models/waste_report_model.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;

  const ApiException(this.message, {this.statusCode});

  @override
  String toString() => message;
}

class PaginatedReportsResult {
  final List<WasteReportModel> items;
  final int currentPage;
  final int lastPage;
  final int total;

  const PaginatedReportsResult({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  bool get hasMore => currentPage < lastPage;
}

class ApiService {
  static const String _tokenKey = 'auth_access_token';
  static const String _userJsonKey = 'auth_user_json';

  final http.Client _client;

  ApiService({http.Client? client}) : _client = client ?? http.Client();

  void close() => _client.close();

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<UserModel?> getSavedUser() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_userJsonKey);
    if (raw == null || raw.isEmpty) return null;
    try {
      final map = jsonDecode(raw) as Map<String, dynamic>;
      return UserModel.fromJson(map);
    } catch (_) {
      return null;
    }
  }

  Future<void> saveAuthSession(String token, UserModel user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_userJsonKey, jsonEncode(user.toJson()));
  }

  Future<void> clearAuthSession() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userJsonKey);
  }

  Future<Map<String, String>> _buildHeaders({bool withAuth = true, bool isJson = true}) async {
    final headers = <String, String>{
      HttpHeaders.acceptHeader: 'application/json',
      'ngrok-skip-browser-warning': 'true',
    };
    if (isJson) {
      headers[HttpHeaders.contentTypeHeader] = 'application/json; charset=UTF-8';
    }
    if (withAuth) {
      final token = await getToken();
      if (token != null && token.isNotEmpty) {
        headers[HttpHeaders.authorizationHeader] = 'Bearer $token';
      }
    }
    return headers;
  }

  // POST /api/login
  Future<UserModel> login({
    required String email,
    required String password,
  }) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/login');
    final response = await _client
        .post(
          uri,
          headers: await _buildHeaders(withAuth: false),
          body: jsonEncode({
            'email': email.trim(),
            'password': password,
          }),
        )
        .timeout(const Duration(seconds: 15));

    final body = _decodeJsonBody(response.body);

    if (response.statusCode == 200 && body['success'] == true && body['data'] is Map) {
      final data = Map<String, dynamic>.from(body['data'] as Map);
      final token = data['access_token']?.toString() ?? '';
      final userMap = data['user'] is Map
          ? Map<String, dynamic>.from(data['user'] as Map)
          : <String, dynamic>{};
      final user = UserModel.fromJson(userMap);
      await saveAuthSession(token, user);
      return user;
    }

    throw ApiException(
      _extractErrorMessage(body, defaultMsg: 'Email atau kata sandi tidak sesuai.'),
      statusCode: response.statusCode,
    );
  }

  // POST /api/register
  Future<UserModel> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/register');
    final response = await _client
        .post(
          uri,
          headers: await _buildHeaders(withAuth: false),
          body: jsonEncode({
            'name': name.trim(),
            'email': email.trim(),
            'password': password,
            'password_confirmation': passwordConfirmation,
          }),
        )
        .timeout(const Duration(seconds: 15));

    final body = _decodeJsonBody(response.body);

    if (response.statusCode == 201 && body['success'] == true && body['data'] is Map) {
      final data = Map<String, dynamic>.from(body['data'] as Map);
      final token = data['access_token']?.toString() ?? '';
      final userMap = data['user'] is Map
          ? Map<String, dynamic>.from(data['user'] as Map)
          : <String, dynamic>{};
      final user = UserModel.fromJson(userMap);
      await saveAuthSession(token, user);
      return user;
    }

    throw ApiException(
      _extractErrorMessage(body, defaultMsg: 'Gagal mendaftarkan akun baru.'),
      statusCode: response.statusCode,
    );
  }

  // POST /api/logout
  Future<void> logout() async {
    try {
      final uri = Uri.parse('${ApiConfig.baseUrl}/logout');
      await _client
          .post(uri, headers: await _buildHeaders(withAuth: true))
          .timeout(const Duration(seconds: 10));
    } finally {
      await clearAuthSession();
    }
  }

  // GET /api/dashboard-summary
  Future<Map<String, dynamic>> getDashboardSummary() async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/dashboard-summary');
    final response = await _client
        .get(uri, headers: await _buildHeaders(withAuth: true))
        .timeout(const Duration(seconds: 15));

    final body = _decodeJsonBody(response.body);

    if (response.statusCode == 200 && body['success'] == true && body['data'] is Map) {
      return Map<String, dynamic>.from(body['data'] as Map);
    }

    throw ApiException(
      _extractErrorMessage(body, defaultMsg: 'Gagal memuat ringkasan dashboard.'),
      statusCode: response.statusCode,
    );
  }

  // GET /api/reports
  Future<List<WasteReportModel>> getReports({
    String? status,
    String? wasteType,
    String? search,
    int page = 1,
  }) async {
    final result = await getReportsPaginated(
      status: status,
      wasteType: wasteType,
      search: search,
      page: page,
    );
    return result.items;
  }

  Future<PaginatedReportsResult> getReportsPaginated({
    String? status,
    String? wasteType,
    String? search,
    int page = 1,
  }) async {
    final queryParams = <String, String>{};
    if (status != null && status.isNotEmpty) queryParams['status'] = status;
    if (wasteType != null && wasteType.isNotEmpty) queryParams['waste_type'] = wasteType;
    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (page > 1) queryParams['page'] = page.toString();

    final uri = Uri.parse('${ApiConfig.baseUrl}/reports').replace(
      queryParameters: queryParams.isEmpty ? null : queryParams,
    );

    final response = await _client
        .get(uri, headers: await _buildHeaders(withAuth: true))
        .timeout(const Duration(seconds: 15));

    final body = _decodeJsonBody(response.body);

    if (response.statusCode == 200 && body['success'] == true && body['data'] is List) {
      final list = (body['data'] as List<dynamic>)
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e));
      final items = list.map(WasteReportModel.fromJson).toList();
      final meta = body['meta'] is Map
          ? Map<String, dynamic>.from(body['meta'] as Map)
          : <String, dynamic>{};
      return PaginatedReportsResult(
        items: items,
        currentPage: WasteReportModel.parseInt(meta['current_page'], page),
        lastPage: WasteReportModel.parseInt(meta['last_page'], page),
        total: WasteReportModel.parseInt(meta['total'], items.length),
      );
    }

    throw ApiException(
      _extractErrorMessage(body, defaultMsg: 'Gagal memuat daftar laporan.'),
      statusCode: response.statusCode,
    );
  }

  // GET /api/reports/{id}
  Future<WasteReportModel> getReportDetail(int id) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/reports/$id');
    final response = await _client
        .get(uri, headers: await _buildHeaders(withAuth: true))
        .timeout(const Duration(seconds: 15));

    final body = _decodeJsonBody(response.body);

    if (response.statusCode == 200 && body['success'] == true && body['data'] is Map) {
      return WasteReportModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
    }

    throw ApiException(
      _extractErrorMessage(body, defaultMsg: 'Detail laporan tidak ditemukan.'),
      statusCode: response.statusCode,
    );
  }

  // DELETE /api/reports/{id}
  Future<void> deleteReport(int id) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/reports/$id');
    final response = await _client
        .delete(uri, headers: await _buildHeaders(withAuth: true))
        .timeout(const Duration(seconds: 15));

    final body = _decodeJsonBody(response.body);

    if (response.statusCode == 200 && body['success'] == true) {
      return;
    }

    throw ApiException(
      _extractErrorMessage(
        body,
        defaultMsg: 'Laporan tidak dapat dihapus.',
      ),
      statusCode: response.statusCode,
    );
  }

  // POST /api/reports/detect (Proxy ke FastAPI /predict)
  Future<Map<String, dynamic>> detectWasteCategory(File photoFile) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/reports/detect');
    final request = http.MultipartRequest('POST', uri);
    request.headers.addAll(await _buildHeaders(withAuth: true, isJson: false));
    request.files.add(await http.MultipartFile.fromPath('photo', photoFile.path));

    final streamedResponse = await _client.send(request).timeout(const Duration(seconds: 25));
    final response = await http.Response.fromStream(streamedResponse);
    final body = _decodeJsonBody(response.body);

    if (response.statusCode == 200 && body['success'] == true && body['data'] is Map) {
      return Map<String, dynamic>.from(body['data'] as Map);
    }

    throw ApiException(
      _extractErrorMessage(body, defaultMsg: 'Gagal mendeteksi kategori sampah via AI.'),
      statusCode: response.statusCode,
    );
  }

  // POST /api/reports
  Future<WasteReportModel> createReport({
    required File photoFile,
    required double latitude,
    required double longitude,
    required String description,
    String? address,
    String? wasteType,
    double? confidenceScore,
  }) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/reports');
    final request = http.MultipartRequest('POST', uri);
    request.headers.addAll(await _buildHeaders(withAuth: true, isJson: false));

    request.fields['latitude'] = latitude.toString();
    request.fields['longitude'] = longitude.toString();
    request.fields['description'] = description.trim();

    if (address != null && address.trim().isNotEmpty) {
      request.fields['address'] = address.trim();
    }
    if (wasteType != null && wasteType.isNotEmpty) {
      request.fields['waste_type'] = wasteType;
    }
    if (confidenceScore != null) {
      request.fields['confidence_score'] = confidenceScore.toString();
    }

    request.files.add(await http.MultipartFile.fromPath('photo', photoFile.path));

    final streamedResponse = await _client.send(request).timeout(const Duration(seconds: 25));
    final response = await http.Response.fromStream(streamedResponse);
    final body = _decodeJsonBody(response.body);

    if ((response.statusCode == 201 || response.statusCode == 200) &&
        body['success'] == true &&
        body['data'] is Map) {
      return WasteReportModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
    }

    throw ApiException(
      _extractErrorMessage(body, defaultMsg: 'Gagal menyimpan laporan sampah.'),
      statusCode: response.statusCode,
    );
  }

  Map<String, dynamic> _decodeJsonBody(String rawBody) {
    try {
      final decoded = jsonDecode(rawBody);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      return <String, dynamic>{};
    } catch (_) {
      return <String, dynamic>{};
    }
  }

  String _extractErrorMessage(Map<String, dynamic> body, {required String defaultMsg}) {
    if (body['errors'] is Map<String, dynamic>) {
      final errors = body['errors'] as Map<String, dynamic>;
      for (final entry in errors.values) {
        if (entry is List && entry.isNotEmpty) {
          return entry.first.toString();
        }
      }
    }
    if (body['message'] is String && (body['message'] as String).isNotEmpty) {
      return body['message'] as String;
    }
    return defaultMsg;
  }
}
