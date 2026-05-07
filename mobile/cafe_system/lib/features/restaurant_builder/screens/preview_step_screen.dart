import 'package:flutter/material.dart';

class PreviewStepView extends StatelessWidget {
  final Map<String, dynamic> brandForm;
  final List<dynamic> menuItems;
  final VoidCallback onBack;
  final VoidCallback onNext;

  const PreviewStepView({
    super.key, required this.brandForm, required this.menuItems, 
    required this.onBack, required this.onNext
  });

  @override
  Widget build(BuildContext context) {
    // Replicate CSS variables logic
    final Color brandColor = _parseColor(brandForm['brandColor'] ?? "#ff7a59");

    return Column(
      children: [
        const Text("Step 2: See what customers will see first", 
          style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
        const SizedBox(height: 20),
        
        // MOCKUP PHONE FRAME
        Container(
          width: 350,
          height: 600,
          decoration: BoxDecoration(
            border: Border.all(color: Colors.black, width: 8),
            borderRadius: BorderRadius.circular(30),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(22),
            child: Scaffold(
              body: Column(
                children: [
                  // Mock Hero with Brand Colors
                  Container(
                    padding: const EdgeInsets.all(20),
                    width: double.infinity,
                    color: brandColor,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(brandForm['name'] ?? "Restaurant Name", 
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                        Text(brandForm['headline'] ?? "Your Headline Here", 
                          style: const TextStyle(color: Colors.white70, fontSize: 18)),
                      ],
                    ),
                  ),
                  // Mock Menu List
                  Expanded(
                    child: ListView.builder(
                      itemCount: menuItems.length.clamp(0, 3),
                      itemBuilder: (context, i) => ListTile(
                        title: Text(menuItems[i]['name']),
                        trailing: ElevatedButton(
                          onPressed: null, 
                          style: ElevatedButton.styleFrom(backgroundColor: brandColor),
                          child: const Text("Order"),
                        ),
                      ),
                    ),
                  )
                ],
              ),
            ),
          ),
        ),

        const SizedBox(height: 30),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            TextButton(onPressed: onBack, child: const Text("Go back and edit")),
            ElevatedButton(onPressed: onNext, child: const Text("This looks good, next step")),
          ],
        )
      ],
    );
  }

  Color _parseColor(String hex) => Color(int.parse(hex.replaceFirst('#', '0xFF')));
}