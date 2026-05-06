<?php
/**
 * API Assistant d'écriture IA — Version Mobile
 * Authentification par user_id (pas de session PHP)
 * Actions: generate_lyrics | correct_text | generate_chorus | get_notes | save_note | create_note | delete_note
 */
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/config.php';

if (ob_get_level() > 0) ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// --- Auth par user_id dans le body ---
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?? [];
$postData  = array_merge($_POST, $jsonInput);

$userId = intval($postData['user_id'] ?? 0);
$action = $postData['action'] ?? ($_GET['action'] ?? '');

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'user_id requis']);
    exit;
}

// Vérifier que l'utilisateur existe
$db = getDBConnection();
$stmt = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
    exit;
}

// Helper cURL Gemini
function callGemini(string $prompt): string {
    $apiKey = GEMINI_API_KEY;
    $url    = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=$apiKey";
    $body   = json_encode(["contents" => [["parts" => [["text" => $prompt]]]]]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
}

// ========================
// ACTION: Lister les notes
// ========================
if ($action === 'get_notes') {
    $stmt = $db->prepare("SELECT id, title, content, updated_at FROM artist_notes WHERE user_id = ? ORDER BY updated_at DESC");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'notes' => $stmt->fetchAll()]);
    exit;
}

// ========================
// ACTION: Créer une note
// ========================
if ($action === 'create_note') {
    $stmt = $db->prepare("INSERT INTO artist_notes (user_id, title, content) VALUES (?, 'Nouvelle Note', '')");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}

// ========================
// ACTION: Sauvegarder note
// ========================
if ($action === 'save_note') {
    $id      = intval($postData['id'] ?? 0);
    $title   = trim($postData['title']   ?? '');
    $content = trim($postData['content'] ?? '');

    if (!$id) { echo json_encode(['success' => false, 'message' => 'ID manquant']); exit; }

    $stmt = $db->prepare("UPDATE artist_notes SET title = ?, content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    echo json_encode(['success' => $stmt->execute([$title, $content, $id, $userId])]);
    exit;
}

// ========================
// ACTION: Supprimer note
// ========================
if ($action === 'delete_note') {
    $id = intval($postData['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM artist_notes WHERE id = ? AND user_id = ?");
    echo json_encode(['success' => $stmt->execute([$id, $userId])]);
    exit;
}

// ========================
// ACTION: Corriger le texte
// ========================
if ($action === 'correct_text') {
    $text = trim($postData['text'] ?? '');
    if (empty($text)) { echo json_encode(['success' => false, 'message' => 'Texte vide']); exit; }

    $prompt = "Agis comme un correcteur professionnel de paroles de chansons.
Corrige les fautes d'orthographe, de grammaire et améliore le style tout en restant fidèle à l'intention de l'artiste.
Texte à corriger:
\"$text\"

Réponds UNIQUEMENT avec le texte corrigé, sans commentaires ni explications.";

    $corrected = callGemini($prompt);
    echo json_encode(['success' => true, 'corrected' => trim($corrected)]);
    exit;
}

// ========================
// ACTION: Générer un refrain
// ========================
if ($action === 'generate_chorus') {
    $title = trim($postData['title'] ?? '');
    $theme = trim($postData['theme'] ?? '');
    $style = trim($postData['style'] ?? '');
    $verse = trim($postData['verse'] ?? '');

    $prompt = "Agis comme un auteur-compositeur professionnel.
Titre: $title | Thème: $theme | Style: $style
Couplet existant:
$verse

Génère un refrain accrocheur qui complète parfaitement ce couplet.
Retourne UNIQUEMENT le refrain, sans introduction ni explication.";

    $chorus = callGemini($prompt);
    echo json_encode(['success' => true, 'chorus' => trim($chorus)]);
    exit;
}

// ========================
// ACTION: Générer des paroles
// ========================
if ($action === 'generate_lyrics') {
    $title    = trim($postData['title']    ?? '');
    $theme    = trim($postData['theme']    ?? '');
    $lang     = trim($postData['language'] ?? 'Français');
    $genre    = trim($postData['genre']    ?? '');
    $audience = trim($postData['audience'] ?? 'Grand public');
    $duration = trim($postData['duration'] ?? '3 minutes');

    if (empty($title) || empty($theme)) {
        echo json_encode(['success' => false, 'message' => 'Titre et thème requis']);
        exit;
    }

    $prompt = "Agis comme un auteur-compositeur professionnel. Écris des paroles de chanson complètes.
Titre: $title | Thème: $theme | Langue: $lang | Genre: $genre | Public: $audience | Durée: $duration

Réponds EXCLUSIVEMENT au format JSON (sans backticks):
{\"lyrics_1\": \"...\", \"lyrics_2\": \"...\"}";

    $raw = callGemini($prompt);
    $raw = preg_replace('/```json\s*|\s*```/', '', $raw);
    $lyricsData = json_decode(trim($raw), true);

    if (!$lyricsData || !isset($lyricsData['lyrics_1'])) {
        echo json_encode(['success' => false, 'message' => 'Erreur de génération, réessayez.']);
        exit;
    }

    // Sauvegarder en base
    try {
        $stmt = $db->prepare("INSERT INTO generated_lyrics (user_id, title, theme, lyrics_1, lyrics_2) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $theme, $lyricsData['lyrics_1'], $lyricsData['lyrics_2'] ?? '']);
    } catch (Exception $e) {}

    echo json_encode([
        'success'  => true,
        'lyrics_1' => $lyricsData['lyrics_1'],
        'lyrics_2' => $lyricsData['lyrics_2'] ?? '',
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action inconnue: ' . htmlspecialchars($action)]);
?>
