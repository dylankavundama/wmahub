import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:audioplayers/audioplayers.dart';
import 'dart:io';
import 'package:flutter/services.dart';
import '../utils/app_theme.dart';
import '../services/notification_service.dart';
import '../services/audio_cache_service.dart';

class ProjectDetailScreen extends StatefulWidget {
  final Map<String, dynamic> project;

  const ProjectDetailScreen({super.key, required this.project});

  @override
  State<ProjectDetailScreen> createState() => _ProjectDetailScreenState();
}

class _ProjectDetailScreenState extends State<ProjectDetailScreen> {
  late AudioPlayer _audioPlayer;
  bool _isPlaying = false;
  bool _isLoadingAudio = false;
  bool _isCached = false;
  Duration _duration = Duration.zero;
  Duration _position = Duration.zero;



  @override
  void initState() {
    super.initState();
    _audioPlayer = AudioPlayer();
    _checkIfAudioCached();

    _audioPlayer.onPlayerStateChanged.listen((state) {
      if (mounted) {
        setState(() {
          _isPlaying = state == PlayerState.playing;
          if (_isPlaying ||
              state == PlayerState.paused ||
              state == PlayerState.stopped) {
            _isLoadingAudio = false;
          }
        });
      }

      final String title = widget.project['title'] ?? 'Sans titre';
      final String artist = widget.project['artist_name'] ?? 'Artiste inconnu';
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
    });

    _audioPlayer.onDurationChanged.listen((newDuration) {
      if (mounted) {
        setState(() {
          _duration = newDuration;
        });
      }
    });

    _audioPlayer.onPositionChanged.listen((newPosition) {
      if (mounted) {
        setState(() {
          _position = newPosition;
        });
      }
    });
  }

  Future<void> _checkIfAudioCached() async {
    String? audioPath = widget.project['audio_path'] ?? widget.project['file_path'];
    if (audioPath != null && audioPath.isNotEmpty) {
      String url = "https://wmahub.com/dashboards/artiste/uploads/$audioPath";
      final cached = await AudioCacheService().isAudioCached(url);
      if (mounted) {
        setState(() {
          _isCached = cached;
        });
      }
    }
  }



  @override
  void dispose() {
    NotificationService.cancelMusicNotification();
    _audioPlayer.dispose();
    super.dispose();
  }

