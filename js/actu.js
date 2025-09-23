document.addEventListener('DOMContentLoaded', function () {
    const actualitesContainer = document.getElementById('actualites-container');

    if (!actualitesContainer) {
        return;
    }

    // Remplacez cette URL par la vôtre et ajoutez les paramètres
    // per_page=4 pour limiter à 4 articles et _embed pour les images
    const apiUrl = 'https://wmahub.com/blog/wp-json/wp/v2/posts?per_page=2&_embed';

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP ! Statut: ${response.status}`);
            }
            return response.json();
        })
        .then(posts => {
            if (posts && posts.length > 0) {
                posts.forEach(post => {
                    const articleCard = document.createElement('article');
                    articleCard.className = 'actu-card';

                    const featuredImage = post._embedded?.['wp:featuredmedia']?.[0]?.source_url || 
                                          null;

                    const imageUrl = featuredImage || './asset/placeholder.jpg';

                    const postHtml = `
                        <a href="${post.link}" target="_blank" rel="noopener noreferrer" aria-label="Lire l'article: ${post.title.rendered}">
                            <img src="${imageUrl}" alt="${post.title.rendered}">
                        </a>
                        <div class="actu-content">
                            <h3>${post.title.rendered}</h3>
                            <p>${post.excerpt.rendered.replace(/<p>|<\/p>|<a\b[^>]*>(.*?)<\/a>|&hellip;/g, '').substring(0, 150)}...</p>
                            <a href="${post.link}" target="_blank" rel="noopener noreferrer" class="read-more">Lire la suite</a>
                        </div>
                    `;

                    articleCard.innerHTML = postHtml;
                    actualitesContainer.appendChild(articleCard);
                });
            } else {
                actualitesContainer.innerHTML = '<p>Aucun article d\'actualité n\'est disponible pour le moment.</p>';
            }
        })
        .catch(error => {
            console.error('Une erreur s\'est produite lors de la récupération des actualités:', error);
            actualitesContainer.innerHTML = '<p>Impossible de charger les actualités. Vérifiez l\'URL de votre API ou réessayez plus tard.</p>';
        });
});