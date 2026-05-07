// import 'package:cafe_system/services/api_service.dart';
// import 'package:flutter_riverpod/flutter_riverpod.dart';

// final apiServiceProvider = Provider((ref) => ApiService(ref));
import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

final dioProvider = Provider<Dio>((ref) {
  final dio = Dio(BaseOptions(
    // REPLACE with your Laravel API URL. 
    // Use http://10.0.2.2:8000/api for Android Emulator
    baseUrl: "http://127.0.0.1:8000/api", 
    headers: {"Accept": "application/json"},
  ));

  dio.interceptors.add(InterceptorsWrapper(
    onRequest: (options, handler) async {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString("cafe_auth_token");
      final restaurantId = prefs.getString("cafe_selected_restaurant_id");

      if (token != null && token.isNotEmpty) {
        options.headers["Authorization"] = "Bearer $token";
      }
      if (restaurantId != null && restaurantId.isNotEmpty) {
        options.headers["X-Restaurant-Id"] = restaurantId;
      }
      return handler.next(options);
    },
  ));

  return dio;
});