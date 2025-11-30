import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../database/db_helper.dart';
import '../services/sale_service.dart';
import '../models/sale.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic> _stats = {};
  List<Sale> _recentSales = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final db = await DatabaseHelper.database;

    // Today's date
    final today = DateTime.now();
    final todayStart = DateTime(today.year, today.month, today.day);
    final todayEnd = todayStart.add(const Duration(days: 1));

    // Today's sales
    final todaySales = await db.rawQuery('''
      SELECT COALESCE(SUM(final_amount), 0) as total 
      FROM sales 
      WHERE created_at >= ? AND created_at < ?
    ''', [todayStart.toIso8601String(), todayEnd.toIso8601String()]);
    final todayTotal = (todaySales.first['total'] as num?)?.toDouble() ?? 0.0;

    // Total orders
    final totalOrders =
        await db.rawQuery('SELECT COUNT(*) as count FROM sales');

    // Low stock items (assuming min_stock_level exists in products)
    final lowStock = await db.rawQuery('''
      SELECT COUNT(DISTINCT si.product_id) as count
      FROM stock_inventory si
      JOIN products p ON si.product_id = p.id
      WHERE si.quantity <= COALESCE(p.min_stock_level, 0)
    ''');

    // Near expiry (within 30 days)
    final nearExpiry = await db.rawQuery('''
      SELECT COUNT(*) as count
      FROM stock_inventory
      WHERE expiry_date IS NOT NULL 
      AND expiry_date > date('now')
      AND expiry_date <= date('now', '+30 days')
    ''');

    // Recent sales
    final recentSales = await SaleService.getSales(limit: 5);

    setState(() {
      _stats = {
        'todaySales': todayTotal,
        'totalOrders': totalOrders.first['count'] as int? ?? 0,
        'lowStock': lowStock.first['count'] as int? ?? 0,
        'nearExpiry': nearExpiry.first['count'] as int? ?? 0,
      };
      _recentSales = recentSales;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Page Title
                  const Text(
                    'Dashboard',
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF2C3E50),
                    ),
                  ),
                  const SizedBox(height: 24),
                  // Summary Cards
                  Row(
                    children: [
                      Expanded(
                        child: _StatCard(
                          title: "Today's Sales",
                          value: _stats['todaySales']?.toStringAsFixed(2) ??
                              '0.00',
                          icon: Icons.shopping_cart,
                          color: const Color(0xFF0D6EFD), // Blue
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: _StatCard(
                          title: 'Total Orders',
                          value: _stats['totalOrders']?.toString() ?? '0',
                          icon: Icons.receipt_long,
                          color: const Color(0xFF198754), // Green
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: _StatCard(
                          title: 'Low Stock Items',
                          value: _stats['lowStock']?.toString() ?? '0',
                          icon: Icons.warning,
                          color: const Color(0xFFFFC107), // Yellow
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: _StatCard(
                          title: 'Near Expiry',
                          value: _stats['nearExpiry']?.toString() ?? '0',
                          icon: Icons.event_busy,
                          color: const Color(0xFFDC3545), // Red
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  // Recent Sales and Top Products Row
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Recent Sales
                      Expanded(
                        flex: 2,
                        child: Card(
                          elevation: 2,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Padding(
                                padding: const EdgeInsets.all(16),
                                child: Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text(
                                      'Recent Sales',
                                      style: TextStyle(
                                        fontSize: 18,
                                        fontWeight: FontWeight.w600,
                                        color: Color(0xFF2C3E50),
                                      ),
                                    ),
                                    TextButton(
                                      onPressed: () {
                                        // Navigate to sales page
                                      },
                                      child: const Text('View All'),
                                    ),
                                  ],
                                ),
                              ),
                              const Divider(height: 1),
                              if (_recentSales.isEmpty)
                                const Padding(
                                  padding: EdgeInsets.all(20),
                                  child: Center(child: Text('No recent sales')),
                                )
                              else
                                SingleChildScrollView(
                                  scrollDirection: Axis.horizontal,
                                  child: DataTable(
                                    columns: const [
                                      DataColumn(label: Text('Invoice #')),
                                      DataColumn(label: Text('Customer')),
                                      DataColumn(label: Text('Amount')),
                                      DataColumn(label: Text('Date')),
                                      DataColumn(label: Text('Action')),
                                    ],
                                    rows: _recentSales.map((sale) {
                                      return DataRow(
                                        cells: [
                                          DataCell(Text(
                                              sale.invoiceNumber ?? 'N/A')),
                                          DataCell(const Text('Walk-in')),
                                          DataCell(Text(
                                            (sale.finalAmount ?? 0)
                                                .toStringAsFixed(2),
                                          )),
                                          DataCell(Text(
                                            _formatDate(sale.createdAt),
                                          )),
                                          DataCell(
                                            IconButton(
                                              icon: const Icon(Icons.print,
                                                  size: 18),
                                              onPressed: () {
                                                // Print invoice
                                              },
                                            ),
                                          ),
                                        ],
                                      );
                                    }).toList(),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      // Top Products
                      Expanded(
                        flex: 1,
                        child: Card(
                          elevation: 2,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Top Products (7 Days)',
                                  style: TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF2C3E50),
                                  ),
                                ),
                                const SizedBox(height: 16),
                                // This would be populated from actual data
                                _TopProductItem(
                                  name: 'NaOH',
                                  sku: 'sku75',
                                  value: '12,000.00',
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
    );
  }

  String _formatDate(String? dateStr) {
    if (dateStr == null) return 'N/A';
    try {
      final date = DateTime.parse(dateStr);
      return DateFormat('dd MMM yyyy HH:mm').format(date);
    } catch (e) {
      return dateStr;
    }
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(10),
      ),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Icon(icon, size: 32, color: color),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              title,
              style: const TextStyle(
                fontSize: 14,
                color: Color(0xFF6C757D),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TopProductItem extends StatelessWidget {
  final String name;
  final String sku;
  final String value;

  const _TopProductItem({
    required this.name,
    required this.sku,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: const Color(0xFF0D6EFD).withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                name,
                style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 14,
                ),
              ),
              Text(
                sku,
                style: const TextStyle(
                  fontSize: 12,
                  color: Color(0xFF6C757D),
                ),
              ),
            ],
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFF0D6EFD),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              value,
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w600,
                fontSize: 12,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
