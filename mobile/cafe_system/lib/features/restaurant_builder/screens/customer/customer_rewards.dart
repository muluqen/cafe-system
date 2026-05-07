import 'package:flutter/material.dart';

class CustomerRewardsView extends StatelessWidget {
  const CustomerRewardsView({super.key});

  @override
  Widget build(BuildContext context) {
  
    const int points = 150; // Mocked: Calculate from order history
    const String tier = "House Favorite";
   

    return Scaffold(
      appBar: AppBar(title: const Text("Rewards")),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            _buildTierCard(tier, points),
            const SizedBox(height: 30),
            _buildMetricGrid(),
            const SizedBox(height: 30),
            _buildUnlockItem("Priority Table Ping", 60, points >= 60),
            _buildUnlockItem("Chef Surprise", 140, points >= 140),
            _buildUnlockItem("Fast Reorder Lane", 220, points >= 220),
          ],
        ),
      ),
    );
  }

  Widget _buildTierCard(String tier, int points) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.blueGrey[900],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          const Text("CURRENT TIER", style: TextStyle(color: Colors.teal, fontSize: 10)),
          Text(tier, style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
          const SizedBox(height: 10),
          LinearProgressIndicator(value: 0.6, color: Colors.teal, backgroundColor: Colors.white12),
          const SizedBox(height: 10),
          Text("$points Points", style: const TextStyle(color: Colors.grey)),
        ],
      ),
    );
  }

  Widget _buildUnlockItem(String title, int threshold, bool reached) {
    return ListTile(
      leading: Icon(reached ? Icons.check_circle : Icons.lock, color: reached ? Colors.green : Colors.grey),
      title: Text(title, style: TextStyle(decoration: reached ? null : TextDecoration.lineThrough)),
      subtitle: Text("$threshold points needed"),
    );
  }

  Widget _buildMetricGrid() {
    return const Row(
      children: [
        Expanded(child: _MetricItem("Visits", "12")),
        Expanded(child: _MetricItem("Spent", "\$450")),
        Expanded(child: _MetricItem("Favs", "5")),
      ],
    );
  }
}

class _MetricItem extends StatelessWidget {
  final String label, value;
  const _MetricItem(this.label, this.value);
  @override
  Widget build(BuildContext context) => Column(children: [Text(value, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)), Text(label)]);
}