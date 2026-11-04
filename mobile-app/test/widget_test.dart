import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mobile_app/config/api_config.dart';
import 'package:mobile_app/main.dart';
import 'package:mobile_app/models/user_model.dart';
import 'package:mobile_app/models/waste_report_model.dart';
import 'package:mobile_app/screens/dashboard_screen.dart';
import 'package:mobile_app/screens/report_detail_screen.dart';
import 'package:mobile_app/services/api_service.dart';
import 'package:mobile_app/widgets/status_badge.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  testWidgets('LoginScreen renders and navigates to RegisterScreen', (WidgetTester tester) async {
    SharedPreferences.setMockInitialValues({});

    await tester.pumpWidget(const SmartWasteApp());
    await tester.pumpAndSettle();

    expect(ApiConfig.baseUrl, contains(':8000/api'));
    expect(find.text('Masuk ke Akun Pelapor'), findsOneWidget);
    expect(find.text('Masuk ke Aplikasi'), findsOneWidget);

    await tester.ensureVisible(find.text('Daftar Sekarang'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Daftar Sekarang'));
    await tester.pumpAndSettle();

    expect(find.text('Registrasi Warga Pelapor'), findsOneWidget);
    expect(find.widgetWithText(ElevatedButton, 'Daftarkan Akun'), findsOneWidget);
  });

  test('UserModel and WasteReportModel safely parse nullable and stringified JSON fields', () {
    final user = UserModel.fromJson({
      'id': '7',
      'name': null,
      'email': 'budi@waste.com',
      'role': null,
    });
    expect(user.id, equals(7));
    expect(user.name, equals(''));
    expect(user.role, equals('user'));

    final report = WasteReportModel.fromJson({
      'id': '15',
      'user_id': null,
      'user': {'name': 'Budi Santoso'},
      'photo_path': 'reports/sample.jpg',
      'photo_url': 'http://127.0.0.1:8000/storage/reports/sample.jpg',
      'waste_type': 'Organic Waste',
      'confidence_score': '0.9540',
      'latitude': '-6.20876340',
      'longitude': '106.84559900',
      'address': null,
      'description': null,
      'status': null,
      'admin_notes': null,
      'created_at': '2026-11-01T12:00:00.000000Z',
    });

    expect(report.id, equals(15));
    expect(report.userId, equals(0));
    expect(report.userName, equals('Budi Santoso'));
    expect(report.photoUrl, equals('${ApiConfig.hostBaseUrl}/storage/reports/sample.jpg'));
    expect(report.confidenceScore, closeTo(0.954, 0.0001));
    expect(report.latitude, closeTo(-6.20876340, 0.000001));
    expect(report.longitude, closeTo(106.84559900, 0.000001));
    expect(report.status, equals('Pending'));

    const regularUser = UserModel(id: 2, name: 'Warga', email: 'warga@waste.com', role: 'user');
    const otherUser = UserModel(id: 99, name: 'Warga Lain', email: 'lain@waste.com', role: 'user');
    const adminUser = UserModel(id: 1, name: 'Admin', email: 'admin@waste.com', role: 'admin');
    expect(regularUser.isAdmin, isFalse);
    expect(adminUser.isAdmin, isTrue);

    // Report with userId 0 (from null): regularUser (id: 2) and null cannot delete, admin can delete
    expect(report.canBeDeletedBy(regularUser), isFalse);
    expect(report.canBeDeletedBy(adminUser), isTrue);
    expect(report.canBeDeletedBy(null), isFalse);

    final ownedPendingReport = WasteReportModel.fromJson({
      'id': 17,
      'user_id': 2,
      'photo_path': 'reports/pending.jpg',
      'photo_url': '',
      'waste_type': 'Organic Waste',
      'latitude': -6.2,
      'longitude': 106.8,
      'description': 'Masih menunggu tindak lanjut',
      'status': 'Pending',
    });
    // Pending: owner and admin can delete; non-owner and null cannot delete
    expect(ownedPendingReport.canBeDeletedBy(regularUser), isTrue);
    expect(ownedPendingReport.canBeDeletedBy(otherUser), isFalse);
    expect(ownedPendingReport.canBeDeletedBy(adminUser), isTrue);
    expect(ownedPendingReport.canBeDeletedBy(null), isFalse);

    final completedReport = WasteReportModel.fromJson({
      'id': 16,
      'user_id': 2,
      'photo_path': 'reports/done.jpg',
      'photo_url': '',
      'waste_type': 'Plastic Waste',
      'latitude': -6.2,
      'longitude': 106.8,
      'description': 'Sudah selesai dibersihkan',
      'status': 'Completed',
    });
    // Completed: regular user CANNOT delete, admin CAN delete
    expect(completedReport.canBeDeletedBy(regularUser), isFalse);
    expect(completedReport.canBeDeletedBy(adminUser), isTrue);
    expect(completedReport.canBeDeletedBy(null), isFalse);
  });

  testWidgets('DashboardScreen renders summary cards and recent reports from API', (WidgetTester tester) async {
    SharedPreferences.setMockInitialValues({'auth_access_token': 'test_token'});

    final mockClient = MockClient((http.Request request) async {
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
                  'photo_url': 'http://127.0.0.1:8000/storage/reports/sample.jpg',
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
      return http.Response('{}', 404);
    });

    final apiService = ApiService(client: mockClient);
    const demoUser = UserModel(
      id: 2,
      name: 'Budi Santoso',
      email: 'user@waste.com',
      role: 'user',
    );

    await tester.pumpWidget(
      MaterialApp(
        home: DashboardScreen(
          user: demoUser,
          apiService: apiService,
          onLogout: () {},
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Halo, Budi Santoso'), findsOneWidget);
    expect(find.text('Total: 4'), findsOneWidget);

    await tester.drag(find.byType(ListView), const Offset(0, -350));
    await tester.pumpAndSettle();

    expect(find.text('Kardus menumpuk di dekat taman kota.'), findsOneWidget);
  });

  testWidgets('StatusBadge, WasteTypeBadge, and ReportDetailScreen render report data', (WidgetTester tester) async {
    SharedPreferences.setMockInitialValues({});
    final sampleReport = WasteReportModel(
      id: 42,
      userId: 2,
      photoPath: 'reports/sample.jpg',
      photoUrl: 'http://10.0.2.2:8000/storage/reports/sample.jpg',
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

    final mockClient = MockClient((_) async => http.Response('{}', 404));

    await tester.pumpWidget(
      MaterialApp(
        home: ReportDetailScreen(
          apiService: ApiService(client: mockClient),
          reportId: 42,
          initialReport: sampleReport,
        ),
      ),
    );
    await tester.pump();

    expect(find.text('Detail Laporan #42'), findsOneWidget);
    expect(find.byType(StatusBadge), findsOneWidget);
    expect(find.byType(WasteTypeBadge), findsOneWidget);
    expect(find.text('Tumpukan botol plastik di dekat halte bus.'), findsOneWidget);
    expect(find.text('Tim kebersihan sedang menuju lokasi.'), findsOneWidget);
    expect(find.text('Hapus Laporan Ini'), findsNothing);
    expect(find.byTooltip('Hapus Laporan'), findsNothing);
    expect(find.textContaining('Laporan ini tidak dapat dihapus'), findsNothing);

    final pendingReport = WasteReportModel(
      id: 43,
      userId: 2,
      photoPath: 'reports/sample.jpg',
      photoUrl: '',
      wasteType: 'Organic Waste',
      confidenceScore: 0.91,
      latitude: -6.208763,
      longitude: 106.845599,
      address: 'Jl. Kebon Sirih No. 10',
      description: 'Sampah daun masih berstatus pending.',
      status: 'Pending',
      createdAt: DateTime(2026, 11, 1, 11, 0),
    );

    const ownerUser = UserModel(id: 2, name: 'Warga', email: 'warga@waste.com', role: 'user');
    await tester.pumpWidget(
      MaterialApp(
        home: ReportDetailScreen(
          key: const ValueKey(43),
          apiService: ApiService(client: mockClient),
          reportId: 43,
          initialReport: pendingReport,
          currentUser: ownerUser,
        ),
      ),
    );
    await tester.pump();
    expect(find.byTooltip('Hapus Laporan'), findsOneWidget);
    await tester.drag(find.byType(ListView), const Offset(0, -600));
    await tester.pump();

    expect(find.text('Hapus Laporan Ini'), findsOneWidget);

    // Admin can delete non-pending reports (In Progress / Completed)
    const adminUser = UserModel(id: 1, name: 'Admin', email: 'admin@waste.com', role: 'admin');
    await tester.pumpWidget(
      MaterialApp(
        home: ReportDetailScreen(
          key: const ValueKey('admin_in_progress'),
          apiService: ApiService(client: mockClient),
          reportId: 42,
          initialReport: sampleReport, // 'In Progress'
          currentUser: adminUser,
        ),
      ),
    );
    await tester.pump();

    // Admin sees delete action in AppBar
    expect(find.byTooltip('Hapus Laporan'), findsOneWidget);
    await tester.drag(find.byType(ListView), const Offset(0, -600));
    await tester.pump();
    // Admin sees bottom delete button
    expect(find.text('Hapus Laporan Ini'), findsOneWidget);
  });
}
