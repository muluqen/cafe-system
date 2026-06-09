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

  // GETTERS (Matched exactly with Pinia)
  bool get isAuthenticated => token.isNotEmpty;
  String get role => user?.role ?? '';
  String get staffRole => user?.staffRole ?? '';
  bool get isRestaurant => user?.role == 'restaurant';
  bool get isCustomer => user?.role == 'customer';

  AuthState({
    this.token = '',
    this.user,
    this.selectedRestaurantId = '',
    this.loading = false,
    this.error = '',
    this.publicRestaurants = const [], 
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

  Future<void> login(String email, String password, {String? accessKey}) async {
    final payload = {
      'email': email,
      'password': password,
    };
    if (accessKey != null && accessKey.isNotEmpty) {
      payload['access_key'] = accessKey;
    }
    await _submitAuth("/auth/login", payload);
  }

  Future<void> register(Map<String, dynamic> payload) async {
    await _submitAuth("/auth/register", payload);
  }

  Future<void> registerRestaurant(Map<String, dynamic> payload) async {
    await _submitAuth("/auth/register-restaurant", payload);
  }

  Future<void> _submitAuth(String endpoint, Map<String, dynamic> payload) async {
    state = state.copyWith(loading: true, error: '');
    try {
      final response = await _dio.post(endpoint, data: payload);
      final data = response.data;

      final user = User.fromJson(data['user']);
      String restId = '';

      if (user.role == 'restaurant') {
        restId = user.restaurantId?.toString() ?? '';
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
      
      if (e.response?.data != null && e.response!.data is Map) {
        final errorData = e.response!.data as Map<String, dynamic>;
        
        if (errorData['errors'] != null) {
          final Map<String, dynamic> errors = errorData['errors'];
          errorMessage = errors.values.expand((err) => err is List ? err : [err]).join(" ");
        } else if (errorData['message'] != null) {
          errorMessage = errorData['message'];
        }
      }
      
      state = state.copyWith(loading: false, error: errorMessage);
      rethrow; 
    }
  }

  // Matching Pinia fetchMe()
  Future<void> fetchMe() async {
    if (state.token.isEmpty) {
      return;
    }

    try {
      final response = await _dio.get("/auth/me");
      final user = User.fromJson(response.data);
      state = state.copyWith(user: user);
      await _persist();
    } catch (e) {
      await logoutLocal();
    }
  }

  void setSelectedRestaurant(String id) {
    state = state.copyWith(selectedRestaurantId: id);
    _persist();
  }

  Future<void> logout() async {
    try {
      if (state.token.isNotEmpty) {
        await _dio.post("/auth/logout");
      }
    } catch (e) {
      // Ignore network errors on logout
      print("Logout API failed, forcing local logout.");
    } finally {
      await logoutLocal();
    }
  }

  // Matching Pinia logoutLocal()
  Future<void> logoutLocal() async {
    state = AuthState(); // Resets everything to default
    await _persist();
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

  // Matching Pinia persist() exactly (removes keys if they are empty)
  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    
    if (state.token.isNotEmpty) {
      await prefs.setString("cafe_auth_token", state.token);
    } else {
      await prefs.remove("cafe_auth_token");
    }
    
    if (state.user != null) {
      await prefs.setString("cafe_auth_user", jsonEncode(state.user!.toJson()));
    } else {
      await prefs.remove("cafe_auth_user");
    }

    if (state.selectedRestaurantId.isNotEmpty) {
      await prefs.setString("cafe_selected_restaurant_id", state.selectedRestaurantId);
    } else {
      await prefs.remove("cafe_selected_restaurant_id");
    }
  }

  // Extra features not in Pinia but kept from your original code
  Future<void> saveBranding(int restaurantId, Map<String, dynamic> config) async {
    final prefs = await SharedPreferences.getInstance();
    final String key = "cafe_restaurant_site_config_$restaurantId";
    await prefs.setString(key, jsonEncode(config));
  }

  Future<Map<String, dynamic>> getBranding(int restaurantId) async {
    final prefs = await SharedPreferences.getInstance();
    final String key = "cafe_restaurant_site_config_$restaurantId";
    final String? raw = prefs.getString(key);
    if (raw == null) return {}; 
    return jsonDecode(raw);
  }  
}