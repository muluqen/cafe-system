class MenuItem {
  final int id;
  final String name;
  final double price;
  final String? description;
  final int? preparationTime;
  final String? categoryName;

  MenuItem({
    required this.id,
    required this.name,
    required this.price,
    this.description,
    this.preparationTime,
    this.categoryName,
  });

  factory MenuItem.fromJson(Map<String, dynamic> json) {
    return MenuItem(
      id: json['id'],
      name: json['name'],
      price: double.tryParse(json['price'].toString()) ?? 0.0,
      description: json['description'],
      preparationTime: json['preparation_time_minutes'],
      // Logic to handle nested Laravel relationship
      categoryName: json['menu_category']?['name'] ?? json['menuCategory']?['name'],
    );
  }
}