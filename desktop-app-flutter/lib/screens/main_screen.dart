import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../services/sync_service.dart';
import '../database/db_helper.dart';
import 'dashboard_screen.dart';
import 'pos_screen.dart';
import 'stock_screen.dart';
import 'products_screen.dart';
import 'customers_screen.dart';
import 'sales_screen.dart';
import 'sync_screen.dart';
import 'login_screen.dart';

class MainScreen extends StatefulWidget {
  const MainScreen({super.key});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  int _selectedIndex = 0;
  bool _hasCheckedData = false;
  bool _sidebarCollapsed = false;

  final List<Widget> _screens = [
    const DashboardScreen(),
    const POSScreen(),
    const StockScreen(),
    const ProductsScreen(),
    const CustomersScreen(),
    const SalesScreen(),
    const SyncScreen(),
  ];

  final List<NavigationItem> _navItems = [
    NavigationItem(icon: Icons.dashboard, label: 'Dashboard'),
    NavigationItem(icon: Icons.point_of_sale, label: 'POS'),
    NavigationItem(icon: Icons.inventory, label: 'Stock'),
    NavigationItem(icon: Icons.shopping_bag, label: 'Products'),
    NavigationItem(icon: Icons.people, label: 'Customers'),
    NavigationItem(icon: Icons.receipt, label: 'Sales'),
    NavigationItem(icon: Icons.sync, label: 'Sync'),
  ];

  @override
  void initState() {
    super.initState();
    _checkAndDownloadData();
  }

