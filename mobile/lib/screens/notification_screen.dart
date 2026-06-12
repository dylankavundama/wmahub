import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_animate/flutter_animate.dart';
import '../utils/app_theme.dart';
import '../services/wordpress_service.dart';
import '../services/cache_service.dart';

class NotificationScreen extends StatefulWidget {
  final int userId;
  const NotificationScreen({super.key, required this.userId});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  List _notifications = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  Future<void> _fetchNotifications({bool isBackground = false}) async {
    if (_notifications.isEmpty && !isBackground) {
      setState(() => _isLoading = true);
    }

    if (_notifications.isEmpty) {
      final cached = await CacheService.load('cache_notifications_${widget.userId}');
      if (cached is List && cached.isNotEmpty && mounted) {
        setState(() {
          _notifications = cached;
          _isLoading = false;
        });
      }
    }

    try {
      final response = await http.get(
        Uri.parse("${WordPressService.apiBaseUrl}/get_notifications.php?user_id=${widget.userId}"),
      ).timeout(const Duration(seconds: 10));

      final data = json.decode(response.body);
      if (data['success'] == true) {
        await CacheService.save('cache_notifications_${widget.userId}', data['data']);
        if (mounted) {
          setState(() {
            _notifications = data['data'];
            _isLoading = false;
          });
        }
      }
    } catch (e) {
      debugPrint("Error: $e");
      final cached = await CacheService.load('cache_notifications_${widget.userId}');
      if (mounted && cached != null) {
        setState(() {
          _notifications = cached;
          _isLoading = false;
        });
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: const Text('NOTIFICATIONS', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: _isLoading 
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryColor))
          : _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_notifications.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.notifications_none_rounded, size: 80, color: Colors.white.withOpacity(0.05)),
            const SizedBox(height: 24),
            const Text(
              'AUCUNE NOTIFICATION',
              style: TextStyle(color: Colors.white24, fontWeight: FontWeight.bold, letterSpacing: 2),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchNotifications,
      color: AppTheme.primaryColor,
      child: ListView.separated(
        padding: const EdgeInsets.all(24),
        itemCount: _notifications.length,
        separatorBuilder: (c, i) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final n = _notifications[index];
          bool isRead = n['is_read'] == "1" || n['is_read'] == 1;

          return Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppTheme.cardColor,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: isRead ? Colors.white.withOpacity(0.03) : AppTheme.primaryColor.withOpacity(0.2)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: (isRead ? Colors.white10 : AppTheme.primaryColor.withOpacity(0.1)),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    _getIconForType(n['type']),
                    color: isRead ? Colors.white38 : AppTheme.primaryColor,
                    size: 18,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        n['message'] ?? '',
                        style: TextStyle(
                          color: isRead ? Colors.white70 : Colors.white,
                          fontSize: 14,
                          fontWeight: isRead ? FontWeight.normal : FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        _formatDate(n['created_at']),
                        style: const TextStyle(color: AppTheme.textGrey, fontSize: 10),
                      ),
                    ],
                  ),
                ),
                if (!isRead)
                  Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(color: AppTheme.primaryColor, shape: BoxShape.circle),
                  ),
              ],
            ),
          ).animate().fadeIn(delay: (index * 50).ms).slideX(begin: 0.05, end: 0);
        },
      ),
    );
  }

  IconData _getIconForType(String? type) {
    switch (type) {
      case 'project_update': return Icons.album_rounded;
      case 'payment': return Icons.account_balance_wallet_rounded;
      case 'new_project': return Icons.cloud_upload_rounded;
      default: return Icons.notifications_rounded;
    }
  }

  String _formatDate(String? dateStr) {
    if (dateStr == null) return '';
    try {
      final date = DateTime.parse(dateStr);
      return "${date.day}/${date.month}/${date.year} à ${date.hour}:${date.minute.toString().padLeft(2, '0')}";
    } catch (e) {
      return dateStr;
    }
  }
}
