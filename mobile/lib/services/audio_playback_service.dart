import 'package:audioplayers/audioplayers.dart';
import 'package:flutter/foundation.dart';
import 'audio_cache_service.dart';
import 'notification_service.dart';

class AudioPlaybackService {
  static final AudioPlaybackService _instance = AudioPlaybackService._internal();
  factory AudioPlaybackService() => _instance;

  AudioPlaybackService._internal() {
    _init();
  }

  final AudioPlayer _audioPlayer = AudioPlayer();
  AudioPlayer get audioPlayer => _audioPlayer;

  final ValueNotifier<PlayerState> playerState = ValueNotifier<PlayerState>(PlayerState.stopped);
  final ValueNotifier<Duration> duration = ValueNotifier<Duration>(Duration.zero);
  final ValueNotifier<Duration> position = ValueNotifier<Duration>(Duration.zero);
  final ValueNotifier<Map<String, dynamic>?> currentProject = ValueNotifier<Map<String, dynamic>?>(null);
  final ValueNotifier<bool> isLoading = ValueNotifier<bool>(false);

  void _init() {
    _audioPlayer.onPlayerStateChanged.listen((state) {
      playerState.value = state;
      if (state == PlayerState.playing || state == PlayerState.paused || state == PlayerState.stopped) {
        isLoading.value = false;
      }

      final proj = currentProject.value;
      if (proj != null) {
        final String title = proj['title'] ?? 'Sans titre';
        final String artist = proj['artist_name'] ?? 'Artiste inconnu';
        if (state == PlayerState.playing) {
          NotificationService.showMusicNotification(
            title: title,
            artist: artist,
            isPlaying: true,
          );
        } else if (state == PlayerState.paused) {
          NotificationService.showMusicNotification(
            title: title,
            artist: artist,
            isPlaying: false,
          );
        } else if (state == PlayerState.stopped || state == PlayerState.completed) {
          NotificationService.cancelMusicNotification();
        }
      }
    });

    _audioPlayer.onDurationChanged.listen((newDuration) {
      duration.value = newDuration;
    });

    _audioPlayer.onPositionChanged.listen((newPosition) {
      position.value = newPosition;
    });
  }

  Future<void> play(Map<String, dynamic> project) async {
    final prevProject = currentProject.value;
    final isSame = prevProject != null && prevProject['id'] == project['id'];

    if (isSame) {
      if (playerState.value == PlayerState.paused) {
        await resume();
        return;
      } else if (playerState.value == PlayerState.playing) {
        await pause();
        return;
      }
    }

    // Stop current if playing
    await stop();

    currentProject.value = project;
    isLoading.value = true;

    // Use cover_path/cover_file and audio_path/file_path
    String? audioPath = project['audio_path'] ?? project['file_path'];
    if (audioPath != null && audioPath.isNotEmpty) {
      String url = "https://wmahub.com/dashboards/artiste/uploads/$audioPath";
      try {
        // 1. Check if already cached
        final cachedPath = await AudioCacheService().getCachedPath(url);
        if (cachedPath != null) {
          debugPrint("Playing from local cache: $cachedPath");
          await _audioPlayer.play(DeviceFileSource(cachedPath));
        } else {
          // 2. Play online stream directly (Buffering will show a loader)
          debugPrint("Playing online stream: $url");
          await _audioPlayer.play(UrlSource(url));

          // 3. Download/cache in background silently
          AudioCacheService().getOrDownloadAudio(url).catchError((err) {
            debugPrint("Silent background audio cache failed: $err");
          });
        }
      } catch (e) {
        isLoading.value = false;
        rethrow;
      }
    } else {
      isLoading.value = false;
      throw Exception("Aucun fichier audio trouvé pour ce projet");
    }
  }

  Future<void> pause() async {
    await _audioPlayer.pause();
  }

  Future<void> resume() async {
    await _audioPlayer.resume();
  }

  Future<void> stop() async {
    await _audioPlayer.stop();
    currentProject.value = null;
    duration.value = Duration.zero;
    position.value = Duration.zero;
    NotificationService.cancelMusicNotification();
  }

  Future<void> seek(Duration newPosition) async {
    await _audioPlayer.seek(newPosition);
  }
}
