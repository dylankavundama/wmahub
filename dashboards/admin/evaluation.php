<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/performance_functions.php';

$db = getDBConnection();
// Lancer le traitement des awards si on est le 28
processMonthlyAwards();

$message = '';
$error = '';

// Traitement de la notation (Individuelle ou Bulk)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $admin_id = $_SESSION['user_id'];
    $employee_ids = isset($_POST['employee_ids']) ? $_POST['employee_ids'] : (isset($_POST['employee_id']) ? [$_POST['employee_id']] : []);
    $rating = (int)$_POST['rating'];
    $comment = $_POST['comment'] ?? '';

    $success_count = 0;
    $errors = [];

    foreach ($employee_ids as $employee_id) {
        $employee_id = (int)$employee_id;
        
        // Vérifier le délai de 48 heures pour chaque employé
        $stmt = $db->prepare("SELECT created_at FROM evaluations WHERE admin_id = ? AND employee_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$admin_id, $employee_id]);
        $last_eval = $stmt->fetch();

        $can_process = true;
        if ($last_eval) {
            $last_time = strtotime($last_eval['created_at']);
            $diff = time() - $last_time;
            if ($diff < (48 * 3600)) {
                $can_process = false;
                $errors[] = "Employé #$employee_id : trop tôt.";
            }
        }

        if ($can_process) {
            $stmt = $db->prepare("INSERT INTO evaluations (admin_id, employee_id, rating, comment) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$admin_id, $employee_id, $rating, $comment])) {
                $success_count++;
            }
        }
    }

    if ($success_count > 0) {
        $message = ($success_count > 1) ? "$success_count évaluations enregistrées !" : "Évaluation enregistrée !";
    }
    if (!empty($errors) && $success_count === 0) {
        $error = implode("<br>", $errors);
    }
}

// Récupérer les employés actifs
$employees = $db->query("SELECT id, name, email FROM users WHERE role = 'employe' AND is_active = 1")->fetchAll();

