import 'package:cafe_system/models/menu_item.dart';
import 'package:cafe_system/models/user.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import 'api_provider.dart';
import '../../auth/providers/auth_provider.dart';

class BuilderState {
  final int currentStepIndex;
  final bool loading;
  final String error;
  final String message;
  final Map<String, dynamic> brandForm;
  final List<MenuItem> menuItems;
  final List<User> staffMembers;
  final List<dynamic> tables;

  BuilderState({
    this.currentStepIndex = 0,
    this.loading = false,
    this.error = '',
    this.message = '',
    this.brandForm = const {},
    this.menuItems = const [],
    this.staffMembers = const [],
    this.tables = const [],
  });

  BuilderState copyWith({
    int? currentStepIndex,
    bool? loading,
    String? error,
    String? message,
    Map<String, dynamic>? brandForm,
    List<MenuItem>? menuItems,
    List<User>? staffMembers,
    List<dynamic>? tables,
  }) {
    return BuilderState(
      currentStepIndex: currentStepIndex ?? this.currentStepIndex,
      loading: loading ?? this.loading,
      error: error ?? this.error,
      message: message ?? this.message,
      brandForm: brandForm ?? this.brandForm,
      menuItems: menuItems ?? this.menuItems,
      staffMembers: staffMembers ?? this.staffMembers,
      tables: tables ?? this.tables,
    );
  }
}

final builderProvider = StateNotifierProvider<BuilderNotifier, BuilderState>((ref) {
  return BuilderNotifier(ref.watch(dioProvider), ref);
});

class BuilderNotifier extends StateNotifier<BuilderState> {
  final Dio _dio;
  final Ref _ref;

  BuilderNotifier(this._dio, this._ref) : super(BuilderState(brandForm: {
    'name': '', 'headline': '', 'customerMessage': '', 
    'brandColor': '#ff7a59', 'brandColors': ['#ff7a59', '#ffb36c'], 'logoUrl': ''
  }));

  // Initialize data (onMounted)
  Future<void> init() async {
    final user = _ref.read(authProvider).user;
    if (user?.restaurantId == null) return;

    state = state.copyWith(loading: true);
    try {
      // Replicates Promise.all loadSetupLists
      final responses = await Future.wait([
        _dio.get('/restaurants/${user!.restaurantId}'),
        _dio.get('/menu_items'),
        _dio.get('/users'),
        _dio.get('/tables'),
      ]);

      final restData = responses[0].data;
      state = state.copyWith(
        brandForm: {
          ...state.brandForm,
          'id': restData['id'],
          'name': restData['name'] ?? '',
          'email': restData['email'] ?? '',
          'phone': restData['phone'] ?? '',
          'address': restData['address'] ?? '',
        },
        menuItems: (responses[1].data['data'] as List).map((m) => MenuItem.fromJson(m)).toList(),
        staffMembers: (responses[2].data['data'] as List).map((u) => User.fromJson(u)).toList(),
        tables: responses[3].data['data'] as List,
        loading: false,
      );
    } catch (e) {
      state = state.copyWith(loading: false, error: e.toString());
    }
  }

  // --- Brand Actions ---
  void updateBrandField(String field, dynamic value) {
    state = state.copyWith(brandForm: {...state.brandForm, field: value});
  }

  Future<void> saveBrand() async {
    state = state.copyWith(loading: true, error: '');
    try {
      await _dio.put('/restaurants/${state.brandForm['id']}', data: state.brandForm);
      state = state.copyWith(loading: false, currentStepIndex: 1, message: "Brand saved!");
    } catch (e) {
      state = state.copyWith(loading: false, error: "Failed to save branding");
    }
  }

  // --- Menu Actions ---
  Future<void> addMenuItem(Map<String, dynamic> data) async {
    state = state.copyWith(loading: true);
    try {
      // First ensure category exists or logic matches Laravel
      await _dio.post('/menu_items', data: {
        ...data,
        'restaurant_id': _ref.read(authProvider).user?.restaurantId,
      });
      await init(); // Refresh list
    } finally {
      state = state.copyWith(loading: false);
    }
  }

  Future<void> removeMenuItem(int id) async {
    await _dio.delete('/menu_items/$id');
    await init();
  }

  // --- Navigation ---
  void setStep(int index) => state = state.copyWith(currentStepIndex: index);
  void next() => state = state.copyWith(currentStepIndex: state.currentStepIndex + 1);
  void prev() => state = state.copyWith(currentStepIndex: state.currentStepIndex - 1);
}