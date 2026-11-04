import 'package:flutter/material.dart';

import '../models/user_model.dart';
import '../models/waste_report_model.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../utils/date_formatter.dart';
import '../widgets/status_badge.dart';
import 'create_report_screen.dart';
import 'report_detail_screen.dart';

class ReportHistoryScreen extends StatefulWidget {
  final ApiService apiService;
  final UserModel? currentUser;
  final String initialStatus;
  final VoidCallback? onUnauthorized;

  const ReportHistoryScreen({
    super.key,
    required this.apiService,
    this.currentUser,
    this.initialStatus = 'All',
    this.onUnauthorized,
  });

  @override
  State<ReportHistoryScreen> createState() => _ReportHistoryScreenState();
}

class _ReportHistoryScreenState extends State<ReportHistoryScreen> {
  static const List<String> _statusFilters = [
    'All',
    'Pending',
    'In Progress',
    'Completed',
    'Rejected',
  ];

  late String _selectedStatus;
  final _searchController = TextEditingController();

  UserModel? _currentUser;
  bool _isLoading = true;
  bool _isLoadingMore = false;
  int _currentPage = 1;
  bool _hasMorePages = false;
  String? _errorMessage;
  List<WasteReportModel> _reports = const [];

  @override
  void initState() {
    super.initState();
    _currentUser = widget.currentUser;
    if (_currentUser == null) {
      _loadCurrentUser();
    }
    _selectedStatus = _statusFilters.contains(widget.initialStatus)
        ? widget.initialStatus
        : 'All';
    _fetchReports();
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
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _handleUnauthorized() async {
    await widget.apiService.clearAuthSession();
    if (!mounted) return;
    widget.onUnauthorized?.call();
    Navigator.of(context).popUntil((route) => route.isFirst);
  }

  Future<void> _fetchReports() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _currentPage = 1;
    });

    try {
      final result = await widget.apiService.getReportsPaginated(
        status: _selectedStatus == 'All' ? null : _selectedStatus,
        search: _searchController.text.trim().isEmpty
            ? null
            : _searchController.text.trim(),
        page: 1,
      );
      if (!mounted) return;
      setState(() {
        _reports = result.items;
        _currentPage = result.currentPage;
        _hasMorePages = result.hasMore;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 401) {
        await _handleUnauthorized();
        return;
      }
      setState(() => _errorMessage = e.message);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _errorMessage = 'Gagal memuat riwayat laporan. Periksa koneksi server Laravel.';
      });
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _loadMoreReports() async {
    if (_isLoading || _isLoadingMore || !_hasMorePages) return;

    setState(() => _isLoadingMore = true);

    try {
      final nextPage = _currentPage + 1;
      final result = await widget.apiService.getReportsPaginated(
        status: _selectedStatus == 'All' ? null : _selectedStatus,
        search: _searchController.text.trim().isEmpty
            ? null
            : _searchController.text.trim(),
        page: nextPage,
      );
      if (!mounted) return;
      setState(() {
        _reports = [..._reports, ...result.items];
        _currentPage = result.currentPage;
        _hasMorePages = result.hasMore;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 401) {
        await _handleUnauthorized();
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
          content: Text('Gagal memuat halaman berikutnya.'),
          backgroundColor: AppTheme.dangerRed,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isLoadingMore = false);
      }
    }
  }

  Future<void> _openCreateReport() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute<bool>(
        builder: (_) => CreateReportScreen(
          apiService: widget.apiService,
          onUnauthorized: widget.onUnauthorized,
        ),
      ),
    );
    if (created == true && mounted) {
      _fetchReports();
    }
  }

  Future<void> _openReportDetail(WasteReportModel report) async {
    final deleted = await Navigator.of(context).push<bool>(
      MaterialPageRoute<bool>(
        builder: (_) => ReportDetailScreen(
          apiService: widget.apiService,
          reportId: report.id,
          initialReport: report,
          currentUser: _currentUser,
          onUnauthorized: widget.onUnauthorized,
        ),
      ),
    );
    if (deleted == true && mounted) {
      _fetchReports();
    }
  }

  Future<void> _confirmAndDeleteReport(WasteReportModel report) async {
    if (!report.canBeDeletedBy(_currentUser)) return;

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

    try {
      await widget.apiService.deleteReport(report.id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Laporan #${report.id} berhasil dihapus.'),
          backgroundColor: AppTheme.forestPrimary,
        ),
      );
      _fetchReports();
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 401) {
        await _handleUnauthorized();
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
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Riwayat & Status Laporan',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        actions: [
          IconButton(
            onPressed: _fetchReports,
            tooltip: 'Muat Ulang',
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _openCreateReport,
        backgroundColor: AppTheme.forestPrimary,
        foregroundColor: Colors.white,
        tooltip: 'Buat Laporan Baru',
        child: const Icon(Icons.add),
      ),
      body: Column(
        children: [
          // Search & Status Filter Bar
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextField(
                  controller: _searchController,
                  textInputAction: TextInputAction.search,
                  onChanged: (_) => setState(() {}),
                  onSubmitted: (_) => _fetchReports(),
                  decoration: InputDecoration(
                    hintText: 'Cari deskripsi atau lokasi laporan...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _searchController.text.isNotEmpty
                        ? IconButton(
                            onPressed: () {
                              _searchController.clear();
                              setState(() {});
                              _fetchReports();
                            },
                            icon: const Icon(Icons.clear),
                          )
                        : null,
                  ),
                ),
                const SizedBox(height: 10),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: _statusFilters.map((status) {
                      final isSelected = _selectedStatus == status;
                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: ChoiceChip(
                          showCheckmark: false,
                          label: Text(status == 'All' ? 'Semua Status' : status),
                          selected: isSelected,
                          selectedColor: AppTheme.forestPrimary,
                          labelStyle: TextStyle(
                            color: isSelected ? Colors.white : AppTheme.inkPrimary,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                          onSelected: (_) {
                            setState(() => _selectedStatus = status);
                            _fetchReports();
                          },
                        ),
                      );
                    }).toList(),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: AppTheme.borderSubtle),

          // Reports List Body
          Expanded(
            child: RefreshIndicator(
              onRefresh: _fetchReports,
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _errorMessage != null
                      ? ListView(
                          padding: const EdgeInsets.all(24),
                          children: [
                            Card(
                              child: Padding(
                                padding: const EdgeInsets.all(20),
                                child: Column(
                                  children: [
                                    const Icon(
                                      Icons.error_outline,
                                      size: 36,
                                      color: AppTheme.dangerRed,
                                    ),
                                    const SizedBox(height: 10),
                                    Text(
                                      _errorMessage!,
                                      textAlign: TextAlign.center,
                                      style: const TextStyle(
                                        color: AppTheme.dangerRed,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                    const SizedBox(height: 12),
                                    ElevatedButton.icon(
                                      onPressed: _fetchReports,
                                      icon: const Icon(Icons.refresh),
                                      label: const Text('Coba Lagi'),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        )
                      : _reports.isEmpty
                          ? ListView(
                              padding: const EdgeInsets.all(24),
                              children: [
                                Card(
                                  child: Padding(
                                    padding: const EdgeInsets.all(24),
                                    child: Column(
                                      children: [
                                        const Icon(
                                          Icons.folder_open_outlined,
                                          size: 40,
                                          color: AppTheme.inkMuted,
                                        ),
                                        const SizedBox(height: 10),
                                        Text(
                                          _selectedStatus == 'All'
                                              ? 'Belum ada riwayat laporan sampah'
                                              : 'Tidak ada laporan dengan status $_selectedStatus',
                                          textAlign: TextAlign.center,
                                          style: const TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 15,
                                            color: AppTheme.inkPrimary,
                                          ),
                                        ),
                                        const SizedBox(height: 6),
                                        const Text(
                                          'Ubah filter status di atas atau buat laporan temuan sampah baru.',
                                          textAlign: TextAlign.center,
                                          style: TextStyle(
                                            color: AppTheme.inkMuted,
                                            fontSize: 13,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            )
                          : ListView.builder(
                              padding: const EdgeInsets.fromLTRB(16, 12, 16, 84),
                              itemCount: _reports.length + (_hasMorePages ? 1 : 0),
                              itemBuilder: (context, index) {
                                if (index >= _reports.length) {
                                  return Padding(
                                    padding: const EdgeInsets.symmetric(vertical: 12),
                                    child: Center(
                                      child: _isLoadingMore
                                          ? const SizedBox(
                                              width: 28,
                                              height: 28,
                                              child: CircularProgressIndicator(strokeWidth: 2.5),
                                            )
                                          : OutlinedButton.icon(
                                              onPressed: _loadMoreReports,
                                              icon: const Icon(Icons.expand_more_rounded),
                                              label: const Text('Muat Lebih Banyak'),
                                            ),
                                    ),
                                  );
                                }
                                final report = _reports[index];
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 10),
                                  child: Card(
                                    clipBehavior: Clip.antiAlias,
                                    child: InkWell(
                                      onTap: () => _openReportDetail(report),
                                      child: Padding(
                                        padding: const EdgeInsets.all(12),
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Row(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                ClipRRect(
                                                  borderRadius: BorderRadius.circular(8),
                                                  child: SizedBox(
                                                    width: 72,
                                                    height: 72,
                                                    child: report.photoUrl.isNotEmpty
                                                        ? Image.network(
                                                            report.photoUrl,
                                                            fit: BoxFit.cover,
                                                            errorBuilder: (_, _, _) =>
                                                                Container(
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
                                                    crossAxisAlignment:
                                                        CrossAxisAlignment.start,
                                                    children: [
                                                      Wrap(
                                                        spacing: 6,
                                                        runSpacing: 6,
                                                        children: [
                                                          WasteTypeBadge(
                                                            wasteType: report.wasteType,
                                                            showConfidence: false,
                                                          ),
                                                          StatusBadge(status: report.status),
                                                        ],
                                                      ),
                                                      if (report.createdAt != null) ...[
                                                        const SizedBox(height: 5),
                                                        Row(
                                                          children: [
                                                            const Icon(
                                                              Icons.access_time_rounded,
                                                              size: 13,
                                                              color: AppTheme.inkMuted,
                                                            ),
                                                            const SizedBox(width: 4),
                                                            Expanded(
                                                              child: Text(
                                                                AppDateFormatter.formatWithDay(
                                                                  report.createdAt!.toLocal(),
                                                                ),
                                                                maxLines: 1,
                                                                overflow: TextOverflow.ellipsis,
                                                                style: const TextStyle(
                                                                  fontSize: 11,
                                                                  color: AppTheme.inkMuted,
                                                                ),
                                                              ),
                                                            ),
                                                          ],
                                                        ),
                                                      ],
                                                      const SizedBox(height: 5),
                                                      Row(
                                                        children: [
                                                          const Icon(
                                                            Icons.location_on_outlined,
                                                            size: 13,
                                                            color: AppTheme.inkMuted,
                                                          ),
                                                          const SizedBox(width: 4),
                                                          Expanded(
                                                            child: Text(
                                                              report.address?.isNotEmpty == true
                                                                  ? report.address!
                                                                  : 'GPS: ${report.latitude.toStringAsFixed(5)}, ${report.longitude.toStringAsFixed(5)}',
                                                              maxLines: 1,
                                                              overflow: TextOverflow.ellipsis,
                                                              style: const TextStyle(
                                                                fontSize: 11,
                                                                color: AppTheme.inkMuted,
                                                              ),
                                                            ),
                                                          ),
                                                        ],
                                                      ),
                                                    ],
                                                  ),
                                                ),
                                              ],
                                            ),
                                            const SizedBox(height: 10),
                                            Text(
                                              report.description,
                                              maxLines: 2,
                                              overflow: TextOverflow.ellipsis,
                                              style: const TextStyle(
                                                fontSize: 13,
                                                fontWeight: FontWeight.w600,
                                                color: AppTheme.inkPrimary,
                                                height: 1.35,
                                              ),
                                            ),
                                            if (report.adminNotes != null &&
                                                report.adminNotes!.isNotEmpty) ...[
                                              const SizedBox(height: 8),
                                              Container(
                                                width: double.infinity,
                                                padding: const EdgeInsets.symmetric(
                                                  horizontal: 10,
                                                  vertical: 7,
                                                ),
                                                decoration: BoxDecoration(
                                                  color: AppTheme.canvasBg,
                                                  borderRadius: BorderRadius.circular(6),
                                                  border: Border.all(
                                                    color: AppTheme.borderSubtle,
                                                  ),
                                                ),
                                                child: Text(
                                                  'Catatan Admin: ${report.adminNotes}',
                                                  maxLines: 2,
                                                  overflow: TextOverflow.ellipsis,
                                                  style: const TextStyle(
                                                    fontSize: 11,
                                                    color: AppTheme.inkPrimary,
                                                  ),
                                                ),
                                              ),
                                            ],
                                            if (report.canBeDeletedBy(_currentUser)) ...[
                                              const SizedBox(height: 10),
                                              const Divider(
                                                height: 1,
                                                color: AppTheme.borderSubtle,
                                              ),
                                              const SizedBox(height: 6),
                                              Align(
                                                alignment: Alignment.centerRight,
                                                child: TextButton.icon(
                                                  onPressed: () =>
                                                      _confirmAndDeleteReport(report),
                                                  icon: const Icon(
                                                    Icons.delete_outline,
                                                    size: 16,
                                                    color: AppTheme.dangerRed,
                                                  ),
                                                  label: const Text(
                                                    'Hapus Laporan',
                                                    style: TextStyle(
                                                      color: AppTheme.dangerRed,
                                                      fontSize: 12,
                                                      fontWeight: FontWeight.bold,
                                                    ),
                                                  ),
                                                  style: TextButton.styleFrom(
                                                    minimumSize: const Size(88, 44),
                                                    padding: const EdgeInsets.symmetric(
                                                      horizontal: 10,
                                                    ),
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ],
                                        ),
                                      ),
                                    ),
                                  ),
                                );
                              },
                            ),
            ),
          ),
        ],
      ),
    );
  }
}
