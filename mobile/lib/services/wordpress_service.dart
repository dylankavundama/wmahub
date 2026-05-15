import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

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
        return json.decode(response.body);
      } else {
        return [];
      }
    } catch (e) {
      debugPrint("Error fetching posts: $e");
      return [];
    }
  }

  Future<List<dynamic>> fetchHeroSlides() async {
    try {
      final url = "${apiBaseUrl}/get_hero_slides.php";
      final response = await http
          .get(Uri.parse(url))
          .timeout(const Duration(seconds: 5)); // Shorter timeout for slider

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data is List && data.isNotEmpty) {
          return data;
        }
      }
      return [];
    } catch (e) {
      debugPrint("Slider API Error (Falling back): $e");
      return [];
    }
  }

  Future<Map<String, dynamic>> fetchAboutInfo() async {
    try {
      final url = "${apiBaseUrl}/get_about_info.php";
      final response = await http
          .get(Uri.parse(url))
          .timeout(const Duration(seconds: 5)); // Shorter timeout for about

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint("About API Error (Falling back): $e");
    }
    return {};
  }

  Future<List<dynamic>> fetchDistributions() async {
    try {
      final url = "${apiBaseUrl}/get_distributions.php";
      final response = await http
          .get(Uri.parse(url))
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint("Distributions API Error: $e");
    }
    return [];
  }
}
