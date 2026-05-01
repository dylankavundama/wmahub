import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_animate/flutter_animate.dart';
import '../utils/app_theme.dart';
import '../services/wordpress_service.dart';
import 'project_detail_screen.dart';
import 'admin_accounting_screen.dart';

class AdminProjectManagementScreen extends StatefulWidget {
  const AdminProjectManagementScreen({super.key});

  @override
  State<AdminProjectManagementScreen> createState() => _AdminProjectManagementScreenState();
}

class _AdminProjectManagementScreenState extends State<AdminProjectManagementScreen> {
  List _projects = [];
  bool _isLoading = true;
  String _searchQuery = "";
  String _currentFilter = 'all';
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchProjects();
  }

  Future<void> _fetchProjects() async {
    setState(() => _isLoading = true);
    try {
      final response = await http.get(
        Uri.parse("${WordPressService.apiBaseUrl}/get_all_projects_admin.php?search=$_searchQuery&status=$_currentFilter"),
      );
      final data = json.decode(response.body);
      if (data['success'] == true) {
        setState(() => _projects = data['projects']);
      }
    } catch (e) {
      debugPrint("Error: $e");
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: const Text('GESTION PROJETS', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: Column(
        children: [
          _buildSearchBar(),
          _buildFilterChips(),
          Expanded(
            child: _isLoading 
                ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryColor))
                : _buildProjectList(),
          ),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        backgroundColor: AppTheme.cardColor,
        selectedItemColor: AppTheme.primaryColor,
        unselectedItemColor: AppTheme.textGrey,
        currentIndex: 1,
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.calculate_rounded), label: 'Compta'),
          BottomNavigationBarItem(icon: Icon(Icons.library_music_rounded), label: 'Projets'),
        ],
        onTap: (index) {
          if (index == 0) {
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const AdminAccountingScreen()),
            );
          }
        },
      ),
    );
  }

  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      child: TextField(
        controller: _searchController,
        style: const TextStyle(color: Colors.white),
        decoration: InputDecoration(
          hintText: 'Rechercher un titre ou artiste...',
          hintStyle: const TextStyle(color: Colors.white24),
          prefixIcon: const Icon(Icons.search, color: AppTheme.primaryColor),
          suffixIcon: _searchQuery.isNotEmpty 
              ? IconButton(
                  icon: const Icon(Icons.clear, color: Colors.white24),
                  onPressed: () {
                    _searchController.clear();
                    setState(() => _searchQuery = "");
                    _fetchProjects();
                  },
                )
              : null,
          filled: true,
          fillColor: AppTheme.cardColor,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
          contentPadding: const EdgeInsets.symmetric(vertical: 16),
        ),
        onSubmitted: (value) {
          setState(() => _searchQuery = value);
          _fetchProjects();
        },
      ),
    ).animate().fadeIn().slideY(begin: -0.1, end: 0);
  }

  Widget _buildFilterChips() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      child: Row(
        children: [
          _filterChip('Tout', 'all'),
          const SizedBox(width: 8),
          _filterChip('En attente', 'en_attente'),
          const SizedBox(width: 8),
          _filterChip('Préparation', 'en_preparation'),
          const SizedBox(width: 8),
          _filterChip('Distribué', 'distribue'),
        ],
      ),
    );
  }

  Widget _filterChip(String label, String value) {
    bool isSelected = _currentFilter == value;
    return InkWell(
      onTap: () {
        setState(() => _currentFilter = value);
        _fetchProjects();
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primaryColor : AppTheme.cardColor,
          borderRadius: BorderRadius.circular(30),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : AppTheme.textGrey,
            fontWeight: FontWeight.bold,
            fontSize: 12,
          ),
        ),
      ),
    );
  }

  Widget _buildProjectList() {
    if (_projects.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.search_off_rounded, size: 64, color: Colors.white10),
            const SizedBox(height: 16),
            Text('Aucun projet trouvé', style: TextStyle(color: AppTheme.textGrey)),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(24),
      itemCount: _projects.length,
      itemBuilder: (context, index) {
        final project = _projects[index];
        return _buildProjectItem(project, index);
      },
    );
  }

  Widget _buildProjectItem(Map<String, dynamic> project, int index) {
    final status = project['status'] ?? 'en_attente';
    Color statusColor = AppTheme.primaryColor;
    if (status == 'distribue') statusColor = Colors.green;
    if (status == 'en_preparation') statusColor = Colors.blue;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white.withOpacity(0.05)),
      ),
      child: InkWell(
        onTap: () => Navigator.push(
          context,
          MaterialPageRoute(builder: (context) => ProjectDetailScreen(project: project)),
        ),
        borderRadius: BorderRadius.circular(24),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Project Mini Cover
              Container(
                width: 60,
                height: 60,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  image: project['cover_path'] != null && project['cover_path'] != ""
                      ? DecorationImage(
                          image: NetworkImage("https://wmahub.com/dashboards/artiste/uploads/${project['cover_path']}"),
                          fit: BoxFit.cover,
                        )
                      : null,
                  color: Colors.white10,
                ),
                child: project['cover_path'] == null || project['cover_path'] == ""
                    ? const Icon(Icons.music_note, color: Colors.white24)
                    : null,
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      project['title'] ?? 'Sans titre',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.white),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      project['artist_name'] ?? 'Artiste inconnu',
                      style: const TextStyle(color: AppTheme.textGrey, fontSize: 12),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: statusColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        status.toUpperCase().replaceAll('_', ' '),
                        style: TextStyle(color: statusColor, fontSize: 9, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.arrow_forward_ios_rounded, color: Colors.white10, size: 14),
            ],
          ),
        ),
      ),
    ).animate().fadeIn(delay: (index * 50).ms).slideX(begin: 0.1, end: 0);
  }
}
