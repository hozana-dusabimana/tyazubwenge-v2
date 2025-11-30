class Sale {
  final int? id;
  final String? localId;
  final int? serverId;
  final String? invoiceNumber;
  final int? customerId;
  final int? branchId;
  final int? userId;
  final double? totalAmount;
  final double discount;
  final double tax;
  final double? finalAmount;
  final String? paymentMethod;
  final String paymentStatus;
  final String saleType;
  final String? notes;
  final String syncStatus;
  final String? createdAt;
  final String? updatedAt;
  final List<SaleItem>? items;

  Sale({
    this.id,
    this.localId,
    this.serverId,
    this.invoiceNumber,
    this.customerId,
    this.branchId,
    this.userId,
    this.totalAmount,
    this.discount = 0,
    this.tax = 0,
    this.finalAmount,
    this.paymentMethod,
    this.paymentStatus = 'completed',
    this.saleType = 'retail',
    this.notes,
    this.syncStatus = 'pending',
    this.createdAt,
    this.updatedAt,
    this.items,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'local_id': localId,
      'server_id': serverId,
      'invoice_number': invoiceNumber,
      'customer_id': customerId,
      'branch_id': branchId,
      'user_id': userId,
      'total_amount': totalAmount,
      'discount': discount,
      'tax': tax,
      'final_amount': finalAmount,
      'payment_method': paymentMethod,
      'payment_status': paymentStatus,
      'sale_type': saleType,
      'notes': notes,
      'sync_status': syncStatus,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory Sale.fromMap(Map<String, dynamic> map) {
    return Sale(
      id: map['id'] as int?,
      localId: map['local_id'] as String?,
      serverId: map['server_id'] as int?,
      invoiceNumber: map['invoice_number'] as String?,
      customerId: map['customer_id'] as int?,
      branchId: map['branch_id'] as int?,
      userId: map['user_id'] as int?,
      totalAmount: (map['total_amount'] as num?)?.toDouble(),
      discount: (map['discount'] as num?)?.toDouble() ?? 0,
      tax: (map['tax'] as num?)?.toDouble() ?? 0,
      finalAmount: (map['final_amount'] as num?)?.toDouble(),
      paymentMethod: map['payment_method'] as String?,
      paymentStatus: map['payment_status'] as String? ?? 'completed',
      saleType: map['sale_type'] as String? ?? 'retail',
      notes: map['notes'] as String?,
      syncStatus: map['sync_status'] as String? ?? 'pending',
      createdAt: map['created_at'] as String?,
      updatedAt: map['updated_at'] as String?,
    );
  }
}

class SaleItem {
  final int? id;
  final int saleId;
  final int productId;
  final double quantity;
  final String? unit;
  final double unitPrice;
  final double discount;
  final double subtotal;

  SaleItem({
    this.id,
    required this.saleId,
    required this.productId,
    required this.quantity,
    this.unit,
    required this.unitPrice,
    this.discount = 0,
    required this.subtotal,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'sale_id': saleId,
      'product_id': productId,
      'quantity': quantity,
      'unit': unit,
      'unit_price': unitPrice,
      'discount': discount,
      'subtotal': subtotal,
    };
  }

  factory SaleItem.fromMap(Map<String, dynamic> map) {
    return SaleItem(
      id: map['id'] as int?,
      saleId: map['sale_id'] as int,
      productId: map['product_id'] as int,
      quantity: (map['quantity'] as num).toDouble(),
      unit: map['unit'] as String?,
      unitPrice: (map['unit_price'] as num).toDouble(),
      discount: (map['discount'] as num?)?.toDouble() ?? 0,
      subtotal: (map['subtotal'] as num).toDouble(),
    );
  }
}

