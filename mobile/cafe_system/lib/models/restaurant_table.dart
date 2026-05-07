class RestaurantTable {
  final int id;
  final String name;
  final int capacity;
  final String? location;

  RestaurantTable({required this.id, required this.name, required this.capacity, this.location});

  factory RestaurantTable.fromJson(Map<String, dynamic> json) {
    return RestaurantTable(
      id: json['id'],
      name: json['name'],
      capacity: json['capacity'] ?? 2,
      location: json['location'],
    );
  }
}