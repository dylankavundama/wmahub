import 'dart:convert';
import 'dart:io';
import 'package:crypto/crypto.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import 'package:flutter/foundation.dart';

class AudioCacheService {
  static final AudioCacheService _instance = AudioCacheService._internal();
  factory AudioCacheService() => _instance;
  AudioCacheService._internal();

  String _getFileName(String url) {
    final uri = Uri.parse(url);
    final pathSegments = uri.pathSegments;
    String ext = '.mp3';
    if (pathSegments.isNotEmpty) {
      final lastSegment = pathSegments.last;
      final dotIndex = lastSegment.lastIndexOf('.');
      if (dotIndex != -1) {
        ext = lastSegment.substring(dotIndex);
      }
    }
    final hash = md5.convert(utf8.encode(url)).toString();
    return '$hash$ext';
  }

  Future<String> _getLocalPath(String url) async {
    final cacheDir = await getTemporaryDirectory();
    final fileName = _getFileName(url);
    return '${cacheDir.path}/$fileName';
  }

  /// Checks if the audio is already present in local cache.
  Future<bool> isAudioCached(String url) async {
    try {
      final localPath = await _getLocalPath(url);
      final file = File(localPath);
      return await file.exists() && await file.length() > 0;
    } catch (_) {
      return false;
    }
  }

  /// Returns the cached file path if it exists, otherwise returns null.
  Future<String?> getCachedPath(String url) async {
    try {
      final localPath = await _getLocalPath(url);
      final file = File(localPath);
      if (await file.exists() && await file.length() > 0) {
        return localPath;
      }
    } catch (e) {
      debugPrint("Error checking cached path: $e");
    }
    return null;
  }

  /// Returns the cached path if it exists, or downloads, caches, and returns it.
  Future<String> getOrDownloadAudio(String url) async {
    final localPath = await _getLocalPath(url);
    final file = File(localPath);

    if (await file.exists() && await file.length() > 0) {
      debugPrint("Audio loaded from cache: $localPath");
      return localPath;
    }

    debugPrint("Downloading audio to cache: $url");
    try {
      final response = await http.get(Uri.parse(url));
      if (response.statusCode == 200) {
        await file.writeAsBytes(response.bodyBytes);
        debugPrint("Audio successfully cached at: $localPath");
        return localPath;
      } else {
        throw HttpException("Failed to download audio. Status code: ${response.statusCode}");
      }
    } catch (e) {
      debugPrint("Error during audio caching: $e");
      if (await file.exists()) {
        try {
          await file.delete();
        } catch (_) {}
      }
      rethrow;
    }
  }
}
