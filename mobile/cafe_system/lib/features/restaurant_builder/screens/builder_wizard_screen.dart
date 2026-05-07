import 'package:cafe_system/features/restaurant_builder/screens/brand_step_screen.dart';
import 'package:cafe_system/features/restaurant_builder/screens/menu_step_screen.dart';
import 'package:cafe_system/features/restaurant_builder/screens/team_step_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/builder_provider.dart';
import '../../auth/providers/auth_provider.dart';


class RestaurantBuilderView extends ConsumerStatefulWidget {
  const RestaurantBuilderView({super.key});

  @override
  ConsumerState<RestaurantBuilderView> createState() => _RestaurantBuilderViewState();
}

class _RestaurantBuilderViewState extends ConsumerState<RestaurantBuilderView> {
  @override
  void initState() {
    super.initState();
    // Replicates onMounted
    Future.microtask(() => ref.read(builderProvider.notifier).init());
  }

  @override
  Widget build(BuildContext context) {
    final builderState = ref.watch(builderProvider);
    final notifier = ref.read(builderProvider.notifier);
    final authState = ref.watch(authProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Restaurant Setup"),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(50),
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: ["Brand", "Preview", "Menu", "Team", "Tables"].asMap().entries.map((entry) {
                bool isActive = builderState.currentStepIndex == entry.key;
                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 8.0),
                  child: ChoiceChip(
                    label: Text("${entry.key + 1}. ${entry.value}"),
                    selected: isActive,
                    onSelected: (_) => notifier.setStep(entry.key),
                  ),
                );
              }).toList(),
            ),
          ),
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: _buildCurrentStep(builderState, notifier, authState),
      ),
    );
  }

  Widget _buildCurrentStep(BuilderState state, BuilderNotifier notifier, AuthState auth) {
    switch (state.currentStepIndex) {
      case 0:
        return BrandStepView(
          form: state.brandForm,
          newColor: "#14b8a6", // Simplified for now
          isSaving: state.loading,
          error: state.error,
          message: state.message,
          onUpdateField: notifier.updateBrandField,
          onUpdateNewColor: (val) {}, 
          onAddColor: () {},
          onRemoveColor: (val) {},
          onSetPrimary: (val) => notifier.updateBrandField('brandColor', val),
          onSave: notifier.saveBrand,
        );
      case 2: // Skip 1 for Preview
        return MenuStepView(
          menuItems: state.menuItems,
          isSaving: state.loading,
          onBack: notifier.prev,
          onNext: notifier.next,
          onSave: notifier.addMenuItem,
          onRemove: notifier.removeMenuItem,
        );
      case 3:
        return TeamStepView(
          staffMembers: state.staffMembers,
          ownerId: auth.user?.id,
          isSaving: state.loading,
          onBack: notifier.prev,
          onNext: notifier.next,
          onAddStaff: (data) {}, // Implement similar to menu
          onUpdateRole: (user, role) {},
          onRemoveStaff: (id) {},
        );
      default:
        return const Center(child: Text("Step Coming Soon..."));
    }
  }
}