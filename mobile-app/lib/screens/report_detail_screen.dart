import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../models/user_model.dart';
import '../models/waste_report_model.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../utils/date_formatter.dart';
import '../widgets/status_badge.dart';

class ReportDetailScreen extends StatefulWidget {
  final ApiService apiService;
  final int reportId;
  final WasteReportModel? initialReport;
  final UserModel? currentUser;
  final VoidCallback? onUnauthorized;

  const ReportDetailScreen({
    super.key,
    required this.apiService,
    required this.reportId,
    this.initialReport,
    this.currentUser,
    this.onUnauthorized,
  });

  @override
  State<ReportDetailScreen> createState() => _ReportDetailScreenState();
}

class _ReportDetailScreenState extends State<ReportDetailScreen> {
  WasteReportModel? _report;
  UserModel? _currentUser;
  bool _isLoading = true;
  bool _isDeleting = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _report = widget.initialReport;
    _currentUser = widget.currentUser;
    if (_currentUser == null) {
      _loadCurrentUser();
    }
    _fetchDetail();
  }

  Future<void> _loadCurrentUser() async {
    try {
      final savedUser = await widget.apiService.getSavedUser();
      if (mounted && savedUser != null) {
        setState(() => _currentUser = savedUser);
      }
    } catch (_) {}
  }

  @override
  void didUpdateWidget(covariant ReportDetailScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.currentUser != widget.currentUser && widget.currentUser != null) {
      _currentUser = widget.currentUser;
    }
    if (oldWidget.reportId != widget.reportId ||
        oldWidget.initialReport != widget.initialReport) {
      _report = widget.initialReport;
      _fetchDetail();
    }
  }

  Future<void> _fetchDetail() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final fresh = await widget.apiService.getReportDetail(widget.reportId);
      if (!mounted) return;
      setState(() => _report = fresh);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 401) {
        await widget.apiService.clearAuthSession();
        if (!mounted) return;
        widget.onUnauthorized?.call();
        Navigator.of(context).popUntil((route) => route.isFirst);
        return;
      }
      if (_report == null) {
        setState(() => _errorMessage = e.message);
      }
    } catch (_) {
      if (!mounted) return;
      if (_report == null) {
        setState(() => _errorMessage = 'Gagal memuat detail laporan dari server.');
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _copyCoordinates(WasteReportModel report) {
    final coords =
        '${report.latitude.toStringAsFixed(8)}, ${report.longitude.toStringAsFixed(8)}';
    Clipboard.setData(ClipboardData(text: coords));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Koordinat GPS ($coords) disalin ke clipboard.'),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  Future<void> _confirmAndDeleteReport(WasteReportModel report) async {
    if (!report.canBeDeletedBy(_currentUser) || _isDeleting) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Hapus Laporan #${report.id}?'),
        content: Text(
          report.isPending
              ? 'Laporan yang masih berstatus Pending ini akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.'
              : 'Laporan ini akan dihapus secara permanen dari sistem oleh Admin. Tindakan ini tidak dapat dibatalkan.',
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
              minimumSize: const Size(110, 44),
            ),
            child: const Text('Ya, Hapus'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _isDeleting = true);

    try {
      await widget.apiService.deleteReport(report.id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Laporan #${report.id} berhasil dihapus.'),
          backgroundColor: AppTheme.forestPrimary,
        ),
      );
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 401) {
        await widget.apiService.clearAuthSession();
        if (!mounted) return;
        widget.onUnauthorized?.call();
        Navigator.of(context).popUntil((route) => route.isFirst);
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.message),
          backgroundColor: AppTheme.dangerRed,
        ),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Gagal menghapus laporan. Periksa koneksi jaringan Anda.'),
          backgroundColor: AppTheme.dangerRed,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isDeleting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final report = _report;

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Detail Laporan #${widget.reportId}',
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        actions: [
          if (report != null && report.canBeDeletedBy(_currentUser))
            IconButton(
              onPressed: _isDeleting ? null : () => _confirmAndDeleteReport(report),
              tooltip: 'Hapus Laporan',
              icon: const Icon(Icons.delete_outline),
            ),
          IconButton(
            onPressed: _fetchDetail,
            tooltip: 'Muat Ulang Detail',
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: report == null
          ? _isLoading
              ? const Center(child: CircularProgressIndicator())
              : Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Card(
                      child: Padding(
                        padding: const EdgeInsets.all(20),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(
                              Icons.error_outline,
                              size: 40,
                              color: AppTheme.dangerRed,
                            ),
                            const SizedBox(height: 10),
                            Text(
                              _errorMessage ?? 'Data laporan tidak ditemukan.',
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                color: AppTheme.dangerRed,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            const SizedBox(height: 14),
                            ElevatedButton.icon(
                              onPressed: _fetchDetail,
                              icon: const Icon(Icons.refresh),
                              label: const Text('Coba Lagi'),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                )
          : RefreshIndicator(
              onRefresh: _fetchDetail,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Photo Card
                  Card(
                    clipBehavior: Clip.antiAlias,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        SizedBox(
                          height: 240,
                          child: report.photoUrl.isNotEmpty
                              ? Image.network(
                                  report.photoUrl,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, _, _) => Container(
                                    color: AppTheme.canvasBg,
                                    alignment: Alignment.center,
                                    child: const Column(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Icon(
                                          Icons.broken_image_outlined,
                                          size: 42,
                                          color: AppTheme.inkMuted,
                                        ),
                                        SizedBox(height: 6),
                                        Text(
                                          'Gambar tidak dapat dimuat',
                                          style: TextStyle(
                                            color: AppTheme.inkMuted,
                                            fontSize: 12,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                )
                              : Container(
                                  color: AppTheme.canvasBg,
                                  alignment: Alignment.center,
                                  child: const Icon(
                                    Icons.image_outlined,
                                    size: 48,
                                    color: AppTheme.inkMuted,
                                  ),
                                ),
                        ),
                        Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                children: [
                                  WasteTypeBadge(
                                    wasteType: report.wasteType,
                                    confidenceScore: report.confidenceScore,
                                  ),
                                  StatusBadge(status: report.status),
                                ],
                              ),
                              const SizedBox(height: 14),
                              const Text(
                                'Deskripsi Laporan',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.bold,
                                  color: AppTheme.inkMuted,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                report.description,
                                style: const TextStyle(
                                  fontSize: 15,
                                  color: AppTheme.inkPrimary,
                                  height: 1.4,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Admin Notes Section
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(
                                Icons.assignment_turned_in_outlined,
                                size: 18,
                                color: AppTheme.forestPrimary,
                              ),
                              const SizedBox(width: 8),
                              const Expanded(
                                child: Text(
                                  'Catatan Tindak Lanjut Admin',
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.bold,
                                    color: AppTheme.inkPrimary,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: AppTheme.canvasBg,
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: AppTheme.borderSubtle),
                            ),
                            child: Text(
                              (report.adminNotes != null &&
                                      report.adminNotes!.trim().isNotEmpty)
                                  ? report.adminNotes!
                                  : 'Belum ada catatan dari Administrator untuk laporan ini.',
                              style: TextStyle(
                                fontSize: 13,
                                color: (report.adminNotes != null &&
                                        report.adminNotes!.trim().isNotEmpty)
                                    ? AppTheme.inkPrimary
                                    : AppTheme.inkMuted,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // GPS Coordinates & Metadata Card
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Expanded(
                                child: Text(
                                  'Lokasi & Koordinat GPS',
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.bold,
                                    color: AppTheme.inkPrimary,
                                  ),
                                ),
                              ),
                              TextButton.icon(
                                onPressed: () => _copyCoordinates(report),
                                icon: const Icon(Icons.copy, size: 16),
                                label: const Text('Salin GPS'),
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          _DetailRow(
                            label: 'Alamat / Patokan',
                            value: report.address?.isNotEmpty == true
                                ? report.address!
                                : 'Tidak dicantumkan',
                          ),
                          _DetailRow(
                            label: 'Latitude',
                            value: report.latitude.toStringAsFixed(8),
                          ),
                          _DetailRow(
                            label: 'Longitude',
                            value: report.longitude.toStringAsFixed(8),
                          ),
                          _DetailRow(
                            label: 'Kategori AI',
                            value: report.wasteType,
                          ),
                          _DetailRow(
                            label: 'Confidence Score',
                            value: report.confidenceScore != null
                                ? '${(report.confidenceScore! * 100).toStringAsFixed(2)}%'
                                : '-',
                          ),
                          if (report.createdAt != null)
                            _DetailRow(
                              label: 'Waktu Lapor',
                              value: AppDateFormatter.formatWithDay(
                                report.createdAt!.toLocal(),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ),
                  if (report.canBeDeletedBy(_currentUser)) ...[
                    const SizedBox(height: 14),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            OutlinedButton.icon(
                              onPressed: _isDeleting
                                  ? null
                                  : () => _confirmAndDeleteReport(report),
                              icon: _isDeleting
                                  ? const SizedBox(
                                      width: 16,
                                      height: 16,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : const Icon(
                                      Icons.delete_outline,
                                      color: AppTheme.dangerRed,
                                    ),
                              label: Text(
                                _isDeleting
                                    ? 'Menghapus Laporan...'
                                    : 'Hapus Laporan Ini',
                                style: const TextStyle(
                                  color: AppTheme.dangerRed,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              style: OutlinedButton.styleFrom(
                                side: const BorderSide(
                                  color: Color(0xFFFCA5A5),
                                ),
                                backgroundColor: const Color(0xFFFEF2F2),
                                minimumSize: const Size.fromHeight(48),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DetailRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 13,
                color: AppTheme.inkMuted,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 13,
                color: AppTheme.inkPrimary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
