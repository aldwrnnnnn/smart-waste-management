import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mobile_app/models/user_model.dart';
import 'package:mobile_app/models/waste_report_model.dart';
import 'package:mobile_app/screens/create_report_screen.dart';
import 'package:mobile_app/screens/dashboard_screen.dart';
import 'package:mobile_app/screens/login_screen.dart';
import 'package:mobile_app/screens/register_screen.dart';
import 'package:mobile_app/screens/report_detail_screen.dart';
import 'package:mobile_app/screens/report_history_screen.dart';
import 'package:mobile_app/services/api_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  const commonSizes = [
    Size(320, 568), // iPhone SE 1st gen / small screen
    Size(360, 640), // Standard Android compact
    Size(360, 780),
    Size(375, 667), // iPhone 8 / SE 2nd
    Size(375, 812), // iPhone X
    Size(390, 844), // iPhone 12/13/14
    Size(393, 852), // iPhone 15/16
    Size(411, 731), // Pixel 2
    Size(412, 915), // Pixel 7
    Size(428, 926), // iPhone Plus/Pro Max
  ];

  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  for (final size in commonSizes) {
    testWidgets('Verify no layout overflow on size ${size.width}x${size.height}', (tester) async {
      tester.view.physicalSize = Size(size.width * 2.0, size.height * 2.0);
      tester.view.devicePixelRatio = 2.0;
      addTearDown(() {
        tester.view.resetPhysicalSize();
        tester.view.resetDevicePixelRatio();
      });

      final mockClient = MockClient((request) async {
        if (request.url.path.endsWith('/dashboard-summary')) {
          return http.Response(
            jsonEncode({
              'success': true,
              'data': {
                'total_reports': 4,
                'status_counts': {
                  'Pending': 1,
                  'In Progress': 1,
                  'Completed': 2,
                  'Rejected': 0,
                },
                'recent_reports': [
                  {
                    'id': 10,
                    'user_id': 2,
                    'photo_path': 'reports/sample.jpg',
                    'photo_url': '',
                    'waste_type': 'Paper Waste',
                    'confidence_score': 0.92,
                    'latitude': -6.2,
                    'longitude': 106.8,
                    'description': 'Kardus menumpuk di dekat taman kota.',
                    'status': 'Completed',
                  },
                ],
              },
            }),
            200,
          );
        }
        if (request.url.path.endsWith('/reports')) {
          return http.Response(
            jsonEncode({
              'success': true,
              'data': [],
            }),
            200,
          );
        }
        return http.Response('{}', 200);
      });
      final apiService = ApiService(client: mockClient);
      const user = UserModel(id: 1, name: 'Budi Santoso', email: 'user@waste.com', role: 'user');

      // 1. LoginScreen
      await tester.pumpWidget(MaterialApp(home: LoginScreen(apiService: apiService, onAuthenticated: (_) {})));
      await tester.pump();

      // 2. RegisterScreen
      await tester.pumpWidget(MaterialApp(home: RegisterScreen(apiService: apiService, onAuthenticated: (_) {})));
      await tester.pump();

      // 3. DashboardScreen
      await tester.pumpWidget(MaterialApp(home: DashboardScreen(user: user, apiService: apiService, onLogout: () {})));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 100));

      // 4. CreateReportScreen
      await tester.pumpWidget(MaterialApp(home: CreateReportScreen(apiService: apiService)));
      await tester.pump();

      // 5. ReportHistoryScreen
      await tester.pumpWidget(MaterialApp(home: ReportHistoryScreen(apiService: apiService)));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 100));

      // 6. ReportDetailScreen
      final sampleReport = WasteReportModel(
        id: 42,
        userId: 2,
        photoPath: 'reports/sample.jpg',
        photoUrl: '',
        wasteType: 'Plastic Waste',
        confidenceScore: 0.944,
        latitude: -6.208763,
        longitude: 106.845599,
        address: 'Jl. Jenderal Sudirman No. 10, Jakarta Pusat',
        description: 'Tumpukan botol plastik di dekat halte bus.',
        status: 'In Progress',
        adminNotes: 'Tim kebersihan sedang menuju lokasi.',
        createdAt: DateTime(2026, 11, 1, 10, 30),
      );
      await tester.pumpWidget(MaterialApp(home: ReportDetailScreen(apiService: apiService, reportId: 42, initialReport: sampleReport)));
      await tester.pump();
    });
  }
}
