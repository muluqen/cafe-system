import 'package:flutter/material.dart';

class RestaurantPulseView extends StatelessWidget {
  const RestaurantPulseView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Customer Pulse")),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            // Hero Card
            Card(
              color: Colors.blueGrey[900],
              child: const ListTile(
                title: Text("Restaurant Average", style: TextStyle(color: Colors.white70)),
                trailing: Text("4.8/5", style: TextStyle(color: Colors.teal, fontSize: 24, fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(height: 20),
            
            // Metrics Grid
            Row(
              children: [
                Expanded(child: _metric("Compliments", "12", Colors.green)),
                const SizedBox(width: 10),
                Expanded(child: _metric("Complaints", "2", Colors.red)),
              ],
            ),
            
            const SizedBox(height: 30),
            const Text("Recent Customer Voice", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const Divider(),
            
            // Feedback Feed
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: 3,
              itemBuilder: (context, index) => Card(
                child: ListTile(
                  title: const Text("Customer Name"),
                  subtitle: const Text("The latte was perfectly balanced. Great service!"),
                  trailing: const Text("5/5"),
                ),
              ),
            )
          ],
        ),
      ),
    );
  }

  Widget _metric(String label, String val, Color color) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Text(label, style: const TextStyle(fontSize: 12)),
            Text(val, style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: color)),
          ],
        ),
      ),
    );
  }
}