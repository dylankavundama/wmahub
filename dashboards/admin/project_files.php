<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA ADMIN - Fichier Projet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; display: flex; flex-direction: column; padding: 2rem 1.5rem; position: fixed; left: 0; top: 0; z-index: 100; transition: transform 0.3s ease; }
        .main-content { margin-left: 280px; min-height: 100vh; transition: margin-left 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .file-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 1.5rem; transition: all 0.3s ease; }
        .file-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 102, 0, 0.3); }
        .glass-panel { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2rem; }
        .custom-input { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; padding: 0.8rem 1.2rem; outline: none; width: 100%; transition: all 0.3s; }
        .custom-input:focus { border-color: #ff6600; background: rgba(255, 255, 255, 0.08); }
        
        .mobile-header { display: none; position: fixed; top: 0; left: 0; right: 0; height: 70px; background: rgba(10, 10, 12, 0.8); backdrop-filter: blur(10px); z-index: 90; border-bottom: 1px solid rgba(255,255,255,0.05); align-items: center; justify-content: space-between; padding: 0 1.5rem; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 95; }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding-top: 70px !important; }
            .mobile-header { display: flex; }
            .sidebar-overlay.active { display: block; }
        }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <header class="mobile-header">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="h-8">
            <span class="font-bold text-sm tracking-tighter">WMA ADMIN</span>
        </div>
        <button id="mobileMenuBtn" class="w-10 h-10 flex items-center justify-center bg-white/5 rounded-lg text-white">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <aside class="sidebar" id="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We move, WMAFam</p>
            </div>
        </div>
        <nav class="flex-1 overflow-y-auto pr-2">
            <a href="index.php" class="nav-link"><i class="fas fa-layer-group"></i> Gestion Projets</a>
            <a href="employees.php" class="nav-link"><i class="fas fa-users-cog"></i> Équipe & Staff</a>
            <a href="tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Gestion Tâches</a>
            <a href="salaries.php" class="nav-link"><i class="fas fa-money-check-alt"></i> Gestion Salaires</a>
            <a href="project_files.php" class="nav-link active"><i class="fas fa-folder-open"></i> Fichier Projet</a>
            <a href="service_cards.php" class="nav-link"><i class="fas fa-id-card"></i> Cartes de Service</a>
            <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i> Notifications</a>
            <a href="finance.php" class="nav-link"><i class="fas fa-chart-pie"></i> Rapports Financiers</a>
            <a href="site_stats.php" class="nav-link"><i class="fas fa-chart-line"></i> Statistiques Site</a>
            <a href="users.php" class="nav-link"><i class="fas fa-user-friends"></i> Utilisateurs</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="p-8 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h2 class="text-2xl font-black">Fichier Projet</h2>
                <p class="text-[10px] text-orange-500 font-black uppercase tracking-widest">Espace collaboratif Admin & Staff</p>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="px-6 py-3 bg-orange-500 rounded-xl font-bold text-sm hover:scale-105 transition-all shadow-lg shadow-orange-500/20 whitespace-nowrap">
                    <i class="fas fa-plus mr-2"></i> Partager un fichier
                </button>
                <?php include '../../includes/header_notifications.php'; ?>
            </div>
        </header>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="filesContainer">
                <!-- Files will be loaded here -->
            </div>
        </div>
    </main>

    <!-- Upload Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="glass-panel w-full max-w-lg p-8">
            <div class="flex justify-between items-center mb-8">
                <h3 class="text-xl font-bold italic">Partager un fichier</h3>
                <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="uploadForm" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black uppercase text-orange-500 mb-2 tracking-widest">Titre du fichier</label>
                    <input type="text" name="title" required class="custom-input" placeholder="Ex: Contrat Artiste X">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-orange-500 mb-2 tracking-widest">Description (facultative)</label>
                    <textarea name="description" class="custom-input h-24 resize-none" placeholder="Précisions sur le contenu..."></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-orange-500 mb-2 tracking-widest">Sélectionner le fichier</label>
                    <input type="file" name="file" required class="custom-input border-dashed border-2 hover:border-orange-500/50">
                    <p class="text-[9px] text-gray-500 mt-2 italic">PDF, MP3, MP4, DOCX, XLSX, PPTX acceptés.</p>
                </div>
                
                <button type="submit" class="w-full py-4 bg-orange-500 rounded-xl font-black uppercase tracking-widest hover:scale-[1.02] transition-all shadow-lg shadow-orange-500/25">
                    Lancer le partage
                </button>
            </form>
        </div>
    </div>

    <script>
        const container = document.getElementById('filesContainer');
        const uploadForm = document.getElementById('uploadForm');

        function getFileIcon(type) {
            if (type.includes('pdf')) return 'fa-file-pdf text-red-500';
            if (type.includes('audio') || type.includes('mp3')) return 'fa-file-audio text-blue-500';
            if (type.includes('video') || type.includes('mp4')) return 'fa-file-video text-purple-500';
            if (type.includes('word') || type.includes('officedocument.word')) return 'fa-file-word text-blue-400';
            if (type.includes('excel') || type.includes('officedocument.spreadsheet')) return 'fa-file-excel text-green-500';
            if (type.includes('powerpoint') || type.includes('officedocument.presentation')) return 'fa-file-powerpoint text-orange-600';
            return 'fa-file text-gray-400';
        }

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        async function loadFiles() {
            try {
                const response = await fetch('../../api/project_files.php?action=get_files');
                const data = await response.json();
                if (data.success) {
                    container.innerHTML = '';
                    data.files.forEach(file => {
                        const date = new Date(file.created_at).toLocaleString('fr-FR', {
                            day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
                        });
                        
                        const div = document.createElement('div');
                        div.className = 'file-card';
                        div.innerHTML = `
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center">
                                    <i class="fas ${getFileIcon(file.file_type)} text-2xl"></i>
                                </div>
                                <div class="flex gap-2">
                                    <a href="../../${file.file_path}" download class="w-8 h-8 rounded-lg bg-orange-500/10 text-orange-500 flex items-center justify-center hover:bg-orange-500 hover:text-white transition-all">
                                        <i class="fas fa-download text-xs"></i>
                                    </a>
                                    <button onclick="deleteFile(${file.id})" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <h4 class="font-bold text-sm mb-1 truncate">${file.title}</h4>
                            <p class="text-[10px] text-gray-500 mb-4 line-clamp-2 h-8">${file.description || 'Aucune description'}</p>
                            
                            <div class="pt-4 border-t border-white/5 flex items-center justify-between">
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-black uppercase text-orange-500">${file.sender_name}</span>
                                    <span class="text-[8px] text-gray-600">${date}</span>
                                </div>
                                <span class="text-[8px] font-mono text-gray-500">${formatBytes(file.file_size)}</span>
                            </div>
                        `;
                        container.appendChild(div);
                    });
                }
            } catch (e) { console.error(e); }
        }

        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(uploadForm);
            
            try {
                const btn = uploadForm.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Upload en cours...';
                
                const response = await fetch('../../api/project_files.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Fichier partagé !',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        background: '#1a1a2e',
                        color: '#fff'
                    });
                    uploadForm.reset();
                    document.getElementById('uploadModal').classList.add('hidden');
                    loadFiles();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: data.message,
                        background: '#1a1a2e',
                        color: '#fff'
                    });
                }
            } catch (e) { 
                console.error(e);
            } finally {
                const btn = uploadForm.querySelector('button[type="submit"]');
                btn.disabled = false;
                btn.innerHTML = 'Lancer le partage';
            }
        });

        async function deleteFile(id) {
            const result = await Swal.fire({
                title: 'Supprimer ce fichier ?',
                text: "Cette action est irréversible.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6600',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler',
                background: '#1a1a2e',
                color: '#fff'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('file_id', id);
                
                try {
                    const response = await fetch('../../api/project_files.php?action=delete', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        loadFiles();
                    }
                } catch (e) { console.error(e); }
            }
        }

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            document.body.classList.toggle('overflow-hidden');
        }

        if (mobileMenuBtn) mobileMenuBtn.onclick = toggleMenu;
        if (sidebarOverlay) sidebarOverlay.onclick = toggleMenu;

        loadFiles();
    </script>
</body>
</html>
