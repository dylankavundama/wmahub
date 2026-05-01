import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:async';
import '../utils/app_theme.dart';
import 'create_project_screen.dart';
import 'project_detail_screen.dart';
import '../services/wordpress_service.dart';
import '../services/auth_service.dart';

class DistributionScreen extends StatefulWidget {
  const DistributionScreen({super.key});

  @override
  State<DistributionScreen> createState() => _DistributionScreenState();
}

class _DistributionScreenState extends State<DistributionScreen> {
  final _authService = AuthService();
  late Future<List<dynamic>> _projectsFuture;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _refreshProjects();

    // Auto-refresh every 5 minutes for projects
    _refreshTimer = Timer.periodic(const Duration(minutes: 5), (timer) {
      _refreshProjects();
    });
  }

  void _refreshProjects() {
    setState(() {
      _projectsFuture = _fetchProjects();
    });
  }

  Future<List<dynamic>> _fetchProjects() async {
    try {
      final user = await _authService.getCurrentUser();
      final userId = user?['id'] ?? 0;

      final response = await http
          .get(
            Uri.parse(
              "${WordPressService.apiBaseUrl}/get_user_projects.php?user_id=$userId",
            ),
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data is List ? data : [];
      }
    } catch (e) {
      debugPrint("Fetch Projects Error: $e");
    }
    return [];
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'MES DISTRIBUTIONS',
          style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16),
        ),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(
              Icons.refresh_rounded,
              color: AppTheme.primaryColor,
            ),
            onPressed: _refreshProjects,
          ),
        ],
      ),
      body: FutureBuilder<List<dynamic>>(
        future: _projectsFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: AppTheme.primaryColor),
            );
          }

          final projects = snapshot.data ?? [];
          if (projects.isEmpty) return _buildEmptyState();

          return ListView.builder(
            padding: const EdgeInsets.all(24),
            itemCount: projects.length,
            itemBuilder: (context, index) {
              final project = projects[index];
              return _buildProjectCard(project).animate().fadeIn().moveY(
                begin: 10,
                end: 0,
                delay: Duration(milliseconds: index * 100),
              );
            },
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          await Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => const CreateProjectScreen(),
            ),
          );
          _refreshProjects();
        },
        backgroundColor: AppTheme.primaryColor,
        label: const Text(
          'NOUVELLE DISTRIBUTION',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 13,
            color: Colors.white,
          ),
        ),
        icon: const Icon(Icons.add_rounded, color: Colors.white),
      ).animate().scale(delay: 500.ms),
    );
  }

  Widget _buildEmptyState() {
    return Padding(
      padding: const EdgeInsets.all(40),
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                color: AppTheme.primaryColor.withOpacity(0.05),
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.library_music_outlined,
                size: 80,
                color: AppTheme.primaryColor.withOpacity(0.5),
              ),
            ),
            const SizedBox(height: 32),
            const Text(
              'RIEN À SIGNALER',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w900,
                letterSpacing: 1,
              ),
            ),
            const SizedBox(height: 12),
            const Text(
              'Vous n\'avez pas encore de projet distribué. Commencez votre carrière ici.',
              textAlign: TextAlign.center,
              style: TextStyle(color: AppTheme.textGrey, fontSize: 14),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProjectCard(Map<String, dynamic> project) {
    final status = project['status'] ?? 'en_attente';
    Color statusColor = AppTheme.primaryColor;
    if (status == 'distribue') statusColor = Colors.green;
    if (status == 'en_preparation') statusColor = Colors.blue;

    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white10),
      ),
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => ProjectDetailScreen(project: project),
            ),
          );
        },
        borderRadius: BorderRadius.circular(24),
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Row(
            children: [
              Container(
                width: 64,
                height: 64,
                child:
                    project['cover_path'] != null && project['cover_path'] != ""
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(16),
                        child: CachedNetworkImage(
                          imageUrl:
                              "https://wmahub.com/dashboards/artiste/uploads/${project['cover_path']}",
                          fit: BoxFit.cover,
                          placeholder: (context, url) => Container(
                            color: Colors.white.withOpacity(0.05),
                            child: const Center(
                              child: CircularProgressIndicator(strokeWidth: 2),
                            ),
                          ),
                          errorWidget: (context, url, error) => const Icon(
                            Icons.music_note,
                            color: AppTheme.primaryColor,
                          ),
                        ),
                      )
                    : const Icon(
                        Icons.music_note,
                        color: AppTheme.primaryColor,
                      ),
              ),
              const SizedBox(width: 20),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      project['title'] ?? 'Sans titre',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      "${project['project_type'] ?? 'Single'} • ${project['artist_name'] ?? 'Artiste'}",
                      style: const TextStyle(
                        color: AppTheme.textGrey,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: statusColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        status.toUpperCase().replaceAll('_', ' '),
                        style: TextStyle(
                          color: statusColor,
                          fontSize: 9,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 0.5,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(
                Icons.chevron_right,
                color: AppTheme.textGrey,
                size: 24,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
