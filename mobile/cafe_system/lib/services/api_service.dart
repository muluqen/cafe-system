import 'package:dio/dio.dart';
import 'package:riverpod/riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  late Dio dio;

  ApiService(ProviderRef<Object?> ref) {
    dio = Dio(
      BaseOptions(
        baseUrl: const String.fromEnvironment(
          'API_BASE_URL',
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

        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }

        if (restaurantId != null) {
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