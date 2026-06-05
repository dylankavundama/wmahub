import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'wordpress_service.dart';

class FavoritesService {
  static const String _favPostsKey = 'favorite_posts';
  static const String _favProjectsKey = 'favorite_projects';

  // ─────────────────────────────────────────────
  // POSTS (Actualités)
  // ─────────────────────────────────────────────

  Future<List<dynamic>> getFavorites() async {
    final prefs = await SharedPreferences.getInstance();
    final String? favsJson = prefs.getString(_favPostsKey);
    if (favsJson == null) return [];
    return json.decode(favsJson);
  }

  Future<void> toggleFavorite(dynamic post) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = await getFavorites();

    final int index =
        favorites.indexWhere((item) => item['id'].toString() == post['id'].toString());

    if (index >= 0) {
      favorites.removeAt(index);
    } else {
      favorites.add(post);
    }

    await prefs.setString(_favPostsKey, json.encode(favorites));
  }

  Future<bool> isFavorite(int postId) async {
    final favorites = await getFavorites();
    return favorites.any((item) => item['id'].toString() == postId.toString());
  }

  Future<void> removePostFavorite(int postId) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = await getFavorites();
    favorites.removeWhere((item) => item['id'].toString() == postId.toString());
    await prefs.setString(_favPostsKey, json.encode(favorites));
  }

  // ─────────────────────────────────────────────
  // PROJECTS (Projets musicaux)
  // ─────────────────────────────────────────────

  Future<List<dynamic>> getProjectFavorites() async {
    final prefs = await SharedPreferences.getInstance();
    final String? favsJson = prefs.getString(_favProjectsKey);
    if (favsJson == null) return [];
    return json.decode(favsJson);
  }

  Future<void> toggleProjectFavorite(dynamic project) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = await getProjectFavorites();

    final int index =
        favorites.indexWhere((item) => item['id'].toString() == project['id'].toString());

    if (index >= 0) {
      favorites.removeAt(index);
    } else {
      favorites.add(project);
    }

    await prefs.setString(_favProjectsKey, json.encode(favorites));
  }

  Future<bool> isProjectFavorite(int projectId) async {
    final favorites = await getProjectFavorites();
    return favorites.any((item) => item['id'].toString() == projectId.toString());
  }

  Future<void> removeProjectFavorite(int projectId) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = await getProjectFavorites();
    favorites.removeWhere((item) => item['id'].toString() == projectId.toString());
    await prefs.setString(_favProjectsKey, json.encode(favorites));
  }

  // ─────────────────────────────────────────────
  // Helper : Compteur de vues stable et déterministe
  // ─────────────────────────────────────────────

  /// Retourne un nombre de vues stable basé sur l'id du contenu.
  /// La formule garantit un résultat entre 120 et 970 pour tout id.
  static int getViewCount(int id) {
    return (id * 13 + 37) % 850 + 120;
  }

  Future<int> getPostViewCount(int postId) async {
    return WordPressService().fetchPostViewCount(postId);
  }

  Future<void> incrementPostViewCount(int postId) async {
    await WordPressService().incrementPostViewCount(postId);
  }

  // ─────────────────────────────────────────────
  // Totaux combinés (pour la page Favoris)
  // ─────────────────────────────────────────────

  Future<int> getTotalFavoritesCount() async {
    final posts = await getFavorites();
    final projects = await getProjectFavorites();
    return posts.length + projects.length;
  }
}