// Calculer les scores
$employee_stats = [];
foreach ($employees as $emp) {
    // Moyenne des tâches (Auto 3/5) - NON ARCHIVÉES
    $stmt = $db->prepare("SELECT AVG(rating), COUNT(*) FROM tasks WHERE user_id = ? AND status = 'termine' AND is_archived = 0");
    $stmt->execute([$emp['id']]);
    $task_res = $stmt->fetch(PDO::FETCH_NUM);
    $avg_tasks = $task_res[0] ?: 0;
    $count_auto = (int)$task_res[1];

    // Moyenne des évaluations admin - NON ARCHIVÉES
    $stmt = $db->prepare("SELECT AVG(rating), COUNT(*) FROM evaluations WHERE employee_id = ? AND is_archived = 0");
    $stmt->execute([$emp['id']]);
    $eval_res = $stmt->fetch(PDO::FETCH_NUM);
    $avg_evals = $eval_res[0] ?: 0;
    $count_admin = (int)$eval_res[1];

    // Score final
    if ($avg_tasks > 0 && $avg_evals > 0) {
        $final_score = ($avg_tasks + $avg_evals) / 2;
    } else {
        $final_score = max($avg_tasks, $avg_evals);
    }

    // Vérifier la disponibilité de la notation (48h)
    $stmt = $db->prepare("SELECT created_at FROM evaluations WHERE admin_id = ? AND employee_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $emp['id']]);
    $last_eval = $stmt->fetch();
    
    $can_rate = true;
    $wait_time = "";
    if ($last_eval) {
        $diff = time() - strtotime($last_eval['created_at']);
        if ($diff < (48 * 3600)) {
            $can_rate = false;
            $remaining = ceil(((48 * 3600) - $diff) / 3600);
            $wait_time = $remaining . "h";
        }
    }

    $employee_stats[$emp['id']] = [
        'info' => $emp,
        'score' => round($final_score, 1),
        'count_auto' => $count_auto,
        'count_admin' => $count_admin,
        'total_ratings' => $count_auto + $count_admin,
        'can_rate' => $can_rate,
        'wait_time' => $wait_time
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluation Staff - WMA HUB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2rem; padding: 2rem; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 3rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; }
        .rating-star { cursor: pointer; font-size: 1.5rem; transition: color 0.2s; }
        .rating-star.active { color: #ff6600; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <h1 class="text-xl font-black text-orange-500 tracking-tighter">WMA HUB</h1>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-layer-group"></i> Projets</a>
            <a href="employees.php" class="nav-link"><i class="fas fa-users-cog"></i> Staff</a>
            <a href="evaluation.php" class="nav-link active"><i class="fas fa-star"></i> Évaluations</a>
        </nav>
        <a href="employees.php" class="nav-link !text-gray-400 mt-auto"><i class="fas fa-arrow-left"></i> Retour</a>
    </aside>

    <main class="main-content">
        <header class="mb-12">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h2 class="text-4xl font-black text-white">Évaluation du <span class="text-orange-500">Staff</span></h2>
                    <p class="text-gray-400 mt-2">Notez votre équipe toutes les 48 heures pour maintenir l'excellence.</p>
                </div>
                <div class="flex items-center gap-4">
                    <button id="multiEvalBtn" class="hidden px-6 py-4 bg-orange-500 text-white rounded-2xl text-sm font-bold shadow-lg shadow-orange-500/20 items-center gap-2 animate-bounce">
                        <i class="fas fa-users"></i> Noter la sélection (<span id="selectedCount">0</span>)
                    </button>
                    <a href="evaluation_history.php" class="px-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-sm font-bold hover:bg-white/10 transition-all flex items-center gap-2">
                        <i class="fas fa-history text-orange-500"></i> Historique
                    </a>
                    <div class="relative group">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 group-focus-within:text-orange-500 transition-colors"></i>
                        <input type="text" id="employeeSearch" placeholder="Rechercher un membre..." 
                            class="bg-white/5 border border-white/10 rounded-2xl py-4 pl-12 pr-6 text-sm focus:outline-none focus:border-orange-500/50 w-full md:w-80 transition-all">
                    </div>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="mb-8 p-4 bg-green-500/10 border border-green-500/20 text-green-500 rounded-2xl flex items-center gap-3">
                <i class="fas fa-check-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-8 p-4 bg-red-500/10 border border-red-500/20 text-red-500 rounded-2xl flex items-center gap-3">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-2 space-y-6" id="employeeGrid">
                <?php foreach ($employee_stats as $id => $data): ?>
                    <div class="glass-card flex flex-col md:flex-row items-center justify-between group gap-6 employee-row" data-name="<?= strtolower(htmlspecialchars($data['info']['name'])) ?>">
                        <div class="flex items-center gap-6 w-full md:w-auto">
                            <?php if($data['can_rate']): ?>
                                <input type="checkbox" class="employee-checkbox w-5 h-5 rounded border-white/10 bg-white/5 text-orange-500 focus:ring-orange-500" value="<?= $id ?>" data-name="<?= htmlspecialchars($data['info']['name']) ?>">
                            <?php else: ?>
                                <div class="w-5"></div>
                            <?php endif; ?>
                            
                            <div class="w-16 h-16 rounded-2xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500 text-2xl font-black">
                                <?= strtoupper(substr($data['info']['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold"><?= htmlspecialchars($data['info']['name']) ?></h4>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1">
                                    <span class="text-xs text-gray-500"><i class="fas fa-check-circle mr-1 text-green-500/50"></i> <?= $data['count_auto'] ?> auto</span>
                                    <span class="text-xs text-gray-500"><i class="fas fa-user-shield mr-1 text-blue-500/50"></i> <?= $data['count_admin'] ?> admin</span>
                                    <div class="flex items-center gap-1 text-orange-500 text-xs font-bold bg-orange-500/10 px-2 py-0.5 rounded-lg">
                                        <i class="fas fa-star"></i> <?= $data['score'] ?>/5
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <div class="text-right hidden md:block">
                                <p class="text-[10px] text-gray-500 uppercase font-bold tracking-tighter">Total évaluations</p>
                                <p class="text-xl font-black text-white leading-none"><?= $data['total_ratings'] ?></p>
                            </div>
                            
                            <?php if ($data['can_rate']): ?>
                                <button onclick="openModal(<?= $id ?>, '<?= htmlspecialchars($data['info']['name']) ?>')" class="flex-1 md:flex-none px-6 py-3 bg-white/5 hover:bg-orange-500 hover:text-white rounded-xl font-bold transition-all border border-white/5">
                                    Noter
                                </button>
                            <?php else: ?>
                                <div class="px-6 py-3 bg-white/5 text-gray-600 rounded-xl font-bold border border-white/5 cursor-not-allowed flex items-center gap-2">
                                    <i class="fas fa-clock text-[10px]"></i> <?= $data['wait_time'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="glass-card h-fit">
                <h3 class="text-xl font-bold mb-4">Critères de Notation</h3>
                <ul class="space-y-4 text-sm text-gray-400">
                    <li class="flex gap-3"><i class="fas fa-circle text-[8px] mt-1.5 text-orange-500"></i> Qualité des fichiers rendus</li>
                    <li class="flex gap-3"><i class="fas fa-circle text-[8px] mt-1.5 text-orange-500"></i> Communication dans le chat</li>
                    <li class="flex gap-3"><i class="fas fa-circle text-[8px] mt-1.5 text-orange-500"></i> Respect des délais</li>
                </ul>
            </div>
        </div>
    </main>

    <!-- Modal Notation -->
    <div id="ratingModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
        <div class="glass-card w-full max-w-md">
            <h3 id="modalTitle" class="text-2xl font-black mb-1">Noter l'employé</h3>
            <p id="modalSubtitle" class="text-gray-500 text-xs mb-6 truncate"></p>
            <form method="POST">
                <div id="modalEmpIdContainer"></div>
                <div class="mb-6">
                    <label class="block text-gray-500 text-xs font-black uppercase tracking-widest mb-4 text-center">Attribuer une note</label>
                    <div class="flex justify-center gap-4">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" class="hidden peer" required>
                            <label for="star<?= $i ?>" class="rating-star text-gray-600 peer-checked:text-orange-500 hover:text-orange-400">
                                <i class="fas fa-star"></i>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-500 text-xs font-black uppercase tracking-widest mb-2">Commentaire (Facultatif)</label>
                    <textarea name="comment" class="w-full bg-white/5 border border-white/10 rounded-xl p-4 text-sm focus:outline-none focus:border-orange-500" rows="3"></textarea>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="closeModal()" class="flex-1 py-4 text-gray-500 font-bold hover:text-white transition-colors">Annuler</button>
                    <button type="submit" name="submit_rating" class="flex-1 py-4 bg-orange-500 text-white font-bold rounded-2xl shadow-lg shadow-orange-500/20">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Recherche en temps réel
        const searchInput = document.getElementById('employeeSearch');
        const employeeRows = document.querySelectorAll('.employee-row');
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        const multiEvalBtn = document.getElementById('multiEvalBtn');
        const selectedCountSpan = document.getElementById('selectedCount');

        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            employeeRows.forEach(row => {
                const name = row.getAttribute('data-name');
                if (name.includes(term)) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Gestion de la sélection multiple
        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateMultiSelection);
        });

        function updateMultiSelection() {
            const selected = document.querySelectorAll('.employee-checkbox:checked');
            const count = selected.length;
            selectedCountSpan.innerText = count;
            
            if (count > 0) {
                multiEvalBtn.classList.remove('hidden');
                multiEvalBtn.classList.add('flex');
            } else {
                multiEvalBtn.classList.remove('flex');
                multiEvalBtn.classList.add('hidden');
            }
        }

        multiEvalBtn.addEventListener('click', () => {
            const selected = document.querySelectorAll('.employee-checkbox:checked');
            const ids = Array.from(selected).map(cb => cb.value);
            const names = Array.from(selected).map(cb => cb.getAttribute('data-name')).join(', ');
            
            openModal(ids, names);
        });

        function openModal(id, name) {
            const container = document.getElementById('modalEmpIdContainer');
            container.innerHTML = '';
            
            if (Array.isArray(id)) {
                id.forEach(val => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'employee_ids[]';
                    input.value = val;
                    container.appendChild(input);
                });
                document.getElementById('modalTitle').innerText = "Noter la sélection";
                document.getElementById('modalSubtitle').innerText = name;
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'employee_id';
                input.value = id;
                container.appendChild(input);
                document.getElementById('modalTitle').innerText = "Noter " + name;
                document.getElementById('modalSubtitle').innerText = "";
            }
            
            document.getElementById('ratingModal').classList.remove('hidden');
            document.getElementById('ratingModal').classList.add('flex');
        }
        function closeModal() {
            document.getElementById('ratingModal').classList.add('hidden');
            document.getElementById('ratingModal').classList.remove('flex');
        }
    </script>
</body>
</html>
