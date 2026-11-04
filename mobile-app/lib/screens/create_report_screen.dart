import 'dart:io';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';

import '../models/waste_report_model.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';

class CreateReportScreen extends StatefulWidget {
  final ApiService apiService;
  final VoidCallback? onUnauthorized;

  const CreateReportScreen({
    super.key,
    required this.apiService,
    this.onUnauthorized,
  });

  @override
  State<CreateReportScreen> createState() => _CreateReportScreenState();
}

class _CreateReportScreenState extends State<CreateReportScreen> {
  static const List<String> _wasteTypes = [
    'Organic Waste',
    'Plastic Waste',
    'Paper Waste',
  ];

  final _formKey = GlobalKey<FormState>();
  final _imagePicker = ImagePicker();
  final _latitudeController = TextEditingController(text: '-6.20000000');
  final _longitudeController = TextEditingController(text: '106.81666600');
  final _addressController = TextEditingController();
  final _descriptionController = TextEditingController();

  File? _selectedPhoto;
  String? _selectedWasteType;
  String? _aiDetectedWasteType;
  double? _aiDetectedConfidence;
  double? _confidenceScore;
  Map<String, double> _probabilities = const {};

  bool _isDetectingAi = false;
  String? _aiDetectionMessage;
  bool _aiDetectionSuccess = false;

  bool _isFetchingLocation = false;
  bool _isLocationDeniedForever = false;
  String? _locationMessage;

  bool _isSubmitting = false;
  String? _submitErrorMessage;

  @override
  void initState() {
    super.initState();
    _fetchCurrentLocation();
  }

