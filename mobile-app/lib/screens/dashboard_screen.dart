import 'package:flutter/material.dart';

import '../models/user_model.dart';
import '../models/waste_report_model.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../utils/date_formatter.dart';
import '../widgets/status_badge.dart';
import 'create_report_screen.dart';
import 'report_detail_screen.dart';
import 'report_history_screen.dart';

class DashboardScreen extends StatefulWidget {
  final UserModel user;
  final ApiService apiService;
  final VoidCallback onLogout;

  const DashboardScreen({
    super.key,
    required this.user,
    required this.apiService,
    required this.onLogout,
  });

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  bool _isLoading = true;
  String? _errorMessage;

  int _totalReports = 0;
  int _pendingCount = 0;
  int _inProgressCount = 0;
  int _completedCount = 0;
  int _rejectedCount = 0;
  List<WasteReportModel> _recentReports = const [];

  @override
  void initState() {
    super.initState();
    _loadDashboardData();
  }

  Future<void> _loadDashboardData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final summary = await widget.apiService.getDashboardSummary();
      if (!mounted) return;

      final statusCounts = summary['status_counts'] is Map
          ? Map<String, dynamic>.from(summary['status_counts'] as Map)
          : <String, dynamic>{};
      final recentRaw = (summary['recent_reports'] as List<dynamic>?) ?? const [];

