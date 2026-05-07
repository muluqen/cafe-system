import 'package:cafe_system/services/resource_service.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'api_provider.dart';

class EntityState {
  final Map<String, List<dynamic>> byEntity;
  final bool loading;
  final bool saving;
  final String? error;

  EntityState({
    this.byEntity = const {},
    this.loading = false,
    this.saving = false,
    this.error,
  });

  EntityState copyWith({
    Map<String, List<dynamic>>? byEntity,
    bool? loading,
    bool? saving,
    String? error,
  }) {
    return EntityState(
      byEntity: byEntity ?? this.byEntity,
      loading: loading ?? this.loading,
      saving: saving ?? this.saving,
      error: error,
    );
  }
}

final entityProvider = StateNotifierProvider<EntityNotifier, EntityState>((ref) {
  return EntityNotifier(ResourceService(ref.watch(dioProvider)));
});

class EntityNotifier extends StateNotifier<EntityState> {
  final ResourceService _service;
  EntityNotifier(this._service) : super(EntityState());

  Future<void> fetch(String key, {String? search}) async {
    state = state.copyWith(loading: true, error: null);
    try {
      final params = search != null && search.isNotEmpty ? {'search': search} : null;
      final list = await _service.getList(key, params: params);
      
      final newMap = Map<String, List<dynamic>>.from(state.byEntity);
      newMap[key] = list;
      state = state.copyWith(byEntity: newMap, loading: false);
    } catch (e) {
      state = state.copyWith(loading: false, error: e.toString());
    }
  }

  Future<void> save(String key, dynamic id, Map<String, dynamic> payload) async {
    state = state.copyWith(saving: true);
    try {
      if (id != null) {
        await _service.update(key, id, payload);
      } else {
        await _service.create(key, payload);
      }
      await fetch(key); // Refresh
    } finally {
      state = state.copyWith(saving: false);
    }
  }

  Future<void> delete(String key, dynamic id) async {
    await _service.delete(key, id);
    await fetch(key);
  }
}