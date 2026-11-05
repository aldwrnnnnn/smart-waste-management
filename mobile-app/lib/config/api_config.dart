class ApiConfig {
  ApiConfig._();

  // Default URL untuk Android Emulator (10.0.2.2 mengarah ke localhost komputer host).
  //
  // PANDUAN PENGUJIAN DI HP FISIK (REAL DEVICE):
  // 1. Pastikan komputer dan HP terhubung ke jaringan Wi-Fi / Hotspot yang sama.
  // 2. Cek IPv4 komputer melalui terminal dengan perintah `ipconfig` (contoh: 192.168.1.10).
  // 3. Jalankan server Laravel dengan host 0.0.0.0:
  //    `php artisan serve --host=0.0.0.0 --port=8000`
  // 4. Ubah nilai `baseUrl` di bawah menjadi:
  //    `static const String baseUrl = 'http://192.168.1.10:8000/api';`
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api',
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
