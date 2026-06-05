import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import 'cache_service.dart';

class WordPressService {
  static const String baseUrl = "https://wmahub.com/blog/wp-json/wp/v2";

  static String get apiBaseUrl {
    // AUTO-DETECTION: Points to Local XAMPP on Emulator, otherwise Production
    if (kDebugMode && !kIsWeb) {
      // return "http://10.0.2.2/wmahub/api"; // Descommentez pour tester en local sur Android
    }
    return "https://wmahub.com/api";
  }

  Future<List<dynamic>> fetchPosts({int page = 1, int perPage = 10}) async {
    try {
      final response = await http
          .get(Uri.parse("$baseUrl/posts?_embed&per_page=$perPage&page=$page"))
          .timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = json.decode(response.body) as List;
        if (page == 1) {
          await CacheService.save('cache_posts_page_1', data);
        }
        return data;
      }
    } catch (e) {
      debugPrint("Error fetching posts: $e");
    }
    if (page == 1) {
      final cached = await CacheService.load('cache_posts_page_1');
      if (cached is List) return cached;
    }
    return [];
  }

  Future<List<dynamic>> fetchHeroSlides() async {
    try {
      final url = "$apiBaseUrl/get_hero_slides.php";
      final response = await http
          .get(Uri.parse(url))
          .timeout(const Duration(seconds: 5)); // Shorter timeout for slider

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data is List && data.isNotEmpty) {
          await CacheService.save('cache_hero_slides', data);
          return data;
        }
      }
    } catch (e) {
      debugPrint("Slider API Error (Falling back): $e");
    }
    final cached = await CacheService.load('cache_hero_slides');
    if (cached is List) return cached;
    return [];
  }

  Future<Map<String, dynamic>> fetchAboutInfo() async {
    try {
      final url = "$apiBaseUrl/get_about_info.php";
      final response = await http
          .get(Uri.parse(url))
          .timeout(const Duration(seconds: 5)); // Shorter timeout for about

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data is Map<String, dynamic>) {
          await CacheService.save('cache_about_info', data);
          return data;
        }
      }
    } catch (e) {
      debugPrint("About API Error (Falling back): $e");
    }
    final cached = await CacheService.load('cache_about_info');
    if (cached is Map<String, dynamic>) return cached;
    return {};
  }

  Future<List<dynamic>> fetchDistributions() async {
    try {
      final url = "$apiBaseUrl/get_distributions.php";
      final response = await http
          .get(Uri.parse(url))
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data is List) {
          await CacheService.save('cache_distributions', data);
          return data;
        }
      }
    } catch (e) {
      debugPrint("Distributions API Error: $e");
    }
    final cached = await CacheService.load('cache_distributions');
    if (cached is List) return cached;
    return [];
  }

  Future<List<dynamic>> fetchLatestDistributed() async {
    try {
      final url = "$apiBaseUrl/get_latest_distributed.php";
      final response = await http
          .get(Uri.parse(url))
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data is List) {
          await CacheService.save('cache_latest_distributed', data);
          return data;
        }
      }
    } catch (e) {
      debugPrint("Latest Distributed API Error: $e");
    }
    final cached = await CacheService.load('cache_latest_distributed');
    if (cached is List) return cached;
    return [];
  }

  Future<List<dynamic>> fetchAllDistributed({int page = 1, int limit = 20}) async {
    try {
      final url = "$apiBaseUrl/get_all_distributed.php?page=$page&limit=$limit";
      final response = await http
          .get(Uri.parse(url))
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data is List) {
          if (page == 1) {
            await CacheService.save('cache_all_distributed_page_1', data);
          }
          return data;
        }
      }
    } catch (e) {
      debugPrint("All Distributed API Error: $e");
    }
    if (page == 1) {
      final cached = await CacheService.load('cache_all_distributed_page_1');
      if (cached is List) return cached;
    }
    return [];
  }

  Future<int> fetchPostViewCount(int postId) async {
    try {
      final url = "$apiBaseUrl/post_views.php?post_id=$postId";
      final response = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return data['total_views'] as int;
        }
      }
    } catch (e) {
      debugPrint("Error fetching post view count: $e");
    }
    return (postId * 13 + 37) % 850 + 120;
  }

  Future<int> incrementPostViewCount(int postId) async {
    try {
      final url = "$apiBaseUrl/post_views.php?post_id=$postId";
      final response = await http.post(Uri.parse(url)).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return data['total_views'] as int;
        }
      }
    } catch (e) {
      debugPrint("Error incrementing post view count: $e");
    }
    return (postId * 13 + 37) % 850 + 120;
  }

  Future<Map<int, int>> fetchPostViewsBatch(List<int> postIds) async {
    if (postIds.isEmpty) return {};
    try {
      final idsStr = postIds.join(',');
      final url = "$apiBaseUrl/post_views.php?post_ids=$idsStr";
      final response = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['views'] != null) {
          final Map<String, dynamic> rawViews = data['views'];
          final Map<int, int> viewsMap = {};
          rawViews.forEach((key, value) {
            final id = int.tryParse(key);
            if (id != null) {
              viewsMap[id] = value as int;
            }
          });
          return viewsMap;
        }
      }
    } catch (e) {
      debugPrint("Error fetching batch post view counts: $e");
    }
    return {};
  }

  Future<Map<String, dynamic>> checkContractStatus(int userId) async {
    try {
      final url = "$apiBaseUrl/contract.php?user_id=$userId";
      final response = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        return json.decode(response.body) as Map<String, dynamic>;
      }
    } catch (e) {
      debugPrint("Error checking contract status: $e");
    }
    return {"success": false, "signed": false};
  }

  Future<bool> saveContractSignature(int userId, String signatureData) async {
    try {
      final url = "$apiBaseUrl/contract.php";
      final response = await http.post(
        Uri.parse(url),
        body: {
          'user_id': userId.toString(),
          'signature_data': signatureData,
        },
      ).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['success'] == true;
      }
    } catch (e) {
      debugPrint("Error saving contract signature: $e");
    }
    return false;
  }
}
