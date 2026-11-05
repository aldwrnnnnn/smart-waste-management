class ApiConfig {
  ApiConfig._();

  // kalo mau jalanin pake ip:
  // php artisan serve --host=0.0.0.0 --port=8000
  // trus ganti pake ip lu
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://prideful-lecturer-snap.ngrok-free.dev/api',
  );

  static String get hostBaseUrl {
    if (baseUrl.endsWith('/api')) {
      return baseUrl.substring(0, baseUrl.length - 4);
    }
    return baseUrl;
  }

  // Mengubah URL foto yang tersimpan sebagai 127.0.0.1, localhost, atau host lain agar dapat dimuat sesuai hostBaseUrl.
  static String resolveStorageUrl(String? rawUrl, String? photoPath) {
    if (photoPath != null && photoPath.isNotEmpty) {
      if (photoPath.startsWith('http://') || photoPath.startsWith('https://')) {
        return photoPath;
      }
      final cleanPath = photoPath.startsWith('/') ? photoPath.substring(1) : photoPath;
      return '$hostBaseUrl/storage/$cleanPath';
    }

    if (rawUrl != null && rawUrl.isNotEmpty) {
      final parsedRaw = Uri.tryParse(rawUrl);
      if (parsedRaw != null) {
        if (parsedRaw.path.contains('/storage/')) {
          final storageIndex = parsedRaw.path.indexOf('/storage/');
          return '$hostBaseUrl${parsedRaw.path.substring(storageIndex)}';
        }
        if (parsedRaw.host != '127.0.0.1' && parsedRaw.host != 'localhost' && parsedRaw.host != '10.0.2.2') {
          return rawUrl;
        }
        return '$hostBaseUrl${parsedRaw.path}';
      }
    }
    return '';
  }
}
