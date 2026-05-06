import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/app_theme.dart';
import '../services/wordpress_service.dart';

class WritingAssistantScreen extends StatefulWidget {
  const WritingAssistantScreen({super.key});

  @override
  State<WritingAssistantScreen> createState() => _WritingAssistantScreenState();
}

class _WritingAssistantScreenState extends State<WritingAssistantScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  int _userId = 0;

  // --- Tab 1: Génération de paroles ---
  final _titleCtrl   = TextEditingController();
  final _themeCtrl   = TextEditingController();
  String _language   = 'Français';
  String _genre      = 'Afrobeat';
  String _lyrics1    = '';
  String _lyrics2    = '';
  bool   _generating = false;
  int    _activeVersion = 1;

  // --- Tab 2: Correction / Refrain ---
  final _correctCtrl = TextEditingController();
  final _chorusTitleCtrl = TextEditingController();
  final _chorusThemeCtrl = TextEditingController();
  final _chorusVerseCtrl = TextEditingController();
  String _chorusStyle  = 'Pop';
  String _correctedText = '';
  String _generatedChorus = '';
  bool   _correcting  = false;
  bool   _generatingChorus = false;

  // --- Tab 3: Notes ---
  List<Map<String, dynamic>> _notes = [];
  bool _loadingNotes = false;
  Map<String, dynamic>? _activeNote;
  final _noteTitleCtrl   = TextEditingController();
  final _noteContentCtrl = TextEditingController();
  bool _savingNote = false;

  static const _languages = ['Français','Lingala','Swahili','Anglais','Espagnol','Portugais','Arabe'];
  static const _genres    = ['Afrobeat','RnB','Zouk','Gospel','Rap','Pop','Reggae','Soul','Jazz'];
  static const _styles    = ['Pop','RnB','Gospel','Afrobeat','Rap','Zouk','Rock','Jazz'];

  String get _apiBase => WordPressService.apiBaseUrl;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadUser();
  }

  Future<void> _loadUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userJson = prefs.getString('auth_user');
    if (userJson != null) {
      final user = json.decode(userJson);
      setState(() => _userId = int.tryParse(user['id'].toString()) ?? 0);
      _fetchNotes();
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
    _titleCtrl.dispose(); _themeCtrl.dispose();
    _correctCtrl.dispose();
    _chorusTitleCtrl.dispose(); _chorusThemeCtrl.dispose(); _chorusVerseCtrl.dispose();
    _noteTitleCtrl.dispose(); _noteContentCtrl.dispose();
    super.dispose();
  }

  // ── API calls ──────────────────────────────────────────────
  Future<Map<String, dynamic>> _post(String action, Map<String, String> body) async {
    try {
      final res = await http.post(
        Uri.parse('$_apiBase/ai_writing_assistant.php'),
        body: {'user_id': _userId.toString(), 'action': action, ...body},
      ).timeout(const Duration(seconds: 40));
      return json.decode(res.body) as Map<String, dynamic>;
    } catch (e) {
      return {'success': false, 'message': 'Erreur réseau : $e'};
    }
  }

  Future<void> _generateLyrics() async {
    if (_titleCtrl.text.isEmpty || _themeCtrl.text.isEmpty) {
      _snack('Remplis le titre et le thème', isError: true); return;
    }
    setState(() { _generating = true; _lyrics1 = ''; _lyrics2 = ''; });
    final res = await _post('generate_lyrics', {
      'title': _titleCtrl.text, 'theme': _themeCtrl.text,
      'language': _language, 'genre': _genre,
      'audience': 'Grand public', 'duration': '3 minutes',
    });
    setState(() {
      _generating = false;
      if (res['success'] == true) {
        _lyrics1 = res['lyrics_1'] ?? ''; _lyrics2 = res['lyrics_2'] ?? '';
      } else { _snack(res['message'] ?? 'Erreur', isError: true); }
    });
  }

  Future<void> _correctText() async {
    if (_correctCtrl.text.isEmpty) { _snack('Écris du texte à corriger', isError: true); return; }
    setState(() { _correcting = true; _correctedText = ''; });
    final res = await _post('correct_text', {'text': _correctCtrl.text});
    setState(() {
      _correcting = false;
      if (res['success'] == true) { _correctedText = res['corrected'] ?? ''; }
      else { _snack(res['message'] ?? 'Erreur', isError: true); }
    });
  }

  Future<void> _generateChorus() async {
    if (_chorusTitleCtrl.text.isEmpty) { _snack('Remplis le titre', isError: true); return; }
    setState(() { _generatingChorus = true; _generatedChorus = ''; });
    final res = await _post('generate_chorus', {
      'title': _chorusTitleCtrl.text, 'theme': _chorusThemeCtrl.text,
      'style': _chorusStyle, 'verse': _chorusVerseCtrl.text,
    });
    setState(() {
      _generatingChorus = false;
      if (res['success'] == true) { _generatedChorus = res['chorus'] ?? ''; }
      else { _snack(res['message'] ?? 'Erreur', isError: true); }
    });
  }

  Future<void> _fetchNotes() async {
    setState(() => _loadingNotes = true);
    final res = await _post('get_notes', {});
    setState(() {
      _loadingNotes = false;
      if (res['success'] == true) {
        _notes = List<Map<String, dynamic>>.from(res['notes'] ?? []);
      }
    });
  }

  Future<void> _createNote() async {
    final res = await _post('create_note', {});
    if (res['success'] == true) {
      await _fetchNotes();
      final newNote = _notes.firstWhere(
        (n) => n['id'].toString() == res['id'].toString(),
        orElse: () => {'id': res['id'], 'title': 'Nouvelle Note', 'content': ''},
      );
      _openNote(newNote);
    }
  }

  Future<void> _saveNote() async {
    if (_activeNote == null) return;
    setState(() => _savingNote = true);
    await _post('save_note', {
      'id': _activeNote!['id'].toString(),
      'title': _noteTitleCtrl.text, 'content': _noteContentCtrl.text,
    });
    setState(() => _savingNote = false);
    _snack('Note sauvegardée ✓');
    _fetchNotes();
  }

  Future<void> _deleteNote(Map<String, dynamic> note) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: AppTheme.cardColor,
        title: const Text('Supprimer cette note ?', style: TextStyle(color: Colors.white)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Supprimer', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirm == true) {
      await _post('delete_note', {'id': note['id'].toString()});
      if (_activeNote?['id'] == note['id']) setState(() => _activeNote = null);
      _fetchNotes();
    }
  }

  void _openNote(Map<String, dynamic> note) {
    setState(() {
      _activeNote = note;
      _noteTitleCtrl.text   = note['title']   ?? '';
      _noteContentCtrl.text = note['content'] ?? '';
    });
  }

  void _snack(String msg, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: isError ? Colors.red.shade700 : AppTheme.primaryColor,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    ));
  }

  void _copyToClipboard(String text) {
    Clipboard.setData(ClipboardData(text: text));
    _snack('Copié dans le presse-papiers ✓');
  }

  // ── UI ────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      appBar: AppBar(
        backgroundColor: AppTheme.backgroundColor,
        elevation: 0,
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: AppTheme.primaryColor.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.auto_awesome, color: AppTheme.primaryColor, size: 20),
            ),
            const SizedBox(width: 12),
            const Text(
              'Assistant IA',
              style: TextStyle(
                color: Colors.white, fontWeight: FontWeight.w900,
                fontSize: 20, letterSpacing: -0.5,
              ),
            ),
          ],
        ),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: AppTheme.primaryColor,
          indicatorWeight: 3,
          labelColor: AppTheme.primaryColor,
          unselectedLabelColor: Colors.white38,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
          tabs: const [
            Tab(icon: Icon(Icons.music_note, size: 18), text: 'Paroles'),
            Tab(icon: Icon(Icons.auto_fix_high, size: 18), text: 'Outils'),
            Tab(icon: Icon(Icons.note_alt_outlined, size: 18), text: 'Notes'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [_buildLyricsTab(), _buildToolsTab(), _buildNotesTab()],
      ),
    );
  }

  // ── TAB 1: Génération de paroles ──────────────────────────
  Widget _buildLyricsTab() {
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        // Header
        _sectionHeader('GÉNÉRER DES PAROLES', Icons.auto_awesome),
        const SizedBox(height: 16),

        _field('Titre de la chanson', _titleCtrl, Icons.title),
        const SizedBox(height: 12),
        _field('Thème / Histoire', _themeCtrl, Icons.lightbulb_outline, maxLines: 3),
        const SizedBox(height: 12),

        Row(children: [
          Expanded(child: _dropdown('Langue', _language, _languages, (v) => setState(() => _language = v!))),
          const SizedBox(width: 12),
          Expanded(child: _dropdown('Genre', _genre, _genres, (v) => setState(() => _genre = v!))),
        ]),
        const SizedBox(height: 24),

        // Bouton générer
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: _generating ? null : _generateLyrics,
            icon: _generating
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : const Icon(Icons.auto_awesome),
            label: Text(_generating ? 'Génération en cours...' : 'Générer les paroles'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryColor,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            ),
          ),
        ).animate().fadeIn(),

        // Résultats
        if (_lyrics1.isNotEmpty) ...[
          const SizedBox(height: 28),
          _sectionHeader('RÉSULTATS', Icons.lyrics_outlined),
          const SizedBox(height: 12),
          Row(children: [
            _versionChip('Version 1', 1),
            const SizedBox(width: 10),
            _versionChip('Version 2', 2),
          ]),
          const SizedBox(height: 16),
          _lyricsCard(_activeVersion == 1 ? _lyrics1 : _lyrics2),
        ],
      ],
    );
  }

  Widget _versionChip(String label, int version) {
    final active = _activeVersion == version;
    return GestureDetector(
      onTap: () => setState(() => _activeVersion = version),
      child: AnimatedContainer(
        duration: 200.ms,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
        decoration: BoxDecoration(
          color: active ? AppTheme.primaryColor : Colors.white.withValues(alpha: 0.05),
          borderRadius: BorderRadius.circular(30),
          border: Border.all(color: active ? AppTheme.primaryColor : Colors.white12),
        ),
        child: Text(label, style: TextStyle(
          color: active ? Colors.white : Colors.white54,
          fontWeight: FontWeight.bold, fontSize: 13,
        )),
      ),
    );
  }

  Widget _lyricsCard(String lyrics) {
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 16, 8, 0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                IconButton(
                  onPressed: () => _copyToClipboard(lyrics),
                  icon: const Icon(Icons.copy_outlined, color: AppTheme.textGrey, size: 20),
                  tooltip: 'Copier',
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: SelectableText(
              lyrics,
              style: const TextStyle(color: Colors.white, height: 1.8, fontSize: 14),
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.1);
  }

  // ── TAB 2: Outils (Correction + Refrain) ─────────────────
  Widget _buildToolsTab() {
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        // Correction
        _sectionHeader('CORRIGER UN TEXTE', Icons.spellcheck),
        const SizedBox(height: 12),
        _field('Colle tes paroles ici...', _correctCtrl, Icons.edit_note, maxLines: 6),
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: _correcting ? null : _correctText,
            icon: _correcting
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : const Icon(Icons.auto_fix_high),
            label: Text(_correcting ? 'Correction en cours...' : 'Corriger le texte'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF6C63FF),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            ),
          ),
        ),
        if (_correctedText.isNotEmpty) ...[
          const SizedBox(height: 16),
          _resultCard(_correctedText, color: const Color(0xFF6C63FF)),
        ],

        const SizedBox(height: 32),
        const Divider(color: Colors.white10),
        const SizedBox(height: 24),

        // Refrain
        _sectionHeader('GÉNÉRER UN REFRAIN', Icons.queue_music),
        const SizedBox(height: 12),
        _field('Titre', _chorusTitleCtrl, Icons.title),
        const SizedBox(height: 10),
        _field('Thème', _chorusThemeCtrl, Icons.lightbulb_outline),
        const SizedBox(height: 10),
        _dropdown('Style', _chorusStyle, _styles, (v) => setState(() => _chorusStyle = v!)),
        const SizedBox(height: 10),
        _field('Couplet existant (optionnel)', _chorusVerseCtrl, Icons.lyrics_outlined, maxLines: 4),
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: _generatingChorus ? null : _generateChorus,
            icon: _generatingChorus
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : const Icon(Icons.queue_music),
            label: Text(_generatingChorus ? 'Génération...' : 'Générer le refrain'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF00B09B),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            ),
          ),
        ),
        if (_generatedChorus.isNotEmpty) ...[
          const SizedBox(height: 16),
          _resultCard(_generatedChorus, color: const Color(0xFF00B09B)),
        ],
        const SizedBox(height: 40),
      ],
    );
  }

  Widget _resultCard(String text, {Color color = AppTheme.primaryColor}) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.07),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              GestureDetector(
                onTap: () => _copyToClipboard(text),
                child: Icon(Icons.copy_outlined, color: color, size: 18),
              ),
            ],
          ),
          const SizedBox(height: 8),
          SelectableText(text, style: const TextStyle(color: Colors.white, height: 1.8, fontSize: 14)),
        ],
      ),
    ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.1);
  }

  // ── TAB 3: Notes ─────────────────────────────────────────
  Widget _buildNotesTab() {
    if (_activeNote != null) return _buildNoteEditor();

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(20),
          child: Row(
            children: [
              Expanded(child: _sectionHeader('MES NOTES', Icons.note_alt_outlined)),
              ElevatedButton.icon(
                onPressed: _createNote,
                icon: const Icon(Icons.add, size: 18),
                label: const Text('Nouvelle'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.primaryColor,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  textStyle: const TextStyle(fontSize: 13),
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: _loadingNotes
              ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryColor))
              : _notes.isEmpty
                  ? _emptyNotes()
                  : RefreshIndicator(
                      color: AppTheme.primaryColor,
                      onRefresh: _fetchNotes,
                      child: ListView.builder(
                        padding: const EdgeInsets.symmetric(horizontal: 20),
                        itemCount: _notes.length,
                        itemBuilder: (ctx, i) => _noteCard(_notes[i]),
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _noteCard(Map<String, dynamic> note) {
    final content = note['content'] ?? '';
    final preview = content.length > 80 ? '${content.substring(0, 80)}...' : content;
    return GestureDetector(
      onTap: () => _openNote(note),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppTheme.cardColor,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white10),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    note['title'] ?? 'Sans titre',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                  ),
                  if (preview.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(preview, style: const TextStyle(color: Colors.white38, fontSize: 12)),
                  ],
                ],
              ),
            ),
            IconButton(
              onPressed: () => _deleteNote(note),
              icon: const Icon(Icons.delete_outline, color: Colors.red, size: 20),
            ),
          ],
        ),
      ).animate().fadeIn(delay: Duration(milliseconds: 50 * (_notes.indexOf(note)))),
    );
  }

  Widget _buildNoteEditor() {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(8, 8, 16, 0),
          child: Row(
            children: [
              IconButton(
                onPressed: () => setState(() => _activeNote = null),
                icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
              ),
              Expanded(
                child: TextField(
                  controller: _noteTitleCtrl,
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18),
                  decoration: const InputDecoration(border: InputBorder.none, hintText: 'Titre...', hintStyle: TextStyle(color: Colors.white24)),
                ),
              ),
              if (_savingNote)
                const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryColor))
              else
                IconButton(
                  onPressed: _saveNote,
                  icon: const Icon(Icons.save_outlined, color: AppTheme.primaryColor),
                ),
            ],
          ),
        ),
        const Divider(color: Colors.white10),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _noteContentCtrl,
              maxLines: null,
              expands: true,
              textAlignVertical: TextAlignVertical.top,
              style: const TextStyle(color: Colors.white, height: 1.8, fontSize: 15),
              decoration: const InputDecoration(
                border: InputBorder.none,
                hintText: 'Écris tes paroles, idées, accords...',
                hintStyle: TextStyle(color: Colors.white24),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _emptyNotes() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.note_add_outlined, size: 60, color: Colors.white12),
          const SizedBox(height: 16),
          const Text('Aucune note', style: TextStyle(color: Colors.white38, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          const Text('Crée ta première note pour garder\ntes idées et paroles', textAlign: TextAlign.center, style: TextStyle(color: Colors.white24, fontSize: 13)),
        ],
      ),
    );
  }

  // ── Helpers ───────────────────────────────────────────────
  Widget _sectionHeader(String title, IconData icon) {
    return Row(
      children: [
        Icon(icon, color: AppTheme.primaryColor, size: 16),
        const SizedBox(width: 8),
        Text(title, style: const TextStyle(
          color: AppTheme.primaryColor, fontWeight: FontWeight.w900,
          fontSize: 12, letterSpacing: 1.5,
        )),
      ],
    );
  }

  Widget _field(String hint, TextEditingController ctrl, IconData icon, {int maxLines = 1}) {
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white10),
      ),
      child: TextField(
        controller: ctrl,
        maxLines: maxLines,
        style: const TextStyle(color: Colors.white),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: const TextStyle(color: Colors.white24),
          prefixIcon: Icon(icon, color: Colors.white24, size: 20),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        ),
      ),
    );
  }

  Widget _dropdown(String label, String value, List<String> items, ValueChanged<String?> onChanged) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white10),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          isExpanded: true,
          dropdownColor: AppTheme.cardColor,
          style: const TextStyle(color: Colors.white, fontSize: 14),
          icon: const Icon(Icons.keyboard_arrow_down, color: Colors.white38),
          items: items.map((e) => DropdownMenuItem(value: e, child: Text(e))).toList(),
          onChanged: onChanged,
        ),
      ),
    );
  }
}
