import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

// ==========================================
// 1. PROVIDERS (Needed for AuthProvider)
// ==========================================

final apiProvider = Provider<ApiService>((ref) {
  return ApiService(ref);
});

// This is exactly what auth_provider.dart is looking for!
final dioProvider = Provider<Dio>((ref) {
  return ref.watch(apiProvider).dio;
});

// ==========================================
// 2. API SERVICE CLASS
// ==========================================

class ApiService {
  late Dio dio;

  ApiService(ProviderRef<Object?> ref) {
    dio = Dio(
      BaseOptions(
        baseUrl: const String.fromEnvironment(
          'API_BASE_URL',
          // TIP: If you are testing on an Android Emulator, 127.0.0.1 will fail. 
          // You need to change this to 'http://10.0.2.2:8000/api' for Android.
          // iOS Simulator and Web work fine with 127.0.0.1.
          defaultValue: 'http://127.0.0.1:8000/api', 
        ),
        headers: {
          'Accept': 'application/json',
        },
      ),
    );

    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final prefs = await SharedPreferences.getInstance();

        final token = prefs.getString('cafe_auth_token');
        final restaurantId = prefs.getString('cafe_selected_restaurant_id');

        // Added .isNotEmpty checks to prevent sending "Bearer " on logged-out state
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }

        if (restaurantId != null && restaurantId.isNotEmpty) {
          options.headers['X-Restaurant-Id'] = restaurantId;
        }

        return handler.next(options);
      },
    ));
  }

  Future<Response> get(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    return await dio.get(endpoint, queryParameters: queryParameters);
  }

  Future<Response> post(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) async {
    return await dio.post(
      endpoint,
      data: data,
      queryParameters: queryParameters,
    );
  }

  Future<Response> put(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) async {
    return await dio.put(
      endpoint,
      data: data,
      queryParameters: queryParameters,
    );
  }

  Future<Response> patch(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) async {
    return await dio.patch(
      endpoint,
      data: data,
      queryParameters: queryParameters,
    );
  }

  Future<Response> delete(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    return await dio.delete(
      endpoint,
      queryParameters: queryParameters,
    );
  }
}