  @override
  void dispose() {
    _latitudeController.dispose();
    _longitudeController.dispose();
    _addressController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  Future<void> _handleUnauthorized() async {
    await widget.apiService.clearAuthSession();
    if (!mounted) return;
    widget.onUnauthorized?.call();
    Navigator.of(context).popUntil((route) => route.isFirst);
  }

  Future<void> _pickPhoto(ImageSource source) async {
    try {
      final picked = await _imagePicker.pickImage(
        source: source,
        maxWidth: 1280,
        maxHeight: 1280,
        imageQuality: 85,
      );
      if (picked == null || !mounted) return;

      final file = File(picked.path);
      setState(() {
        _selectedPhoto = file;
        _aiDetectedWasteType = null;
        _aiDetectedConfidence = null;
        _confidenceScore = null;
        _probabilities = const {};
        _submitErrorMessage = null;
      });

      await _runAiDetection();
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _submitErrorMessage =
            'Gagal membuka kamera atau galeri. Pastikan izin kamera/penyimpanan telah diaktifkan.';
      });
    }
  }

  Future<void> _runAiDetection() async {
    final photo = _selectedPhoto;
    if (photo == null) return;

    setState(() {
      _isDetectingAi = true;
      _aiDetectedWasteType = null;
      _aiDetectedConfidence = null;
      _confidenceScore = null;
      _probabilities = const {};
      _aiDetectionMessage = 'Mengirim gambar ke FastAPI MobileNetV2 untuk deteksi otomatis...';
      _aiDetectionSuccess = false;
    });

    try {
      final data = await widget.apiService.detectWasteCategory(photo);
      if (!mounted) return;

      final detectedType = data['waste_type']?.toString() ?? 'Plastic Waste';
      final conf = WasteReportModel.parseNullableDouble(data['confidence_score']);

      final probsRaw = data['all_probabilities'];
      final parsedProbs = <String, double>{};
      if (probsRaw is Map) {
        for (final entry in probsRaw.entries) {
          final parsedVal = WasteReportModel.parseNullableDouble(entry.value);
          if (parsedVal != null) {
            parsedProbs[entry.key.toString()] = parsedVal;
          }
        }
      }

      setState(() {
        if (_wasteTypes.contains(detectedType)) {
          _selectedWasteType = detectedType;
          _aiDetectedWasteType = detectedType;
        }
        _aiDetectedConfidence = conf;
        _confidenceScore = conf;
        _probabilities = parsedProbs;
        _aiDetectionSuccess = true;
        final pct = conf != null ? '${(conf * 100).toStringAsFixed(1)}%' : '-';
        _aiDetectionMessage = 'Hasil Prediksi AI: $detectedType (Confidence: $pct)';
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 401) {
        await _handleUnauthorized();
        return;
      }
      setState(() {
        _aiDetectionSuccess = false;
        _aiDetectionMessage = '${e.message} Anda tetap dapat memilih kategori secara manual.';
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _aiDetectionSuccess = false;
        _aiDetectionMessage =
            'Layanan deteksi AI belum dapat dijangkau. Silakan pilih kategori sampah secara manual di bawah.';
      });
    } finally {
      if (mounted) {
        setState(() => _isDetectingAi = false);
      }
    }
  }

  Future<void> _fetchCurrentLocation() async {
    setState(() {
      _isFetchingLocation = true;
      _isLocationDeniedForever = false;
      _locationMessage = 'Mengambil titik koordinat GPS perangkat...';
    });

    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        if (!mounted) return;
        setState(() {
          _isFetchingLocation = false;
          _locationMessage =
              'Layanan GPS nonaktif. Menggunakan koordinat default (dapat diubah manual).';
        });
        return;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        if (!mounted) return;
        setState(() {
          _isFetchingLocation = false;
          _isLocationDeniedForever = permission == LocationPermission.deniedForever;
          _locationMessage = permission == LocationPermission.deniedForever
              ? 'Izin lokasi ditolak permanen. Buka pengaturan aplikasi atau isi koordinat secara manual.'
              : 'Izin lokasi belum diberikan. Anda dapat mengisi koordinat secara manual.';
        });
        return;
      }

      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 10),
        ),
      );

      if (!mounted) return;
      setState(() {
        _latitudeController.text = pos.latitude.toStringAsFixed(8);
        _longitudeController.text = pos.longitude.toStringAsFixed(8);
        _locationMessage = 'Koordinat GPS berhasil diambil secara akurat.';
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _locationMessage =
            'Gagal mengunci sinyal GPS emulator/perangkat. Koordinat dapat disesuaikan manual.';
      });
    } finally {
      if (mounted) {
        setState(() => _isFetchingLocation = false);
      }
    }
  }

  Future<void> _submitReport() async {
    if (_isDetectingAi) return;

    if (_selectedPhoto == null) {
      setState(() {
        _submitErrorMessage =
            'Silakan ambil foto sampah dari Kamera atau Galeri terlebih dahulu.';
      });
      return;
    }

    if (!_formKey.currentState!.validate()) return;

    final lat = double.tryParse(_latitudeController.text.trim());
    final lng = double.tryParse(_longitudeController.text.trim());
    if (lat == null || lng == null || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
      setState(() {
        _submitErrorMessage =
            'Koordinat Latitude harus di antara -90 s/d 90 dan Longitude di antara -180 s/d 180.';
      });
      return;
    }

    setState(() {
      _isSubmitting = true;
      _submitErrorMessage = null;
    });

    try {
      await widget.apiService.createReport(
        photoFile: _selectedPhoto!,
        latitude: lat,
        longitude: lng,
        address: _addressController.text,
        description: _descriptionController.text,
        wasteType: _selectedWasteType,
        confidenceScore: _confidenceScore,
      );

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Laporan sampah berhasil dikirim dan disimpan!'),
          backgroundColor: AppTheme.forestPrimary,
        ),
      );
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 401) {
        await _handleUnauthorized();
        return;
      }
      setState(() => _submitErrorMessage = e.message);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _submitErrorMessage =
            'Gagal mengirim laporan ke server Laravel. Periksa koneksi jaringan Anda.';
      });
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Buat Laporan Sampah',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (_submitErrorMessage != null) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppTheme.dangerSurface,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: AppTheme.dangerRed.withValues(alpha: 0.4),
                      ),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.error_outline, color: AppTheme.dangerRed, size: 20),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            _submitErrorMessage!,
                            style: const TextStyle(
                              color: AppTheme.dangerRed,
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                ],

                // 1. Photo Capture & AI Detection Card
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text(
                          '1. Foto Sampah & Deteksi AI',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.inkPrimary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        const Text(
                          'Ambil foto dari Kamera atau Galeri. Sistem akan otomatis mendeteksi kategori sampah.',
                          style: TextStyle(fontSize: 12, color: AppTheme.inkMuted),
                        ),
                        const SizedBox(height: 14),

                        if (_selectedPhoto != null) ...[
                          ClipRRect(
                            borderRadius: BorderRadius.circular(10),
                            child: Image.file(
                              _selectedPhoto!,
                              height: 200,
                              width: double.infinity,
                              fit: BoxFit.cover,
                            ),
                          ),
                          const SizedBox(height: 12),
                        ] else ...[
                          Container(
                            height: 150,
                            decoration: BoxDecoration(
                              color: AppTheme.canvasBg,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: AppTheme.borderInput),
                            ),
                            child: const Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  Icons.add_photo_alternate_outlined,
                                  size: 42,
                                  color: AppTheme.inkMuted,
                                ),
                                SizedBox(height: 8),
                                Text(
                                  'Belum ada foto yang dipilih',
                                  style: TextStyle(
                                    color: AppTheme.inkMuted,
                                    fontSize: 13,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 12),
                        ],

                        Row(
                          children: [
                            Expanded(
                              child: ElevatedButton.icon(
                                onPressed: (_isSubmitting || _isDetectingAi)
                                    ? null
                                    : () => _pickPhoto(ImageSource.camera),
                                icon: const Icon(Icons.photo_camera_outlined, size: 18),
                                label: const Text('Kamera'),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: (_isSubmitting || _isDetectingAi)
                                    ? null
                                    : () => _pickPhoto(ImageSource.gallery),
                                icon: const Icon(Icons.photo_library_outlined, size: 18),
                                label: const Text('Galeri'),
                              ),
                            ),
                          ],
                        ),

                        if (_selectedPhoto != null) ...[
                          const SizedBox(height: 12),
                          OutlinedButton.icon(
                            onPressed: (_isDetectingAi || _isSubmitting)
                                ? null
                                : _runAiDetection,
                            icon: _isDetectingAi
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : const Icon(Icons.auto_awesome_outlined, size: 18),
                            label: Text(
                              _isDetectingAi
                                  ? 'Menganalisis Gambar dengan AI...'
                                  : 'Deteksi Ulang',
                            ),
                          ),
                        ],

                        if (_aiDetectionMessage != null) ...[
                          const SizedBox(height: 12),
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: _aiDetectionSuccess
                                  ? const Color(0xFFDCFCE7)
                                  : const Color(0xFFFEF3C7),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(
                                color: _aiDetectionSuccess
                                    ? const Color(0xFF22C55E)
                                    : const Color(0xFFF59E0B),
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _aiDetectionMessage!,
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    color: _aiDetectionSuccess
                                        ? const Color(0xFF14532D)
                                        : const Color(0xFF78350F),
                                  ),
                                ),
                                if (_probabilities.isNotEmpty) ...[
                                  const SizedBox(height: 8),
                                  ..._probabilities.entries.map(
                                    (entry) => Padding(
                                      padding: const EdgeInsets.only(bottom: 4),
                                      child: Row(
                                        children: [
                                          SizedBox(
                                            width: 105,
                                            child: Text(
                                              entry.key,
                                              style: const TextStyle(
                                                fontSize: 11,
                                                color: AppTheme.inkPrimary,
                                              ),
                                            ),
                                          ),
                                          Expanded(
                                            child: LinearProgressIndicator(
                                              value: entry.value.clamp(0.0, 1.0),
                                              minHeight: 7,
                                              backgroundColor: Colors.white,
                                              color: AppTheme.forestPrimary,
                                              borderRadius: BorderRadius.circular(4),
                                            ),
                                          ),
                                          const SizedBox(width: 8),
                                          SizedBox(
                                            width: 46,
                                            child: Text(
                                              '${(entry.value * 100).toStringAsFixed(1)}%',
                                              textAlign: TextAlign.right,
                                              style: const TextStyle(
                                                fontSize: 11,
                                                fontWeight: FontWeight.bold,
                                              ),
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
                        ],

                        const SizedBox(height: 14),
                        DropdownButtonFormField<String>(
                          key: ValueKey<String?>(_selectedWasteType),
                          initialValue: _selectedWasteType,
                          isExpanded: true,
                          decoration: const InputDecoration(
                            labelText: 'Kategori Sampah (Hasil AI / Pilihan Manual)',
                            prefixIcon: Icon(Icons.category_outlined),
                          ),
                          hint: const Text(
                            'Otomatis via AI atau pilih kategori',
                            overflow: TextOverflow.ellipsis,
                          ),
                          items: _wasteTypes
                              .map(
                                (type) => DropdownMenuItem<String>(
                                  value: type,
                                  child: Text(type, overflow: TextOverflow.ellipsis),
                                ),
                              )
                              .toList(),
                          onChanged: (value) {
                            setState(() {
                              _selectedWasteType = value;
                              if (_aiDetectedWasteType == null ||
                                  value != _aiDetectedWasteType) {
                                _confidenceScore = null;
                              } else {
                                _confidenceScore = _aiDetectedConfidence;
                              }
                            });
                          },
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // 2. GPS Coordinates & Location Card
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text(
                          '2. Titik Koordinat GPS & Lokasi',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.inkPrimary,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            Icon(
                              _isFetchingLocation
                                  ? Icons.sync
                                  : (_locationMessage != null &&
                                          _locationMessage!.contains('akurat')
                                      ? Icons.check_circle_outline
                                      : Icons.info_outline),
                              size: 15,
                              color: _isFetchingLocation
                                  ? AppTheme.inkMuted
                                  : (_locationMessage != null &&
                                          _locationMessage!.contains('akurat')
                                      ? AppTheme.forestPrimary
                                      : AppTheme.inkMuted),
                            ),
                            const SizedBox(width: 6),
                            Expanded(
                              child: Text(
                                _locationMessage ?? 'Koordinat GPS diambil otomatis.',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: _isFetchingLocation
                                      ? AppTheme.inkMuted
                                      : (_locationMessage != null &&
                                              _locationMessage!.contains('akurat')
                                          ? AppTheme.forestPrimary
                                          : AppTheme.inkMuted),
                                  fontWeight: (_locationMessage != null &&
                                          _locationMessage!.contains('akurat'))
                                      ? FontWeight.w500
                                      : FontWeight.normal,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            OutlinedButton.icon(
                              onPressed: _isFetchingLocation ? null : _fetchCurrentLocation,
                              icon: _isFetchingLocation
                                  ? const SizedBox(
                                      width: 14,
                                      height: 14,
                                      child: CircularProgressIndicator(strokeWidth: 2),
                                    )
                                  : const Icon(Icons.my_location, size: 16),
                              label: const Text(
                                'Refresh GPS',
                                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                              ),
                              style: OutlinedButton.styleFrom(
                                foregroundColor: AppTheme.forestPrimary,
                                side: const BorderSide(color: AppTheme.borderInput),
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                minimumSize: const Size(0, 38),
                                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                              ),
                            ),
                            if (_isLocationDeniedForever)
                              TextButton.icon(
                                onPressed: () => Geolocator.openAppSettings(),
                                icon: const Icon(Icons.settings_outlined, size: 16),
                                label: const Text(
                                  'Buka Pengaturan Izin',
                                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                                ),
                              ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        Row(
                          children: [
                            Expanded(
                              child: TextFormField(
                                controller: _latitudeController,
                                keyboardType: const TextInputType.numberWithOptions(
                                  signed: true,
                                  decimal: true,
                                ),
                                decoration: const InputDecoration(
                                  labelText: 'Latitude',
                                  prefixIcon: Icon(Icons.location_on_outlined),
                                ),
                                validator: (v) {
                                  if (v == null || v.trim().isEmpty) {
                                    return 'Wajib diisi';
                                  }
                                  final parsed = double.tryParse(v.trim());
                                  if (parsed == null) {
                                    return 'Angka tidak valid';
                                  }
                                  if (parsed < -90 || parsed > 90) {
                                    return 'Rentang -90 s/d 90';
                                  }
                                  return null;
                                },
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: TextFormField(
                                controller: _longitudeController,
                                keyboardType: const TextInputType.numberWithOptions(
                                  signed: true,
                                  decimal: true,
                                ),
                                decoration: const InputDecoration(
                                  labelText: 'Longitude',
                                  prefixIcon: Icon(Icons.explore_outlined),
                                ),
                                validator: (v) {
                                  if (v == null || v.trim().isEmpty) {
                                    return 'Wajib diisi';
                                  }
                                  final parsed = double.tryParse(v.trim());
                                  if (parsed == null) {
                                    return 'Angka tidak valid';
                                  }
                                  if (parsed < -180 || parsed > 180) {
                                    return 'Rentang -180 s/d 180';
                                  }
                                  return null;
                                },
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        TextFormField(
                          controller: _addressController,
                          textInputAction: TextInputAction.next,
                          decoration: const InputDecoration(
                            labelText: 'Alamat / Patokan Lokasi (Opsional)',
                            hintText: 'Contoh: Dekat Halte Bus Sudirman',
                            prefixIcon: Icon(Icons.map_outlined),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // 3. Description Card
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text(
                          '3. Deskripsi Kondisi Sampah',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.inkPrimary,
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _descriptionController,
                          maxLines: 4,
                          decoration: const InputDecoration(
                            labelText: 'Deskripsi Laporan',
                            hintText:
                                'Jelaskan kondisi tumpukan sampah di lokasi agar mudah ditangani petugas...',
                            alignLabelWithHint: true,
                          ),
                          validator: (v) {
                            if (v == null || v.trim().length < 5) {
                              return 'Deskripsi minimal terdiri dari 5 karakter.';
                            }
                            return null;
                          },
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 20),

                ElevatedButton.icon(
                  onPressed: (_isSubmitting || _isDetectingAi) ? null : _submitReport,
                  icon: _isSubmitting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.4,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.send_outlined),
                  label: Text(
                    _isSubmitting
                        ? 'Mengirim Laporan...'
                        : (_isDetectingAi
                            ? 'Menunggu Deteksi AI...'
                            : 'Kirim Laporan Sampah'),
                  ),
                ),
                const SizedBox(height: 24),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
