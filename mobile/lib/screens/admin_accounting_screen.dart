import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_animate/flutter_animate.dart';
import '../utils/app_theme.dart';
import '../services/wordpress_service.dart';
import '../services/auth_service.dart';
import '../main.dart';
import 'admin_project_management_screen.dart';

class AdminAccountingScreen extends StatefulWidget {
  const AdminAccountingScreen({super.key});

  @override
  State<AdminAccountingScreen> createState() => _AdminAccountingScreenState();
}

class _AdminAccountingScreenState extends State<AdminAccountingScreen> {
  bool _isLocked = true;
  final TextEditingController _pinController = TextEditingController();
  Map<String, dynamic>? _accountingData;
  bool _isLoading = false;
  String _currentFilter = 'all'; // all, income, expense

  @override
  void initState() {
    super.initState();
  }

  Future<void> _fetchData() async {
    setState(() => _isLoading = true);
    try {
      final response = await http.get(
        Uri.parse("${WordPressService.apiBaseUrl}/get_accounting_data.php"),
      );
      final data = json.decode(response.body);
      if (data['success'] == true) {
        setState(() => _accountingData = data['data']);
      }
    } catch (e) {
      debugPrint("Error: $e");
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _verifyPin() {
    if (_pinController.text == "1010") {
      setState(() => _isLocked = false);
      _fetchData();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Code PIN incorrect'), backgroundColor: Colors.red),
      );
      _pinController.clear();
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLocked) return _buildPinScreen();

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: const Text('GESTION COMPTABLE', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: AppTheme.primaryColor),
            onPressed: _fetchData,
          ),
          IconButton(
            icon: const Icon(Icons.logout_rounded, color: Colors.redAccent),
            onPressed: () async {
              final navigatorContext = context;
              await AuthService().logout();
              if (navigatorContext.mounted) {
                RestartWidget.restartApp(navigatorContext);
              }
            },
          ),
        ],
      ),
      body: _isLoading 
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryColor))
          : _buildDashboard(),
      floatingActionButton: FloatingActionButton(
        onPressed: _showAddTransactionDialog,
        backgroundColor: AppTheme.primaryColor,
        child: const Icon(Icons.add, color: Colors.white),
      ),
      bottomNavigationBar: BottomNavigationBar(
        backgroundColor: AppTheme.cardColor,
        selectedItemColor: AppTheme.primaryColor,
        unselectedItemColor: AppTheme.textGrey,
        currentIndex: 0,
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.calculate_rounded), label: 'Compta'),
          BottomNavigationBarItem(icon: Icon(Icons.library_music_rounded), label: 'Projets'),
        ],
        onTap: (index) {
          if (index == 1) {
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const AdminProjectManagementScreen()),
            );
          }
        },
      ),
    );
  }

  Widget _buildPinScreen() {
    return Scaffold(
      backgroundColor: Colors.black,
      body: Container(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.lock_outline_rounded, size: 80, color: AppTheme.primaryColor),
            const SizedBox(height: 24),
            const Text(
              'ACCÈS SÉCURISÉ',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, letterSpacing: 2, color: Colors.white),
            ),
            const SizedBox(height: 8),
            const Text(
              'Veuillez entrer votre code PIN pour continuer',
              textAlign: TextAlign.center,
              style: TextStyle(color: AppTheme.textGrey),
            ),
            const SizedBox(height: 40),
            TextField(
              controller: _pinController,
              obscureText: true,
              keyboardType: TextInputType.number,
              textAlign: TextAlign.center,
              maxLength: 4,
              style: const TextStyle(fontSize: 32, letterSpacing: 20, fontWeight: FontWeight.bold, color: Colors.white),
              decoration: InputDecoration(
                counterText: "",
                filled: true,
                fillColor: AppTheme.cardColor,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
              ),
              onChanged: (v) {
                if (v.length == 4) _verifyPin();
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDashboard() {
    final stats = _accountingData?['stats'];
    List transactions = _accountingData?['transactions'] as List? ?? [];
    
    // Filtrage
    if (_currentFilter != 'all') {
      transactions = transactions.where((t) => t['type'] == _currentFilter).toList();
    }

    return RefreshIndicator(
      onRefresh: _fetchData,
      color: AppTheme.primaryColor,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildBalanceCard(double.parse((stats?['balance'] ?? 0).toString())),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(child: _buildMiniStat('RECETTES', double.parse((stats?['total_income'] ?? 0).toString()), Colors.green)),
                const SizedBox(width: 16),
                Expanded(child: _buildMiniStat('DÉPENSES', double.parse((stats?['total_expense'] ?? 0).toString()), Colors.red)),
              ],
            ),
            const SizedBox(height: 32),
            _buildFilters(),
            const SizedBox(height: 24),
            const Text(
              'HISTORIQUE RÉCENT',
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.primaryColor, letterSpacing: 1.5),
            ),
            const SizedBox(height: 16),
            ...transactions.map((t) => _buildTransactionItem(t)).toList(),
            if (transactions.isEmpty)
               const Center(child: Padding(
                 padding: EdgeInsets.all(40.0),
                 child: Text('Aucune transaction trouvée', style: TextStyle(color: AppTheme.textGrey)),
               )),
            const SizedBox(height: 80), // Space for FAB
          ],
        ),
      ),
    );
  }

  Widget _buildFilters() {
    return Row(
      children: [
        _filterChip('Tout', 'all'),
        const SizedBox(width: 8),
        _filterChip('Recettes', 'income'),
        const SizedBox(width: 8),
        _filterChip('Dépenses', 'expense'),
      ],
    );
  }

  Widget _filterChip(String label, String value) {
    bool isSelected = _currentFilter == value;
    return InkWell(
      onTap: () => setState(() => _currentFilter = value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primaryColor : AppTheme.cardColor,
          borderRadius: BorderRadius.circular(30),
          border: Border.all(color: isSelected ? AppTheme.primaryColor : Colors.white10),
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

  Widget _buildBalanceCard(double balance) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppTheme.primaryColor, Colors.orange.shade800],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(30),
        boxShadow: [
          BoxShadow(color: AppTheme.primaryColor.withOpacity(0.3), blurRadius: 20, offset: const Offset(0, 10)),
        ],
      ),
      child: Column(
        children: [
          const Text('SOLDE ACTUEL', style: TextStyle(color: Colors.white70, fontWeight: FontWeight.bold, fontSize: 12, letterSpacing: 1)),
          const SizedBox(height: 12),
          Text(
            '${balance.toStringAsFixed(2)} \$',
            style: const TextStyle(color: Colors.white, fontSize: 40, fontWeight: FontWeight.w900),
          ),
        ],
      ),
    ).animate().scale();
  }

  Widget _buildMiniStat(String label, double value, Color color) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: AppTheme.textGrey, fontSize: 10, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          Text(
            '${value.toStringAsFixed(2)} \$',
            style: TextStyle(color: color, fontSize: 18, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildTransactionItem(Map<String, dynamic> t) {
    bool isIncome = t['type'] == 'income';
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white10),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: (isIncome ? Colors.green : Colors.red).withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(
              isIncome ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded,
              color: isIncome ? Colors.green : Colors.red,
              size: 20,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(t['category'] ?? 'Autre', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white)),
                Text(t['transaction_date'] ?? '', style: const TextStyle(color: AppTheme.textGrey, fontSize: 11)),
              ],
            ),
          ),
          Text(
            '${isIncome ? '+' : '-'} ${double.parse(t['amount'].toString()).toStringAsFixed(2)} \$',
            style: TextStyle(
              color: isIncome ? Colors.green : Colors.red,
              fontWeight: FontWeight.bold,
              fontSize: 14,
            ),
          ),
        ],
      ),
    ).animate().fadeIn().moveX(begin: 20, end: 0);
  }

  void _showAddTransactionDialog() {
    final amountController = TextEditingController();
    final descriptionController = TextEditingController();
    String selectedType = 'income';
    String selectedCategory = 'Distribution';
    final List<String> categories = ['Distribution', 'OneRpm', 'Marketing', 'Frais Fixes', 'Sponsoring', 'Autre'];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Container(
          padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom, left: 24, right: 24, top: 24),
          decoration: const BoxDecoration(
            color: Color(0xFF151515),
            borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(child: Container(width: 40, height: 4, decoration: const BoxDecoration(color: Colors.white10, borderRadius: BorderRadius.all(Radius.circular(2))))),
                const SizedBox(height: 24),
                const Text('NOUVELLE TRANSACTION', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 18)),
                const SizedBox(height: 32),
                
                // Type Switcher
                Row(
                  children: [
                    Expanded(child: _buildTypeOption(setModalState, 'RECETTE', 'income', selectedType == 'income', (val) => setModalState(() => selectedType = val))),
                    const SizedBox(width: 16),
                    Expanded(child: _buildTypeOption(setModalState, 'DÉPENSE', 'expense', selectedType == 'expense', (val) => setModalState(() => selectedType = val))),
                  ],
                ),
                const SizedBox(height: 24),
                
                _buildFieldLabel('MONTANT (\$)'),
                TextField(
                  controller: amountController,
                  keyboardType: TextInputType.number,
                  style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                  decoration: _inputDecoration('0.00'),
                ),
                const SizedBox(height: 20),
                
                _buildFieldLabel('CATÉGORIE'),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(color: AppTheme.cardColor, borderRadius: BorderRadius.circular(16)),
                  child: DropdownButton<String>(
                    value: selectedCategory,
                    dropdownColor: AppTheme.cardColor,
                    underline: Container(),
                    isExpanded: true,
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                    items: categories.map((c) => DropdownMenuItem(value: c, child: Text(c))).toList(),
                    onChanged: (v) => setModalState(() => selectedCategory = v!),
                  ),
                ),
                const SizedBox(height: 20),
                
                _buildFieldLabel('DESCRIPTION'),
                TextField(
                  controller: descriptionController,
                  style: const TextStyle(color: Colors.white),
                  decoration: _inputDecoration('Ex: Paiement royalties Mai'),
                ),
                const SizedBox(height: 40),
                
                ElevatedButton(
                  onPressed: () async {
                    if (amountController.text.isEmpty) return;
                    
                    final response = await http.post(
                      Uri.parse("${WordPressService.apiBaseUrl}/add_accounting_transaction.php"),
                      body: {
                        'amount': amountController.text,
                        'type': selectedType,
                        'category': selectedCategory,
                        'description': descriptionController.text,
                      },
                    );
                    if (json.decode(response.body)['success']) {
                      Navigator.pop(context);
                      _fetchData();
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Transaction enregistrée !'), backgroundColor: Colors.green));
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryColor,
                    minimumSize: const Size(double.infinity, 60),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                  ),
                  child: const Text('ENREGISTRER', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.5, color: Colors.white)),
                ),
                const SizedBox(height: 40),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildTypeOption(StateSetter setModalState, String label, String value, bool isSelected, Function(String) onSelect) {
    return InkWell(
      onTap: () => onSelect(value),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isSelected ? (value == 'income' ? Colors.green : Colors.red).withOpacity(0.1) : AppTheme.cardColor,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: isSelected ? (value == 'income' ? Colors.green : Colors.red) : Colors.white10),
        ),
        child: Center(
          child: Text(label, style: TextStyle(color: isSelected ? (value == 'income' ? Colors.green : Colors.red) : AppTheme.textGrey, fontWeight: FontWeight.bold, fontSize: 12)),
        ),
      ),
    );
  }

  Widget _buildFieldLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0, left: 4),
      child: Text(text, style: const TextStyle(color: AppTheme.textGrey, fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 1)),
    );
  }

  InputDecoration _inputDecoration(String hint) {
    return InputDecoration(
      hintText: hint,
      hintStyle: const TextStyle(color: Colors.white24),
      filled: true,
      fillColor: AppTheme.cardColor,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
    );
  }
}
