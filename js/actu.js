document.addEventListener('DOMContentLoaded', function () {
    const actualitesContainer = document.getElementById('actualites-container');

    // Vérifie si le conteneur HTML existe avant de continuer
    if (!actualitesContainer) {
        console.error('Erreur: Le conteneur #actualites-container est manquant dans le HTML.');
        return;
    }

    // URL de votre API WordPress pour les 4 derniers articles avec les images
    // TRÈS IMPORTANT: Vérifiez que cette URL est correcte !
    // Le paramètre `_embed` est essentiel pour inclure les données des images.
    const apiUrl = 'https://wmahub.com/blog/wp-json/wp/v2/posts?per_page=4&_embed';

    console.log('Tentative de récupération des articles depuis:', apiUrl);

    fetch(apiUrl)
        .then(response => {
            console.log('Réponse de l\'API reçue:', response);
            if (!response.ok) {
                // Si la réponse n'est pas OK (ex: 404, 500), jeter une erreur
                throw new Error(`Erreur HTTP ! Statut: ${response.status} - ${response.statusText}`);
            }
            return response.json(); // Convertit la réponse en JSON
        })
        .then(posts => {
            console.log('Données JSON des articles reçues:', posts);

            if (posts && posts.length > 0) {
                actualitesContainer.innerHTML = ''; // Nettoie le conteneur pour afficher les articles

                posts.forEach(post => {
                    const articleCard = document.createElement('article');
                    articleCard.className = 'actu-card';

                    // Logique pour trouver l'image vedette de l'article
                    const featuredImage = post._embedded?.['wp:featuredmedia']?.[0]?.source_url || 
                                          post.jetpack_featured_media_url || // Pour les sites Jetpack
                                          null;

                    const imageUrl = featuredImage || './asset/placeholder.jpg'; // Image de remplacement

                    const postHtml = `
                        <div class="actu-card-header">
                            <img src="${imageUrl}" alt="${post.title.rendered || 'Image d\'article'}">
                        </div>
                        <div class="actu-content">
                            <h3>${post.title.rendered || 'Titre inconnu'}</h3>
                            <p>${(post.excerpt?.rendered || 'Pas de résumé disponible.').replace(/<p>|<\/p>|<a\b[^>]*>(.*?)<\/a>|&hellip;/g, '').substring(0, 150)}...</p>
                            <a href="${post.link}" target="_blank" rel="noopener noreferrer" class="read-more">Lire la suite</a>
                        </div>
                    `;

                    articleCard.innerHTML = postHtml;
                    actualitesContainer.appendChild(articleCard);
                });
            } else {
                actualitesContainer.innerHTML = '<p>Aucun article d\'actualité n\'est disponible pour le moment.</p>';
                console.log('Aucun article trouvé dans la réponse de l\'API.');
            }
        })
        .catch(error => {
            console.error('Une erreur s\'est produite lors de la récupération des actualités:', error);
            actualitesContainer.innerHTML = `<p>Impossible de charger les actualités. Erreur: ${error.message}. Vérifiez l'URL de votre API ou les permissions CORS.</p>`;
        });
});