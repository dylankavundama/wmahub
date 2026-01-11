<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$db = getDBConnection();

// Action: Récupérer une création spécifique
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM generated_lyrics WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $item = $stmt->fetch();
    echo json_encode(['success' => !!$item] + ($item ?: []));
    exit;
}

// Action: Créer une nouvelle note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create_note') {
    $stmt = $db->prepare("INSERT INTO artist_notes (user_id, title, content) VALUES (?, 'Nouvelle Note', '')");
    $stmt->execute([$_SESSION['user_id']]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}

// Action: Sauvegarder une note spécifique
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_note') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
        exit;
    }
    $stmt = $db->prepare("UPDATE artist_notes SET title = ?, content = ? WHERE id = ? AND user_id = ?");
    $success = $stmt->execute([$data['title'], $data['content'], $data['id'], $_SESSION['user_id']]);
    echo json_encode(['success' => $success]);
    exit;
}

// Action: Corriger le texte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'correct_text') {
    $data = json_decode(file_get_contents('php://input'), true);
    $text = $data['text'];

    $apiKey = 'AIzaSyA_KgjNanXy09Hh2GMI-3pust2XjUqLgEA';
    $prompt = "Agis comme un correcteur professionnel de textes et de paroles de chansons. 
    Corrige les fautes d'orthographe, de grammaire et améliore le style tout en restant fidèle à l'intention de l'artiste.
    Texte à corriger:
    \"$text\"
    
    Réonds UNIQUEMENT avec le texte corrigé, sans commentaires.";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
    $postData = ["contents" => [["parts" => [["text" => $prompt]]]]];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    $corrected = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Erreur lors de la correction';
    echo json_encode(['success' => true, 'corrected' => trim($corrected)]);
    exit;
}

// Action: Générer un refrain
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'generate_chorus') {
    $title = $_POST['title'];
    $theme = $_POST['theme'];
    $style = $_POST['style'];
    $verse = $_POST['verse'];

    $apiKey = 'AIzaSyA_KgjNanXy09Hh2GMI-3pust2XjUqLgEA';
    $prompt = "Agis comme un auteur-compositeur. Écris un refrain accrocheur pour une chanson.
    Titre: $title
    Thème: $theme
    Style: $style
    Couplet existant: $verse
    
    Intègre le refrain DIRECTEMENT après ou au milieu du couplet fourni si nécessaire. 
    ENTOURE LE REFRAIN GÉNÉRÉ AVEC DES BALISES <span class=\"text-orange-500 font-bold underline\"> ... </span> pour qu'il soit bien visible.
    Rends le tout harmonieux.
    Relourne UNIQUEMENT le texte final avec le refrain coloré.";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
    $postData = ["contents" => [["parts" => [["text" => $prompt]]]]];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    $final_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Erreur lors de la génération';
    echo json_encode(['success' => true, 'result' => trim($final_text)]);
    exit;
}

// Action: Supprimer une note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete_note') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare("DELETE FROM artist_notes WHERE id = ? AND user_id = ?");
    $success = $stmt->execute([$data['id'], $_SESSION['user_id']]);
    echo json_encode(['success' => $success]);
    exit;
}

// Action: Générer des paroles (Gemini)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Configuration de la clé API Gemini
    $apiKey = 'AIzaSyA_KgjNanXy09Hh2GMI-3pust2XjUqLgEA';

    if ($apiKey === 'YOUR_API_KEY_HERE') {
        echo json_encode(['success' => false, 'message' => 'Clé API Gemini non configurée. Veuillez contacter l\'administrateur.']);
        exit;
    }

    $title = $_POST['title'];
    $theme = $_POST['theme'];
    $lang = $_POST['language'];
    $genre = $_POST['genre'];
    $audience = $_POST['audience'];
    $duration = $_POST['duration'];

    $prompt = "Agis comme un auteur-compositeur professionnel. Écris deux versions de paroles de chanson différentes. 
    Titre: $title
    Thème: $theme
    Langue: $lang
    Genre: $genre
    Public: $audience
    Durée estimée: $duration
    
    Réponds EXCLUSIVEMENT au format JSON comme suit:
    {
        \"lyrics_1\": \"...\",
        \"lyrics_2\": \"...\"
    }";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode === 200 && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $textResponse = $result['candidates'][0]['content']['parts'][0]['text'];
        
        // Nettoyage si Gemini met des backticks ```json
        $textResponse = preg_replace('/```json\s*|\s*```/', '', $textResponse);
        $lyricsData = json_decode($textResponse, true);

        if ($lyricsData) {
            // Sauvegarder en base
            $stmt = $db->prepare("INSERT INTO generated_lyrics (user_id, title, theme, lyrics_1, lyrics_2) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $theme, $lyricsData['lyrics_1'], $lyricsData['lyrics_2']]);

            echo json_encode([
                'success' => true,
                'lyrics_1' => $lyricsData['lyrics_1'],
                'lyrics_2' => $lyricsData['lyrics_2']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur de formatage des paroles.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur API Gemini: ' . ($result['error']['message'] ?? 'Réponse invalide')]);
    }
    exit;
}
