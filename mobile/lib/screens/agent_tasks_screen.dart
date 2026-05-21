import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_animate/flutter_animate.dart';
import '../utils/app_theme.dart';
import '../services/auth_service.dart';
import '../services/wordpress_service.dart';

class AgentTasksScreen extends StatefulWidget {
  final Map<String, dynamic> user;
  final VoidCallback onLogout;

  const AgentTasksScreen({
    super.key, 
    required this.user, 
    required this.onLogout,
  });

  @override
  State<AgentTasksScreen> createState() => _AgentTasksScreenState();
}

class _AgentTasksScreenState extends State<AgentTasksScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _dashboardData;
  List<dynamic> _tasks = [];
  List<dynamic> _withdrawals = [];
  String _activeTab = 'tasks'; // 'tasks' or 'withdrawals'

  @override
  void initState() {
    super.initState();
    _fetchAgentData();
  }

  Future<void> _fetchAgentData() async {
    setState(() => _isLoading = true);
    try {
      final userId = widget.user['id'];
      final response = await http.get(
        Uri.parse("${WordPressService.apiBaseUrl}/get_agent_tasks.php?user_id=$userId"),
      );
      
      final data = json.decode(response.body);
      if (data['success'] == true) {
        setState(() {
          _dashboardData = data['agent'];
          _tasks = data['tasks'] ?? [];
          _withdrawals = data['withdrawals'] ?? [];
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
        _showErrorSnackBar(data['message'] ?? "Erreur de chargement");
      }
    } catch (e) {
      debugPrint("Error: $e");
      setState(() => _isLoading = false);
      _showErrorSnackBar("Une erreur est survenue: $e");
    }
  }

  Future<void> _updateTaskStatus(int taskId, String nextStatus) async {
    try {
      final userId = widget.user['id'];
      final response = await http.post(
        Uri.parse("${WordPressService.apiBaseUrl}/update_task_status.php"),
        body: {
          'task_id': taskId.toString(),
          'user_id': userId.toString(),
          'status': nextStatus,
        },
      );

      final data = json.decode(response.body);
      if (data['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              nextStatus == 'termine' 
                  ? "Mission accomplie ! Félicitations." 
                  : "Mission démarrée avec succès.",
            ),
            backgroundColor: Colors.green,
          ),
        );
        _fetchAgentData();
      } else {
        _showErrorSnackBar(data['message'] ?? "Erreur de mise à jour");
      }
    } catch (e) {
      _showErrorSnackBar("Une erreur est survenue: $e");
    }
  }

  void _showErrorSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.redAccent),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        backgroundColor: AppTheme.backgroundColor,
        body: Center(
          child: CircularProgressIndicator(color: AppTheme.primaryColor),
        ),
      );
    }

    final pendingBonuses = _dashboardData?['pending_bonuses'] ?? 0.0;
    final salary = _dashboardData?['salary'] ?? 0.0;
    final totalMonthly = _dashboardData?['total_monthly_revenue'] ?? 0.0;

    final inProgressCount = _tasks.where((t) => t['status'] == 'en_cours').length;
    final completedCount = _tasks.where((t) => t['status'] == 'termine').length;

    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      appBar: AppBar(
        title: const Text(
          'ESPACE AGENT',
          style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1.5, fontSize: 16),
        ),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: AppTheme.primaryColor),
            onPressed: _fetchAgentData,
          ),
          IconButton(
            icon: const Icon(Icons.logout_rounded, color: Colors.redAccent),
            onPressed: () async {
              await AuthService().logout();
              widget.onLogout();
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // User Header Info
            Row(
              children: [
                CircleAvatar(
                  radius: 28,
                  backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                  child: const Icon(Icons.support_agent_rounded, color: AppTheme.primaryColor, size: 28),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        widget.user['name'] ?? 'Agent WMA',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const Text(
                        'Membre de l\'équipe',
                        style: TextStyle(color: AppTheme.textGrey, fontSize: 12),
                      ),
                    ],
                  ),
                ),
              ],
            ).animate().fadeIn(duration: 400.ms),

            const SizedBox(height: 24),

            // Revenue Card (Salary + Bonuses)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    AppTheme.primaryColor.withValues(alpha: 0.2),
                    Colors.orange.withValues(alpha: 0.05),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: AppTheme.primaryColor.withValues(alpha: 0.3)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'REVENU MENSUEL ESTIMÉ',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      color: AppTheme.primaryColor,
                      letterSpacing: 1.5,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${totalMonthly.toStringAsFixed(2)}\$',
                    style: const TextStyle(fontSize: 36, fontWeight: FontWeight.w900, color: Colors.white),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      _buildSubStat('Fixe', '${salary.toStringAsFixed(0)}\$'),
                      Container(width: 1, height: 24, color: Colors.white24),
                      _buildSubStat('Bonus Cumulés', '${pendingBonuses.toStringAsFixed(2)}\$'),
                    ],
                  ),
                ],
              ),
            ).animate().fadeIn(delay: 150.ms).slideY(begin: 0.1, end: 0),

            const SizedBox(height: 20),

            // Task stats row
            Row(
              children: [
                Expanded(
                  child: _buildCountStatCard(
                    'EN COURS', 
                    inProgressCount.toString(), 
                    Colors.amberAccent,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildCountStatCard(
                    'TERMINÉES', 
                    completedCount.toString(), 
                    Colors.greenAccent,
                  ),
                ),
              ],
            ).animate().fadeIn(delay: 250.ms),

            const SizedBox(height: 28),

            // Tab selection (Missions vs Encaissements)
            Row(
              children: [
                _buildTabButton('Missions', 'tasks'),
                const SizedBox(width: 12),
                _buildTabButton('Encaissements', 'withdrawals'),
              ],
            ).animate().fadeIn(delay: 350.ms),

            const SizedBox(height: 16),

            // List area
            if (_activeTab == 'tasks') ...[
              if (_tasks.isEmpty)
                _buildEmptyState("Aucune mission attribuée pour le moment.")
              else
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: _tasks.length,
                  itemBuilder: (context, index) {
                    final task = _tasks[index];
                    return _buildTaskCard(task);
                  },
                ).animate().fadeIn(duration: 400.ms),
            ] else ...[
              if (_withdrawals.isEmpty)
                _buildEmptyState("Aucun historique d'encaissement trouvé.")
              else
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: _withdrawals.length,
                  itemBuilder: (context, index) {
                    final withdrawal = _withdrawals[index];
                    return _buildWithdrawalCard(withdrawal);
                  },
                ).animate().fadeIn(duration: 400.ms),
            ],
            
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _buildSubStat(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(fontSize: 11, color: AppTheme.textGrey),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
        ),
      ],
    );
  }

  Widget _buildCountStatCard(String label, String count, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.02),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.05)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: AppTheme.textGrey, letterSpacing: 0.5),
          ),
          const SizedBox(height: 6),
          Text(
            count,
            style: TextStyle(fontSize: 28, fontWeight: FontWeight.w900, color: color),
          ),
        ],
      ),
    );
  }

  Widget _buildTabButton(String label, String tabKey) {
    final isActive = _activeTab == tabKey;
    return GestureDetector(
      onTap: () => setState(() => _activeTab = tabKey),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
        decoration: BoxDecoration(
          color: isActive ? AppTheme.primaryColor : Colors.white.withValues(alpha: 0.03),
          borderRadius: BorderRadius.circular(30),
          border: Border.all(
            color: isActive ? Colors.transparent : Colors.white.withValues(alpha: 0.05),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isActive ? Colors.white : AppTheme.textGrey,
            fontWeight: FontWeight.bold,
            fontSize: 13,
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState(String message) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(40),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.01),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.03)),
      ),
      child: Column(
        children: [
          const Icon(Icons.inbox_rounded, size: 40, color: AppTheme.textGrey),
          const SizedBox(height: 12),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppTheme.textGrey, fontSize: 13, height: 1.4),
          ),
        ],
      ),
    );
  }

  Widget _buildTaskCard(dynamic task) {
    final status = task['status'];
    final revenue = task['revenue'] ?? 0.0;
    
    Color statusColor = Colors.grey;
    String statusLabel = "Assigné";
    
    if (status == 'en_cours') {
      statusColor = Colors.amber;
      statusLabel = "En cours";
    } else if (status == 'termine') {
      statusColor = Colors.green;
      statusLabel = "Terminé";
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.03),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(
          color: status == 'termine' 
              ? Colors.green.withValues(alpha: 0.1) 
              : Colors.white.withValues(alpha: 0.05),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: statusColor.withValues(alpha: 0.2)),
                ),
                child: Text(
                  statusLabel.toUpperCase(),
                  style: TextStyle(color: statusColor, fontSize: 9, fontWeight: FontWeight.bold, letterSpacing: 0.5),
                ),
              ),
              if (revenue > 0)
                Text(
                  '+${revenue.toStringAsFixed(0)}\$',
                  style: const TextStyle(color: Colors.orangeAccent, fontWeight: FontWeight.bold, fontSize: 13),
                ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            task['title'] ?? 'Mission sans titre',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, height: 1.3),
          ),
          const SizedBox(height: 8),
          Text(
            task['description'] ?? 'Aucune description fournie.',
            style: const TextStyle(color: AppTheme.textGrey, fontSize: 12, height: 1.4),
            maxLines: 3,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 16),
          
          // Action button depending on status
          if (status == 'assignee') ...[
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => _updateTaskStatus(task['id'], 'en_cours'),
                icon: const Icon(Icons.play_arrow_rounded, color: Colors.white),
                label: const Text('DÉMARRER LA MISSION', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.primaryColor,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ),
          ] else if (status == 'en_cours') ...[
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => _updateTaskStatus(task['id'], 'termine'),
                icon: const Icon(Icons.check_circle_outline_rounded, color: Colors.white),
                label: const Text('MARQUER COMME TERMINÉE', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ),
          ] else if (status == 'termine') ...[
            Row(
              children: [
                const Icon(Icons.check_circle, color: Colors.green, size: 18),
                const SizedBox(width: 8),
                const Text(
                  'Validée & Complétée',
                  style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 12),
                ),
                const Spacer(),
                // Stars rating
                Row(
                  children: List.generate(5, (starIndex) {
                    final rating = task['rating'] ?? 3;
                    return Icon(
                      starIndex < rating ? Icons.star_rounded : Icons.star_border_rounded,
                      color: Colors.amber,
                      size: 16,
                    );
                  }),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildWithdrawalCard(dynamic withdrawal) {
    final amount = withdrawal['montant'] ?? 0.0;
    final rawDate = withdrawal['date_encaissement'] ?? '';
    
    // Formatting date lightly
    String dateStr = rawDate;
    try {
      final parsed = DateTime.parse(rawDate);
      dateStr = "${parsed.day.toString().padLeft(2, '0')}/${parsed.month.toString().padLeft(2, '0')}/${parsed.year}";
    } catch (_) {}

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.02),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.white.withValues(alpha: 0.04)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'SALAIRE & BONUS CONFIRMÉ',
                style: TextStyle(fontSize: 9, color: Colors.greenAccent, fontWeight: FontWeight.bold, letterSpacing: 0.5),
              ),
              const SizedBox(height: 4),
              Text(
                dateStr,
                style: const TextStyle(color: AppTheme.textGrey, fontSize: 12),
              ),
            ],
          ),
          Text(
            '${amount.toStringAsFixed(0)}\$',
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Colors.greenAccent),
          ),
        ],
      ),
    );
  }
}
