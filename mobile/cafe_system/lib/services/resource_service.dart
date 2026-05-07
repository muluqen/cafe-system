import 'package:dio/dio.dart';

class ResourceService {
  final Dio _dio;
  ResourceService(this._dio);

  Future<List<dynamic>> getList(String resource, {Map<String, dynamic>? params}) async {
    final response = await _dio.get("/$resource", queryParameters: params);
    final data = response.data;

    // Logic to unwrap Laravel's data or data.data structure
    if (data is List) return data;
    if (data is Map && data['data'] is List) return data['data'];
    
    return [];
  }

  Future<dynamic> create(String resource, Map<String, dynamic> payload) async {
    final response = await _dio.post("/$resource", data: payload);
    return response.data;
  }

  Future<dynamic> update(String resource, dynamic id, Map<String, dynamic> payload) async {
    final response = await _dio.put("/$resource/$id", data: payload);
    return response.data;
  }

  Future<void> delete(String resource, dynamic id) async {
    await _dio.delete("/$resource/$id");
  }
}