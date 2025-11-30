class Customer {
  final int? id;
  final String name;
  final String? email;
  final String? phone;
  final String? address;
  final int loyaltyPoints;
  final double creditLimit;
  final String? localId;
  final int? serverId;
  final String syncStatus;
  final String? createdAt;
  final String? updatedAt;

  Customer({
    this.id,
    required this.name,
    this.email,
    this.phone,
    this.address,
    this.loyaltyPoints = 0,
    this.creditLimit = 0,
    this.localId,
    this.serverId,
    this.syncStatus = 'pending',
    this.createdAt,
    this.updatedAt,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone': phone,
      'address': address,
      'loyalty_points': loyaltyPoints,
      'credit_limit': creditLimit,
      'local_id': localId,
      'server_id': serverId,
      'sync_status': syncStatus,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory Customer.fromMap(Map<String, dynamic> map) {
    return Customer(
      id: map['id'] as int?,
      name: map['name'] as String,
      email: map['email'] as String?,
      phone: map['phone'] as String?,
      address: map['address'] as String?,
      loyaltyPoints: map['loyalty_points'] as int? ?? 0,
      creditLimit: (map['credit_limit'] as num?)?.toDouble() ?? 0,
      localId: map['local_id'] as String?,
      serverId: map['server_id'] as int?,
      syncStatus: map['sync_status'] as String? ?? 'pending',
      createdAt: map['created_at'] as String?,
      updatedAt: map['updated_at'] as String?,
    );
  }
}