      setState(() {
        _totalReports = WasteReportModel.parseInt(summary['total_reports']);
        _pendingCount = WasteReportModel.parseInt(
          statusCounts['Pending'] ?? statusCounts['pending'],
        );
        _inProgressCount = WasteReportModel.parseInt(
          statusCounts['In Progress'] ?? statusCounts['in_progress'],
        );
        _completedCount = WasteReportModel.parseInt(
          statusCounts['Completed'] ?? statusCounts['completed'],
        );
        _rejectedCount = WasteReportModel.parseInt(
          statusCounts['Rejected'] ?? statusCounts['rejected'],
        );
        _recentReports = recentRaw
            .whereType<Map>()
            .map((item) => WasteReportModel.fromJson(Map<String, dynamic>.from(item)))
            .toList();
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 401) {
        widget.onLogout();
        return;
      }
      setState(() => _errorMessage = e.message);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _errorMessage =
            'Tidak dapat memuat data dashboard. Pastikan server Laravel aktif di jaringan lokal.';
      });
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _openCreateReport() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute<bool>(
        builder: (_) => CreateReportScreen(
          apiService: widget.apiService,
          onUnauthorized: widget.onLogout,
        ),
      ),
    );
    if (created == true && mounted) {
      _loadDashboardData();
    }
  }

  void _openReportHistory({String initialStatus = 'All'}) {
    Navigator.of(context)
        .push<void>(
          MaterialPageRoute<void>(
            builder: (_) => ReportHistoryScreen(
              apiService: widget.apiService,
              currentUser: widget.user,
              initialStatus: initialStatus,
              onUnauthorized: widget.onLogout,
            ),
          ),
        )
        .then((_) {
          if (mounted) _loadDashboardData();
        });
  }

  Future<void> _openReportDetail(WasteReportModel report) async {
    final deleted = await Navigator.of(context).push<bool>(
      MaterialPageRoute<bool>(
        builder: (_) => ReportDetailScreen(
          apiService: widget.apiService,
          reportId: report.id,
          initialReport: report,
          currentUser: widget.user,
          onUnauthorized: widget.onLogout,
        ),
      ),
    );
    if (deleted == true && mounted) {
      _loadDashboardData();
    }
  }

  Future<void> _confirmLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Keluar dari Akun?'),
        content: const Text(
          'Sesi Anda akan diakhiri dan Anda perlu masuk kembali untuk mengirim laporan.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.dangerRed,
              minimumSize: const Size(100, 44),
            ),
            child: const Text('Keluar'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      widget.onLogout();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Smart Waste Management',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        actions: [
          IconButton(
            onPressed: _loadDashboardData,
            tooltip: 'Muat Ulang Data',
            icon: const Icon(Icons.refresh),
          ),
          IconButton(
            onPressed: _confirmLogout,
            tooltip: 'Keluar Sesi (Logout)',
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openCreateReport,
        backgroundColor: AppTheme.forestPrimary,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add_a_photo_outlined),
        label: const Text(
          'Buat Laporan',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _loadDashboardData,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 92),
          children: [
            // User Greeting Card
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: AppTheme.forestDark,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 22,
                        backgroundColor: AppTheme.forestPrimary,
                        child: Text(
                          widget.user.name.isNotEmpty
                              ? widget.user.name[0].toUpperCase()
                              : 'U',
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                            fontSize: 18,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Halo, ${widget.user.name}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 17,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              widget.user.email,
                              style: const TextStyle(
                                color: Color(0xFFA7C4B5),
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          'Total: $_totalReports',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: _openCreateReport,
                          icon: const Icon(Icons.camera_alt_outlined, size: 16),
                          label: const Text(
                            'Laporkan Sampah',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(fontSize: 13),
                          ),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppTheme.forestPrimary,
                            padding: const EdgeInsets.symmetric(horizontal: 8),
                            minimumSize: const Size.fromHeight(44),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _openReportHistory(),
                          icon: const Icon(Icons.history, size: 16),
                          label: const Text(
                            'Riwayat Laporan',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(fontSize: 13),
                          ),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.white,
                            side: const BorderSide(color: Colors.white38),
                            padding: const EdgeInsets.symmetric(horizontal: 8),
                            minimumSize: const Size.fromHeight(44),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),

            // Status Statistics Section
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Expanded(
                  child: Text(
                    'Statistik Status Laporan Anda',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.inkPrimary,
                    ),
                  ),
                ),
                TextButton(
                  onPressed: () => _openReportHistory(),
                  child: const Text('Lihat Semua'),
                ),
              ],
            ),
            const SizedBox(height: 6),

            GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              childAspectRatio: 1.45,
              children: [
                _StatusSummaryCard(
                  label: 'Pending',
                  subtitle: 'Menunggu verifikasi',
                  count: _pendingCount,
                  bgColor: const Color(0xFFFEF3C7),
                  textColor: const Color(0xFF78350F),
                  borderColor: const Color(0xFFF59E0B),
                  icon: Icons.schedule_outlined,
                  onTap: () => _openReportHistory(initialStatus: 'Pending'),
                ),
                _StatusSummaryCard(
                  label: 'In Progress',
                  subtitle: 'Sedang ditangani',
                  count: _inProgressCount,
                  bgColor: const Color(0xFFDBEAFE),
                  textColor: const Color(0xFF1E3A8A),
                  borderColor: const Color(0xFF3B82F6),
                  icon: Icons.local_shipping_outlined,
                  onTap: () => _openReportHistory(initialStatus: 'In Progress'),
                ),
                _StatusSummaryCard(
                  label: 'Completed',
                  subtitle: 'Selesai diangkut',
                  count: _completedCount,
                  bgColor: const Color(0xFFDCFCE7),
                  textColor: const Color(0xFF14532D),
                  borderColor: const Color(0xFF22C55E),
                  icon: Icons.check_circle_outline,
                  onTap: () => _openReportHistory(initialStatus: 'Completed'),
                ),
                _StatusSummaryCard(
                  label: 'Rejected',
                  subtitle: 'Laporan ditolak',
                  count: _rejectedCount,
                  bgColor: const Color(0xFFFEE2E2),
                  textColor: const Color(0xFF7F1D1D),
                  borderColor: const Color(0xFFEF4444),
                  icon: Icons.cancel_outlined,
                  onTap: () => _openReportHistory(initialStatus: 'Rejected'),
                ),
              ],
            ),
            const SizedBox(height: 22),

            // Recent Reports Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Expanded(
                  child: Text(
                    'Laporan Terbaru Anda',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.inkPrimary,
                    ),
                  ),
                ),
                TextButton(
                  onPressed: () => _openReportHistory(),
                  child: const Text('Filter & Riwayat'),
                ),
              ],
            ),
            const SizedBox(height: 6),

            if (_isLoading)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(28),
                  child: Column(
                    children: [
                      CircularProgressIndicator(),
                      SizedBox(height: 12),
                      Text(
                        'Memuat ringkasan laporan dari server...',
                        style: TextStyle(color: AppTheme.inkMuted, fontSize: 13),
                      ),
                    ],
                  ),
                ),
              )
            else if (_errorMessage != null)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.error_outline, color: AppTheme.dangerRed),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              _errorMessage!,
                              style: const TextStyle(
                                color: AppTheme.dangerRed,
                                fontWeight: FontWeight.w600,
                                fontSize: 13,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      OutlinedButton.icon(
                        onPressed: _loadDashboardData,
                        icon: const Icon(Icons.refresh),
                        label: const Text('Coba Muat Ulang'),
                      ),
                    ],
                  ),
                ),
              )
            else if (_recentReports.isEmpty)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    children: [
                      const Icon(
                        Icons.inbox_outlined,
                        size: 40,
                        color: AppTheme.inkMuted,
                      ),
                      const SizedBox(height: 10),
                      const Text(
                        'Belum ada laporan sampah yang dikirimkan',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 15,
                          color: AppTheme.inkPrimary,
                        ),
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Ambil foto temuan sampah menggunakan kamera atau galeri untuk mendeteksi jenis sampah dengan AI.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: AppTheme.inkMuted, fontSize: 13),
                      ),
                      const SizedBox(height: 14),
                      ElevatedButton.icon(
                        onPressed: _openCreateReport,
                        icon: const Icon(Icons.add_a_photo_outlined),
                        label: const Text('Buat Laporan Pertama'),
                      ),
                    ],
                  ),
                ),
              )
            else
              ..._recentReports.map(
                (report) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Card(
                    clipBehavior: Clip.antiAlias,
                    child: InkWell(
                      onTap: () => _openReportDetail(report),
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(8),
                              child: SizedBox(
                                width: 70,
                                height: 70,
                                child: report.photoUrl.isNotEmpty
                                    ? Image.network(
                                        report.photoUrl,
                                        headers: const {'ngrok-skip-browser-warning': 'true'},
                                        fit: BoxFit.cover,
                                        errorBuilder: (_, _, _) => Container(
                                          color: AppTheme.canvasBg,
                                          child: const Icon(
                                            Icons.image_outlined,
                                            color: AppTheme.inkMuted,
                                          ),
                                        ),
                                      )
                                    : Container(
                                        color: AppTheme.canvasBg,
                                        child: const Icon(
                                          Icons.image_outlined,
                                          color: AppTheme.inkMuted,
                                        ),
                                      ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Wrap(
                                    spacing: 6,
                                    runSpacing: 6,
                                    crossAxisAlignment: WrapCrossAlignment.center,
                                    children: [
                                      WasteTypeBadge(
                                        wasteType: report.wasteType,
                                        confidenceScore: report.confidenceScore,
                                      ),
                                      StatusBadge(status: report.status),
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    report.description,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w500,
                                      color: AppTheme.inkPrimary,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    report.createdAt != null
                                        ? AppDateFormatter.formatWithDay(
                                            report.createdAt!.toLocal(),
                                          )
                                        : 'Laporan #${report.id}',
                                    style: const TextStyle(
                                      fontSize: 11,
                                      color: AppTheme.inkMuted,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const Icon(
                              Icons.chevron_right,
                              color: AppTheme.inkMuted,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _StatusSummaryCard extends StatelessWidget {
  final String label;
  final String subtitle;
  final int count;
  final Color bgColor;
  final Color textColor;
  final Color borderColor;
  final IconData icon;
  final VoidCallback onTap;

  const _StatusSummaryCard({
    required this.label,
    required this.subtitle,
    required this.count,
    required this.bgColor,
    required this.textColor,
    required this.borderColor,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppTheme.borderSubtle),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Flexible(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                      decoration: BoxDecoration(
                        color: bgColor,
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: borderColor),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(icon, size: 13, color: textColor),
                          const SizedBox(width: 4),
                          Flexible(
                            child: Text(
                              label,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                color: textColor,
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    '$count',
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.inkPrimary,
                    ),
                  ),
                ],
              ),
              Text(
                subtitle,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 12,
                  color: AppTheme.inkMuted,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
