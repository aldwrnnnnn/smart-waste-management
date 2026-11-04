import '../config/api_config.dart';
import 'user_model.dart';

class WasteReportModel {
  final int id;
  final int userId;
  final String? userName;
  final String photoPath;
  final String photoUrl;
  final String wasteType;
  final double? confidenceScore;
  final double latitude;
  final double longitude;
  final String? address;
  final String description;
  final String status;
  final String? adminNotes;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  const WasteReportModel({
    required this.id,
    required this.userId,
    this.userName,
    required this.photoPath,
    required this.photoUrl,
    required this.wasteType,
    this.confidenceScore,
    required this.latitude,
    required this.longitude,
    this.address,
    required this.description,
    required this.status,
    this.adminNotes,
    this.createdAt,
    this.updatedAt,
  });

  bool get isPending => status.trim().toLowerCase() == 'pending';

  bool get canBeDeleted => isPending;

  bool canBeDeletedBy([UserModel? user]) {
    if (user == null) return false;
    if (user.isAdmin) return true;
    return isPending && user.id == userId;
  }

  static int parseInt(dynamic value, [int fallback = 0]) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value) ?? fallback;
    return fallback;
  }

  static double parseDouble(dynamic value, [double fallback = 0.0]) {
    if (value is double) return value;
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? fallback;
    return fallback;
  }

  static double? parseNullableDouble(dynamic value) {
    if (value == null) return null;
    if (value is double) return value;
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value);
    return null;
  }

  factory WasteReportModel.fromJson(Map<String, dynamic> json) {
    final rawPhotoPath = json['photo_path']?.toString() ?? '';
    final rawPhotoUrl = json['photo_url']?.toString();

    String? resolvedUserName = json['user_name']?.toString();
    if ((resolvedUserName == null || resolvedUserName.isEmpty) &&
        json['user'] is Map) {
      final userMap = Map<String, dynamic>.from(json['user'] as Map);
      resolvedUserName = userMap['name']?.toString();
    }

    return WasteReportModel(
      id: parseInt(json['id']),
      userId: parseInt(json['user_id']),
      userName: resolvedUserName,
      photoPath: rawPhotoPath,
      photoUrl: ApiConfig.resolveStorageUrl(rawPhotoUrl, rawPhotoPath),
      wasteType: json['waste_type']?.toString() ?? 'Plastic Waste',
      confidenceScore: parseNullableDouble(json['confidence_score']),
      latitude: parseDouble(json['latitude']),
      longitude: parseDouble(json['longitude']),
      address: json['address']?.toString(),
      description: json['description']?.toString() ?? '',
      status: json['status']?.toString() ?? 'Pending',
      adminNotes: json['admin_notes']?.toString(),
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'].toString())
          : null,
    );
  }
}
