import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

class CacheService {
  static Future<void> save(String key, dynamic data) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(key, json.encode(data));
    } catch (e) {
      // ignore
    }
  }

  static Future<dynamic> load(String key) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final stringData = prefs.getString(key);
      if (stringData != null) {
        return json.decode(stringData);
      }
    } catch (e) {
      // ignore
    }
    return null;
  }

  static Future<void> clearAll() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final keys = prefs.getKeys();
      for (final key in keys) {
        if (key.startsWith('cache_')) {
          await prefs.remove(key);
        }
      }
    } catch (e) {
      // ignore
    }
  }
}
