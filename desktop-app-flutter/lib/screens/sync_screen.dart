import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/sync_service.dart';

class SyncScreen extends StatefulWidget {
  const SyncScreen({super.key});

  @override
  State<SyncScreen> createState() => _SyncScreenState();
}

class _SyncScreenState extends State<SyncScreen> {
  bool _isSyncing = false;
  Map<String, dynamic>? _lastSyncResult;

  Future<void> _handleSync() async {
    setState(() {
      _isSyncing = true;
      _lastSyncResult = null;
    });

    try {
      final syncService = Provider.of<SyncService>(context, listen: false);
      final result = await syncService.syncNow();
      
      setState(() {
        _lastSyncResult = result;
        _isSyncing = false;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['success'] == true
                ? 'Sync completed! Synced: ${result['synced']}, Failed: ${result['failed']}'
                : 'Sync failed: ${result['message']}'),
          ),
        );
      }
    } catch (e) {
      setState(() {
        _isSyncing = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }

  Future<void> _handleDownload() async {
    setState(() {
      _isSyncing = true;
      _lastSyncResult = null;
    });

    try {
      final syncService = Provider.of<SyncService>(context, listen: false);
      
      // Check connection first
      await syncService.checkConnection();
      if (!syncService.isOnline) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('No internet connection')),
          );
        }
        setState(() {
          _isSyncing = false;
        });
        return;
      }

      final result = await syncService.downloadUpdates();
      
      setState(() {
        _isSyncing = false;
      });

      if (mounted) {
        if (result['success'] == true) {
          final data = result['data'] as Map<String, dynamic>? ?? {};
          final productCount = (data['products'] as List?)?.length ?? 0;
          final stockCount = (data['stock'] as List?)?.length ?? 0;
          final customerCount = (data['customers'] as List?)?.length ?? 0;
          
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                  'Data updated! Products: $productCount, Stock: $stockCount, Customers: $customerCount'),
              duration: const Duration(seconds: 3),
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Download failed: ${result['message'] ?? 'Unknown error'}'),
            ),
          );
        }
      }
    } catch (e) {
      setState(() {
        _isSyncing = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final syncService = Provider.of<SyncService>(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Data Synchronization'),
        automaticallyImplyLeading: false,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(
                          syncService.isOnline ? Icons.cloud_done : Icons.cloud_off,
                          color: syncService.isOnline ? Colors.green : Colors.red,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          syncService.isOnline ? 'Online' : 'Offline',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: syncService.isOnline ? Colors.green : Colors.red,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: _isSyncing ? null : _handleSync,
                            icon: _isSyncing
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : const Icon(Icons.upload),
                            label: Text(_isSyncing ? 'Syncing...' : 'Upload Local Changes'),
                            style: ElevatedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 16),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: _isSyncing ? null : _handleDownload,
                            icon: _isSyncing
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : const Icon(Icons.download),
                            label: const Text('Download Updates'),
                            style: ElevatedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              backgroundColor: Colors.blue,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            if (_lastSyncResult != null) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Last Sync Result',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text('Synced: ${_lastSyncResult!['synced'] ?? 0}'),
                      Text('Failed: ${_lastSyncResult!['failed'] ?? 0}'),
                      if (_lastSyncResult!['errors'] != null &&
                          (_lastSyncResult!['errors'] as List).isNotEmpty) ...[
                        const SizedBox(height: 8),
                        const Text('Errors:', style: TextStyle(fontWeight: FontWeight.bold)),
                        ...(_lastSyncResult!['errors'] as List).map((error) => Text('  - $error')),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}


