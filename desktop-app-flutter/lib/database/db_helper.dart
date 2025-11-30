import 'dart:io';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as path;
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

class DatabaseHelper {
  static Database? _database;
  static bool _initialized = false;

  static Future<void> initialize() async {
    if (_initialized) return;

    if (Platform.isWindows || Platform.isLinux || Platform.isMacOS) {
      sqfliteFfiInit();
      databaseFactory = databaseFactoryFfi;
    }
    _initialized = true;
  }

  static Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDatabase();
    return _database!;
  }

  static Future<Database> _initDatabase() async {
    await initialize();

    String dbPath;
    if (Platform.isWindows || Platform.isLinux || Platform.isMacOS) {
      final directory = await getApplicationSupportDirectory();
      dbPath = path.join(directory.path, 'tyazubwenge.db');
    } else {
      final directory = await getDatabasesPath();
      dbPath = path.join(directory, 'tyazubwenge.db');
    }

    return await openDatabase(
      dbPath,
      version: 1,
      onCreate: _onCreate,
      onOpen: (db) {
        db.execute('PRAGMA foreign_keys = ON');
      },
    );
  }

  static Future<void> _onCreate(Database db, int version) async {
    await db.execute('PRAGMA foreign_keys = ON');

    // Users
    await db.execute('''
      CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY,
        username TEXT UNIQUE NOT NULL,
        email TEXT,
        full_name TEXT,
        role TEXT,
        branch_id INTEGER,
        server_synced INTEGER DEFAULT 0,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Products
    await db.execute('''
      CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        sku TEXT UNIQUE,
        barcode TEXT,
        description TEXT,
        category_id INTEGER,
        brand_id INTEGER,
        unit TEXT,
        cost_price REAL,
        retail_price REAL,
        wholesale_price REAL,
        min_stock_level REAL,
        status TEXT DEFAULT 'active',
        local_id TEXT,
        server_id INTEGER,
        server_synced INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Stock Inventory
    await db.execute('''
      CREATE TABLE IF NOT EXISTS stock_inventory (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        branch_id INTEGER,
        quantity REAL NOT NULL,
        unit TEXT,
        cost_price REAL,
        batch_number TEXT,
        expiry_date TEXT,
        supplier_id INTEGER,
        local_id TEXT UNIQUE,
        server_id INTEGER,
        sync_status TEXT DEFAULT 'pending',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id)
      )
    ''');

    // Customers
    await db.execute('''
      CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT,
        phone TEXT,
        address TEXT,
        loyalty_points INTEGER DEFAULT 0,
        credit_limit REAL DEFAULT 0,
        local_id TEXT UNIQUE,
        server_id INTEGER,
        sync_status TEXT DEFAULT 'pending',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Sales
    await db.execute('''
      CREATE TABLE IF NOT EXISTS sales (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        local_id TEXT UNIQUE,
        server_id INTEGER,
        invoice_number TEXT,
        customer_id INTEGER,
        branch_id INTEGER,
        user_id INTEGER,
        total_amount REAL,
        discount REAL DEFAULT 0,
        tax REAL DEFAULT 0,
        final_amount REAL,
        payment_method TEXT,
        payment_status TEXT DEFAULT 'paid',
        sale_type TEXT DEFAULT 'retail',
        notes TEXT,
        sync_status TEXT DEFAULT 'pending',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id)
      )
    ''');

    // Sale Items
    await db.execute('''
      CREATE TABLE IF NOT EXISTS sale_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sale_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        quantity REAL NOT NULL,
        unit TEXT,
        unit_price REAL NOT NULL,
        discount REAL DEFAULT 0,
        subtotal REAL NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
      )
    ''');

    // Categories
    await db.execute('''
      CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        description TEXT,
        server_synced INTEGER DEFAULT 0,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Brands
    await db.execute('''
      CREATE TABLE IF NOT EXISTS brands (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        description TEXT,
        server_synced INTEGER DEFAULT 0,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Suppliers
    await db.execute('''
      CREATE TABLE IF NOT EXISTS suppliers (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        email TEXT,
        phone TEXT,
        address TEXT,
        server_synced INTEGER DEFAULT 0,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Sync Queue
    await db.execute('''
      CREATE TABLE IF NOT EXISTS sync_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        entity_type TEXT NOT NULL,
        entity_id INTEGER NOT NULL,
        operation TEXT NOT NULL,
        data TEXT,
        retry_count INTEGER DEFAULT 0,
        status TEXT DEFAULT 'pending',
        error_message TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Sync Mappings
    await db.execute('''
      CREATE TABLE IF NOT EXISTS sync_mappings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        entity_type TEXT NOT NULL,
        local_id TEXT NOT NULL,
        server_id INTEGER NOT NULL,
        UNIQUE(entity_type, local_id)
      )
    ''');

    // Settings
    await db.execute('''
      CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Indexes
    await db.execute(
        'CREATE INDEX IF NOT EXISTS idx_stock_product ON stock_inventory(product_id)');
    await db.execute(
        'CREATE INDEX IF NOT EXISTS idx_stock_sync ON stock_inventory(sync_status)');
    await db.execute(
        'CREATE INDEX IF NOT EXISTS idx_sales_sync ON sales(sync_status)');
    await db.execute(
        'CREATE INDEX IF NOT EXISTS idx_customers_sync ON customers(sync_status)');
    await db.execute(
        'CREATE INDEX IF NOT EXISTS idx_sync_queue_status ON sync_queue(status)');
  }

  static Future<void> closeDatabase() async {
    if (_database != null) {
      await _database!.close();
      _database = null;
    }
  }
}