  Future<void> _togglePlay() async {
    HapticFeedback.lightImpact();
    if (_isPlaying) {
      await _audioPlayer.pause();
    } else {
      String? audioPath =
          widget.project['audio_path'] ?? widget.project['file_path'];
      if (audioPath != null && audioPath.isNotEmpty) {
        String url = "https://wmahub.com/dashboards/artiste/uploads/$audioPath";

        setState(() {
          _isLoadingAudio = true;
        });

        try {
          final localPath = await AudioCacheService().getOrDownloadAudio(url);
          if (mounted) {
            setState(() {
              _isCached = true;
            });
          }
          await _audioPlayer.play(DeviceFileSource(localPath));
        } catch (e) {
          if (mounted) {
            setState(() {
              _isLoadingAudio = false;
            });
            String errorMsg = 'Erreur lors du chargement de l\'audio';
            if (e is SocketException || e is HttpException) {
              errorMsg = 'Connexion internet requise pour télécharger l\'audio';
            }
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(errorMsg),
              ),
            );
          }
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Aucun fichier audio disponible pour ce projet'),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = widget.project['status'] ?? 'en_attente';
    Color statusColor = AppTheme.primaryColor;
    if (status == 'distribue') statusColor = Colors.green;
    if (status == 'en_preparation') statusColor = Colors.blue;

    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      body: CustomScrollView(
        slivers: [
          _buildAppBar(context),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildHeader(),
                  const SizedBox(height: 24),
                  _buildAudioPlayer(),
                  const SizedBox(height: 32),
                  _buildStatusCard(status, statusColor),
                  const SizedBox(height: 32),
                  _buildInfoSection(),
                  const SizedBox(height: 32),
                  if (widget.project['details'] != null &&
                      widget.project['details'] != '')
                    _buildDetailsSection(),
                  const SizedBox(height: 40),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAppBar(BuildContext context) {
    return SliverAppBar(
      expandedHeight: 400,
      pinned: true,
      backgroundColor: AppTheme.backgroundColor,
      leading: IconButton(
        icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white),
        onPressed: () => Navigator.pop(context),
      ),
      flexibleSpace: FlexibleSpaceBar(
        background: Stack(
          fit: StackFit.expand,
          children: [
            Hero(
              tag: 'project_cover_${widget.project['id']}',
              child:
                  widget.project['cover_path'] != null &&
                      widget.project['cover_path'] != ""
                  ? CachedNetworkImage(
                      imageUrl:
                          "https://wmahub.com/dashboards/artiste/uploads/${widget.project['cover_path']}",
                      fit: BoxFit.cover,
                    )
                  : Container(
                      color: AppTheme.cardColor,
                      child: const Icon(
                        Icons.music_note,
                        size: 100,
                        color: AppTheme.primaryColor,
                      ),
                    ),
            ),
            Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.black.withValues(alpha: 0.3),
                    AppTheme.backgroundColor,
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    final String publisherName =
        widget.project['publisher_name'] ??
        widget.project['artist_name'] ??
        'l\'artiste';
    final String? photoUrl = widget.project['publisher_photo'];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          widget.project['title'] ?? 'Sans titre',
          style: const TextStyle(
            fontSize: 28,
            fontWeight: FontWeight.w900,
            color: Colors.white,
          ),
        ).animate().fadeIn().slideX(),
        const SizedBox(height: 12),
        Row(
          children: [
            Container(
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: AppTheme.primaryColor.withValues(alpha: 0.5),
                  width: 1.5,
                ),
              ),
              child: CircleAvatar(
                radius: 12,
                backgroundColor: AppTheme.cardColor,
                backgroundImage: photoUrl != null && photoUrl.isNotEmpty
                    ? CachedNetworkImageProvider(photoUrl)
                    : null,
                child: photoUrl == null || photoUrl.isEmpty
                    ? const Icon(Icons.person, size: 12, color: Colors.white70)
                    : null,
              ),
            ),
            const SizedBox(width: 8),
            RichText(
              text: TextSpan(
                style: const TextStyle(fontSize: 12, fontFamily: 'Outfit'),
                children: [
                  const TextSpan(
                    text: 'Publié par ',
                    style: TextStyle(color: AppTheme.textGrey),
                  ),
                  TextSpan(
                    text: publisherName,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ).animate().fadeIn(delay: 100.ms),
        const SizedBox(height: 12),
        Text(
          widget.project['artist_name'] ?? 'Artiste inconnu',
          style: const TextStyle(
            fontSize: 18,
            color: AppTheme.primaryColor,
            fontWeight: FontWeight.bold,
          ),
        ).animate().fadeIn(delay: 200.ms),
      ],
    );
  }

  Widget _buildAudioPlayer() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white.withValues(alpha: 0.05)),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'APERÇU AUDIO',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                  color: AppTheme.textGrey,
                  letterSpacing: 1.2,
                ),
              ),
              if (_isCached)
                const Row(
                  children: [
                    Icon(
                      Icons.offline_pin_rounded,
                      color: Colors.green,
                      size: 14,
                    ),
                    SizedBox(width: 4),
                    Text(
                      'Disponible hors ligne',
                      style: TextStyle(
                        color: Colors.green,
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              IconButton(
                onPressed: _isLoadingAudio ? null : _togglePlay,
                iconSize: 48,
                icon: _isLoadingAudio
                    ? const SizedBox(
                        width: 48,
                        height: 48,
                        child: CircularProgressIndicator(
                          color: AppTheme.primaryColor,
                        ),
                      )
                    : Icon(
                        _isPlaying
                            ? Icons.pause_circle_filled
                            : Icons.play_circle_filled,
                        color: AppTheme.primaryColor,
                      ),
              ),
              Expanded(
                child: Column(
                  children: [
                    Slider(
                      activeColor: AppTheme.primaryColor,
                      inactiveColor: Colors.white10,
                      min: 0,
                      max: _duration.inSeconds.toDouble() > 0
                          ? _duration.inSeconds.toDouble()
                          : 1.0,
                      value: _position.inSeconds.toDouble(),
                      onChanged: (value) async {
                        final position = Duration(seconds: value.toInt());
                        await _audioPlayer.seek(position);
                      },
                    ),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            _formatDuration(_position),
                            style: const TextStyle(
                              color: AppTheme.textGrey,
                              fontSize: 10,
                            ),
                          ),
                          Text(
                            _formatDuration(_duration),
                            style: const TextStyle(
                              color: AppTheme.textGrey,
                              fontSize: 10,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    ).animate().fadeIn(delay: 300.ms).slideY(begin: 0.1, end: 0);
  }

  String _formatDuration(Duration duration) {
    String twoDigits(int n) => n.toString().padLeft(2, "0");
    String twoDigitMinutes = twoDigits(duration.inMinutes.remainder(60));
    String twoDigitSeconds = twoDigits(duration.inSeconds.remainder(60));
    return "$twoDigitMinutes:$twoDigitSeconds";
  }

  Widget _buildStatusCard(String status, Color color) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          Icon(Icons.info_outline, color: color),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'STATUT ACTUEL',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textGrey,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  status.toUpperCase().replaceAll('_', ' '),
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    ).animate().fadeIn(delay: 400.ms);
  }

  Widget _buildInfoSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'INFORMATIONS GÉNÉRALES',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: AppTheme.primaryColor,
            letterSpacing: 1.2,
          ),
        ),
        const SizedBox(height: 20),
        _buildInfoRow(
          Icons.category_outlined,
          'Type',
          widget.project['project_type'] ?? 'Single',
        ),
        _buildInfoRow(
          Icons.music_note_outlined,
          'Genre',
          widget.project['genre'] ?? 'Afrobeats',
        ),
        _buildInfoRow(
          Icons.calendar_today_outlined,
          'Date de sortie',
          widget.project['date_sortie'] ?? 'Non définie',
        ),
        _buildInfoRow(
          Icons.language_outlined,
          'Langues',
          widget.project['languages'] ?? 'Non spécifié',
        ),
        _buildInfoRow(
          Icons.card_membership_outlined,
          'Pack Promo',
          widget.project['promo_pack'] ?? 'Aucun',
        ),
      ],
    ).animate().fadeIn(delay: 600.ms);
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        children: [
          Icon(icon, size: 20, color: AppTheme.textGrey),
          const SizedBox(width: 16),
          Text(label, style: const TextStyle(color: AppTheme.textGrey)),
          const Spacer(),
          Text(
            value,
            style: const TextStyle(
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailsSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'DÉTAILS DU PROJET',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: AppTheme.primaryColor,
            letterSpacing: 1.2,
          ),
        ),
        const SizedBox(height: 12),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: AppTheme.cardColor,
            borderRadius: BorderRadius.circular(20),
          ),
          child: Text(
            widget.project['details'],
            style: const TextStyle(color: AppTheme.textGrey, height: 1.5),
          ),
        ),
      ],
    ).animate().fadeIn(delay: 800.ms);
  }
}
