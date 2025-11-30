import 'package:flutter/material.dart';
import '../models/stock.dart';
import '../models/product.dart';
import '../services/stock_service.dart';
import '../services/product_service.dart';

class StockScreen extends StatefulWidget {
  const StockScreen({super.key});

  @override
  State<StockScreen> createState() => _StockScreenState();
}

class _StockScreenState extends State<StockScreen> {
  List<StockItem> _stockItems = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadStock();
  }

  Future<void> _loadStock() async {
    setState(() => _isLoading = true);
    try {
      final stock = await StockService.getStockItems();
      setState(() {
        _stockItems = stock;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading stock: $e')),
        );
      }
    }
  }

  Future<void> _showAddStockModal() async {
    final products = await ProductService.getProducts();
    if (products.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No products available. Please sync first.')),
      );
      return;
    }

    Product? selectedProduct;
    final quantityController = TextEditingController();
    final unitController = TextEditingController();
    final batchController = TextEditingController();
    final expiryController = TextEditingController();

    final result = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          title: const Text('Add Stock'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                DropdownButtonFormField<Product>(
                  decoration: const InputDecoration(labelText: 'Product'),
                  items: products.map((p) => DropdownMenuItem(
                    value: p,
                    child: Text(p.name),
                  )).toList(),
                  onChanged: (product) {
                    setState(() {
                      selectedProduct = product;
                      unitController.text = product?.unit ?? 'pcs';
                    });
                  },
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: quantityController,
                  decoration: const InputDecoration(labelText: 'Quantity'),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: unitController,
                  decoration: const InputDecoration(labelText: 'Unit'),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: batchController,
                  decoration: const InputDecoration(labelText: 'Batch Number (optional)'),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: expiryController,
                  decoration: const InputDecoration(
                    labelText: 'Expiry Date (YYYY-MM-DD) (optional)',
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Add'),
            ),
          ],
        ),
      ),
    );

    if (result == true && selectedProduct != null) {
      try {
        final stock = StockItem(
          productId: selectedProduct!.id!,
          quantity: double.tryParse(quantityController.text) ?? 0,
          unit: unitController.text.isEmpty ? null : unitController.text,
          batchNumber: batchController.text.isEmpty ? null : batchController.text,
          expiryDate: expiryController.text.isEmpty ? null : expiryController.text,
        );

        await StockService.addStock(stock);
        _loadStock();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Stock added successfully!')),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error adding stock: $e')),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _stockItems.isEmpty
              ? const Center(child: Text('No stock items'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Stock Management',
                            style: TextStyle(
                              fontSize: 28,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF2C3E50),
                            ),
                          ),
                          ElevatedButton.icon(
                            onPressed: _showAddStockModal,
                            icon: const Icon(Icons.add),
                            label: const Text('Add Stock'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF0D6EFD),
                              foregroundColor: Colors.white,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),
                      Card(
                        elevation: 2,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: DataTable(
                              columns: const [
                                DataColumn(label: Text('Product')),
                                DataColumn(label: Text('SKU')),
                                DataColumn(label: Text('Quantity')),
                                DataColumn(label: Text('Unit')),
                                DataColumn(label: Text('Batch')),
                                DataColumn(label: Text('Expiry')),
                                DataColumn(label: Text('Status')),
                              ],
                              rows: _stockItems.map((item) {
                                return DataRow(
                                  cells: [
                                    DataCell(Text(item.productName ?? 'N/A')),
                                    DataCell(Text(item.productSku ?? 'N/A')),
                                    DataCell(Text(item.quantity.toStringAsFixed(2))),
                                    DataCell(Text(item.unit ?? item.productSku ?? 'N/A')),
                                    DataCell(Text(item.batchNumber ?? 'N/A')),
                                    DataCell(Text(item.expiryDate ?? 'N/A')),
                                    DataCell(
                                      Chip(
                                        label: Text(item.syncStatus),
                                        backgroundColor: item.syncStatus == 'synced'
                                            ? Colors.green
                                            : Colors.orange,
                                      ),
                                    ),
                                  ],
                                );
                              }).toList(),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }
}
