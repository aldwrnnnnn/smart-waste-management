import 'package:flutter/material.dart';

import 'models/user_model.dart';
import 'screens/dashboard_screen.dart';
import 'screens/login_screen.dart';
import 'services/api_service.dart';
import 'theme/app_theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const SmartWasteApp());
}

class SmartWasteApp extends StatefulWidget {
  final ApiService? apiService;

  const SmartWasteApp({super.key, this.apiService});

  @override
  State<SmartWasteApp> createState() => _SmartWasteAppState();
}

class _SmartWasteAppState extends State<SmartWasteApp> {
  late final ApiService _apiService;
  UserModel? _currentUser;
  bool _initializing = true;

  @override
  void initState() {
    super.initState();
    _apiService = widget.apiService ?? ApiService();
    _restoreSession();
  }

  @override
  void dispose() {
    if (widget.apiService == null) {
      _apiService.close();
    }
    super.dispose();
  }

  Future<void> _restoreSession() async {
    final token = await _apiService.getToken();
    final user = await _apiService.getSavedUser();
    if (!mounted) return;
    setState(() {
      if (token != null && token.isNotEmpty && user != null) {
        _currentUser = user;
      }
      _initializing = false;
    });
  }

  void _handleAuthenticated(UserModel user) {
    setState(() {
      _currentUser = user;
    });
  }

  Future<void> _handleLogout() async {
    await _apiService.logout();
    if (!mounted) return;
    setState(() {
      _currentUser = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Smart Waste Management',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: _initializing
          ? Scaffold(
              backgroundColor: Colors.white,
              body: Center(
                child: Image.asset(
                  'assets/images/logo.png',
                  width: 96,
                  height: 96,
                  fit: BoxFit.contain,
                  errorBuilder: (_, _, _) => const CircularProgressIndicator(
                    color: AppTheme.forestPrimary,
                  ),
                ),
              ),
            )
          : _currentUser == null
              ? LoginScreen(
                  apiService: _apiService,
                  onAuthenticated: _handleAuthenticated,
                )
              : DashboardScreen(
                  user: _currentUser!,
                  apiService: _apiService,
                  onLogout: _handleLogout,
                ),
    );
  }
}
