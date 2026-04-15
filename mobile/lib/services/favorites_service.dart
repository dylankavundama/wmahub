import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

class FavoritesService {
  static const String _favKey = 'favorite_posts';

  Future<List<dynamic>> getFavorites() async {
    final prefs = await SharedPreferences.getInstance();
    final String? favsJson = prefs.getString(_favKey);
    if (favsJson == null) return [];
    return json.decode(favsJson);
  }

  Future<void> toggleFavorite(dynamic post) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = await getFavorites();

    final int index = favorites.indexWhere((item) => item['id'] == post['id']);

    if (index >= 0) {
      favorites.removeAt(index);
    } else {
      favorites.add(post);
    }

    await prefs.setString(_favKey, json.encode(favorites));
  }

  Future<bool> isFavorite(int postId) async {
    final favorites = await getFavorites();
    return favorites.any((item) => item['id'] == postId);
  }
}
