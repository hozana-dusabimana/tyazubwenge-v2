class Product {
  final int? id;
  final String name;
  final String? sku;
  final String? barcode;
  final String? description;
  final int? categoryId;
  final int? brandId;
  final String? unit;
  final double? costPrice;
  final double? retailPrice;
  final double? wholesalePrice;
  final double? minStockLevel;
  final String status;
  final String? localId;
  final int? serverId;
  final int serverSynced;
  final String? createdAt;
  final String? updatedAt;

  Product({
    this.id,
    required this.name,
    this.sku,
    this.barcode,
    this.description,
    this.categoryId,
    this.brandId,
    this.unit,
    this.costPrice,
    this.retailPrice,
    this.wholesalePrice,
    this.minStockLevel,
    this.status = 'active',
    this.localId,
    this.serverId,
    this.serverSynced = 0,
    this.createdAt,
    this.updatedAt,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'name': name,
      'sku': sku,
      'barcode': barcode,
      'description': description,
      'category_id': categoryId,
      'brand_id': brandId,
      'unit': unit,
      'cost_price': costPrice,
      'retail_price': retailPrice,
      'wholesale_price': wholesalePrice,
      'min_stock_level': minStockLevel,
      'status': status,
      'local_id': localId,
      'server_id': serverId,
      'server_synced': serverSynced,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory Product.fromMap(Map<String, dynamic> map) {
    return Product(
      id: map['id'] as int?,
      name: map['name'] as String,
      sku: map['sku'] as String?,
      barcode: map['barcode'] as String?,
      description: map['description'] as String?,
      categoryId: map['category_id'] as int?,
      brandId: map['brand_id'] as int?,
      unit: map['unit'] as String?,
      costPrice: (map['cost_price'] as num?)?.toDouble(),
      retailPrice: (map['retail_price'] as num?)?.toDouble(),
      wholesalePrice: (map['wholesale_price'] as num?)?.toDouble(),
      minStockLevel: (map['min_stock_level'] as num?)?.toDouble(),
      status: map['status'] as String? ?? 'active',
      localId: map['local_id'] as String?,
      serverId: map['server_id'] as int?,
      serverSynced: map['server_synced'] as int? ?? 0,
      createdAt: map['created_at'] as String?,
      updatedAt: map['updated_at'] as String?,
    );
  }
}

