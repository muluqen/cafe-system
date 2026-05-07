import 'dart:convert';
import 'package:cafe_system/models/user.dart';
import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../restaurant_builder/providers/api_provider.dart';

class AuthState {
  final String token;
  final User? user;
  final String selectedRestaurantId;
  final bool loading;
  final String error;
  final List<dynamic> publicRestaurants;

  bool get isAuthenticated => token.isNotEmpty;
  bool get isRestaurant => user?.role == 'restaurant';
  bool get isCustomer => user?.role == 'customer';
  String get staffRole => user?.staffRole ?? '';
  AuthState({
    this.token = '',
    this.user,
    this.selectedRestaurantId = '',
    this.loading = false,
    this.error = '',
    this.publicRestaurants = const [], // Default to empty list
  });

  AuthState copyWith({
    String? token,
    User? user,
    String? selectedRestaurantId,
    bool? loading,
    String? error,
    List<dynamic>? publicRestaurants,
  }) {
    return AuthState(
      token: token ?? this.token,
      user: user ?? this.user,
      selectedRestaurantId: selectedRestaurantId ?? this.selectedRestaurantId,
      loading: loading ?? this.loading,
      error: error ?? this.error,
      publicRestaurants: publicRestaurants ?? this.publicRestaurants,
    );
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref.watch(dioProvider));
});

class AuthNotifier extends StateNotifier<AuthState> {
  final Dio _dio;

  AuthNotifier(this._dio) : super(AuthState()) {
    loadStoredAuth();
  }

  // --- ACTIONS ---
  Future<void> registerRestaurant(Map<String, dynamic> payload) async {
  // endpoint: /auth/register-restaurant
  await _submitAuth("/auth/register-restaurant", payload);
}
Future<void> saveBranding(int restaurantId, Map<String, dynamic> config) async {
  final prefs = await SharedPreferences.getInstance();
  final String key = "cafe_restaurant_site_config_$restaurantId";
  await prefs.setString(key, jsonEncode(config));
}

Future<Map<String, dynamic>> getBranding(int restaurantId) async {
  final prefs = await SharedPreferences.getInstance();
  final String key = "cafe_restaurant_site_config_$restaurantId";
  final String? raw = prefs.getString(key);
  if (raw == null) return {}; // Return defaults
  return jsonDecode(raw);
}  

  Future<void> loadPublicRestaurants() async {
    try {
      final response = await _dio.get("/auth/restaurants");
      state = state.copyWith(
        publicRestaurants: response.data is List ? response.data : [],
      );
    } catch (e) {
      print("Error loading restaurants: $e");
    }
  }

  Future<void> login(String email, String password) async {
    await _submitAuth("/auth/login", {
      'email': email,
      'password': password,
    });
  }

  Future<void> register(Map<String, dynamic> payload) async {
    await _submitAuth("/auth/register", payload);
  }

  // Shared Logic for Login/Register (replicates Vue's submitAuth)
  Future<void> _submitAuth(String endpoint, Map<String, dynamic> payload) async {
    state = state.copyWith(loading: true, error: '');
    try {
      final response = await _dio.post(endpoint, data: payload);
      final data = response.data;

      final user = User.fromJson(data['user']);
      String restId = '';

      if (user.role == 'restaurant') {
        restId = user.restaurantId.toString();
      }

      state = state.copyWith(
        token: data['token'] ?? '',
        user: user,
        selectedRestaurantId: restId,
        loading: false,
      );
      await _persist();
    } on DioException catch (e) {
      String errorMessage = "Authentication failed";
      
      // Handle Laravel validation errors (flattened like your Vue code)
      if (e.response?.data['errors'] != null) {
        final Map<String, dynamic> errors = e.response?.data['errors'];
        errorMessage = errors.values.expand((e) => e as List).join(" ");
      } else if (e.response?.data['message'] != null) {
        errorMessage = e.response?.data['message'];
      }
      
      state = state.copyWith(loading: false, error: errorMessage);
      rethrow; 
    }
  }

  Future<void> logout() async {
    try {
      if (state.token.isNotEmpty) {
        await _dio.post("/auth/logout");
      }
    } finally {
      state = AuthState(); // Resets everything including publicRestaurants
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
    }
  }

  // --- HELPERS ---

  Future<void> loadStoredAuth() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString("cafe_auth_token") ?? "";
    final userJson = prefs.getString("cafe_auth_user");
    final restId = prefs.getString("cafe_selected_restaurant_id") ?? "";

    User? user;
    if (userJson != null) {
      try {
        user = User.fromJson(jsonDecode(userJson));
      } catch (e) {
        print("Error decoding user: $e");
      }
    }
    state = state.copyWith(
      token: token, 
      user: user, 
      selectedRestaurantId: restId
    );
  }

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString("cafe_auth_token", state.token);
    await prefs.setString("cafe_selected_restaurant_id", state.selectedRestaurantId);
    if (state.user != null) {
      await prefs.setString("cafe_auth_user", jsonEncode(state.user!.toJson()));
    } else {
      await prefs.remove("cafe_auth_user");
    }
  }

  void setSelectedRestaurant(String id) {
    state = state.copyWith(selectedRestaurantId: id);
    _persist();
  }
}