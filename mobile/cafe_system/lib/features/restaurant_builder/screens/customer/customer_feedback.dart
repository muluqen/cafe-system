import 'package:flutter/material.dart';

class CustomerFeedbackView extends StatefulWidget {
  const CustomerFeedbackView({super.key});

  @override
  State<CustomerFeedbackView> createState() => _CustomerFeedbackViewState();
}

class _CustomerFeedbackViewState extends State<CustomerFeedbackView> {
  int rating = 5;
  final _complimentController = TextEditingController();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Share Feedback")),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            const Text("Overall Rating"),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(5, (index) => IconButton(
                icon: Icon(index < rating ? Icons.star : Icons.star_border, color: Colors.orange),
                onPressed: () => setState(() => rating = index + 1),
              )),
            ),
            TextField(
              controller: _complimentController,
              maxLines: 4,
              decoration: const InputDecoration(labelText: "What made the visit great?", border: OutlineInputBorder()),
            ),
            const SizedBox(height: 20),
            ElevatedButton(onPressed: () {}, child: const Text("Save Feedback")),
          ],
        ),
      ),
    );
  }
}