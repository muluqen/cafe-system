class User {
  final int? id;
  final String? name;
  final String? email;
  final String? role;
  final String? staffRole;
  final int? restaurantId;

  User({this.id, this.name, this.email, this.role, this.staffRole, this.restaurantId});

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      role: json['role'],
      staffRole: json['staff_role'],
      restaurantId: json['restaurant_id'],
    );
  }
bool get isCustomer => role == 'customer';
bool get isRestaurant => role == 'restaurant';
  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'role': role,
    'staff_role': staffRole,
    'restaurant_id': restaurantId,
  };
}