  Future<void> _checkAndDownloadData() async {
    if (_hasCheckedData) return;
    _hasCheckedData = true;

    await Future.delayed(const Duration(milliseconds: 500));

    final syncService = Provider.of<SyncService>(context, listen: false);
    final db = await DatabaseHelper.database;

    final productCount =
        await db.rawQuery('SELECT COUNT(*) as count FROM products');
    final hasData = (productCount.first['count'] as int) > 0;

    try {
      await syncService.checkConnection();
      if (syncService.isOnline) {
        // Always download updates, not just on first run
        if (!hasData) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Downloading initial data...')),
            );
          }
        } else {
          // Silently check for updates when data exists
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Checking for updates...'),
                duration: Duration(seconds: 1),
              ),
            );
          }
        }

        final result = await syncService.downloadUpdates();

        if (mounted) {
          if (result['success'] == true) {
            final data = result['data'] as Map<String, dynamic>? ?? {};
            final productCount = (data['products'] as List?)?.length ?? 0;
            final stockCount = (data['stock'] as List?)?.length ?? 0;
            final customerCount = (data['customers'] as List?)?.length ?? 0;

            if (hasData) {
              // Show update notification
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(
                      'Data updated! Products: $productCount, Stock: $stockCount, Customers: $customerCount'),
                  duration: const Duration(seconds: 3),
                ),
              );
            } else {
              // Show initial download notification
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(
                      'Data downloaded! Products: $productCount, Stock: $stockCount, Customers: $customerCount'),
                  duration: const Duration(seconds: 3),
                ),
              );
            }

            // Refresh current screen if needed
            setState(() {});
          }
        }
      }
    } catch (e) {
      // Silent fail
    }
  }

  @override
  Widget build(BuildContext context) {
    final authService = Provider.of<AuthService>(context);
    final user = authService.getUser();

    return Scaffold(
      body: Column(
        children: [
          // Top Bar (Blue Header)
          Container(
            height: 60,
            color: const Color(0xFF0D6EFD), // Blue
            child: Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.menu, color: Colors.white),
                  onPressed: () {
                    setState(() {
                      _sidebarCollapsed = !_sidebarCollapsed;
                    });
                  },
                ),
                const Text(
                  'Tyazubwenge Management System',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const Spacer(),
                Row(
                  children: [
                    const Icon(Icons.person, color: Colors.white, size: 20),
                    const SizedBox(width: 8),
                    Text(
                      user?['full_name'] ??
                          user?['username'] ??
                          'System Administrator',
                      style: const TextStyle(color: Colors.white, fontSize: 14),
                    ),
                    const SizedBox(width: 8),
                    const Icon(Icons.arrow_drop_down, color: Colors.white),
                  ],
                ),
                const SizedBox(width: 16),
              ],
            ),
          ),
          // Main Content Row
          Expanded(
            child: Row(
              children: [
                // Sidebar
                AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  width: _sidebarCollapsed ? 0 : 260,
                  color: Colors.white,
                  child: _sidebarCollapsed
                      ? const SizedBox.shrink()
                      : Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.1),
                                blurRadius: 4,
                                offset: const Offset(2, 0),
                              ),
                            ],
                          ),
                          child: Column(
                            children: [
                              // Sidebar Header
                              Container(
                                padding: const EdgeInsets.all(20),
                                decoration: const BoxDecoration(
                                  border: Border(
                                    bottom: BorderSide(
                                        color: Color(0xFFE9ECEF), width: 1),
                                  ),
                                ),
                                child: Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text(
                                      'Tyazubwenge Management System',
                                      style: TextStyle(
                                        fontSize: 14,
                                        fontWeight: FontWeight.w600,
                                        color: Color(0xFF0D6EFD),
                                      ),
                                    ),
                                    IconButton(
                                      icon: const Icon(Icons.close, size: 18),
                                      onPressed: () {
                                        setState(() {
                                          _sidebarCollapsed = true;
                                        });
                                      },
                                      padding: EdgeInsets.zero,
                                      constraints: const BoxConstraints(),
                                    ),
                                  ],
                                ),
                              ),
                              // Navigation Menu
                              Expanded(
                                child: ListView.builder(
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 10),
                                  itemCount: _navItems.length,
                                  itemBuilder: (context, index) {
                                    final item = _navItems[index];
                                    final isSelected = _selectedIndex == index;
                                    return Container(
                                      margin: const EdgeInsets.symmetric(
                                          horizontal: 8, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: isSelected
                                            ? const Color(
                                                0xFFE7F1FF) // Light blue background
                                            : Colors.transparent,
                                        borderRadius: BorderRadius.circular(8),
                                        border: Border(
                                          left: BorderSide(
                                            color: isSelected
                                                ? const Color(0xFF0D6EFD)
                                                : Colors.transparent,
                                            width: 3,
                                          ),
                                        ),
                                      ),
                                      child: ListTile(
                                        leading: Icon(
                                          item.icon,
                                          color: isSelected
                                              ? const Color(0xFF0D6EFD)
                                              : const Color(0xFF495057),
                                          size: 20,
                                        ),
                                        title: Text(
                                          item.label,
                                          style: TextStyle(
                                            color: isSelected
                                                ? const Color(0xFF0D6EFD)
                                                : const Color(0xFF495057),
                                            fontWeight: isSelected
                                                ? FontWeight.w600
                                                : FontWeight.normal,
                                            fontSize: 14,
                                          ),
                                        ),
                                        onTap: () {
                                          setState(() {
                                            _selectedIndex = index;
                                          });
                                        },
                                        contentPadding:
                                            const EdgeInsets.symmetric(
                                          horizontal: 20,
                                          vertical: 8,
                                        ),
                                      ),
                                    );
                                  },
                                ),
                              ),
                              // Logout Button
                              Container(
                                padding: const EdgeInsets.all(16),
                                decoration: const BoxDecoration(
                                  border: Border(
                                    top: BorderSide(
                                        color: Color(0xFFE9ECEF), width: 1),
                                  ),
                                ),
                                child: SizedBox(
                                  width: double.infinity,
                                  child: ElevatedButton.icon(
                                    onPressed: () async {
                                      await authService.logout();
                                      if (!mounted) return;
                                      // Navigate to login screen
                                      // ignore: use_build_context_synchronously
                                      Navigator.of(context).pushAndRemoveUntil(
                                        MaterialPageRoute(
                                          builder: (_) => const LoginScreen(),
                                        ),
                                        (route) =>
                                            false, // Remove all previous routes
                                      );
                                    },
                                    icon: const Icon(Icons.logout, size: 18),
                                    label: const Text('Logout'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.red,
                                      foregroundColor: Colors.white,
                                      padding: const EdgeInsets.symmetric(
                                          vertical: 12),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                ),
                // Main Content Area
                Expanded(
                  child: _screens[_selectedIndex],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class NavigationItem {
  final IconData icon;
  final String label;

  NavigationItem({required this.icon, required this.label});
}
