<?php
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/xml; charset=utf-8');

try {
    $db = getDBConnection();
    
    // Fetch all active UA artists
    $stmt = $db->query("SELECT id FROM ua_artists WHERE is_ua = 1 ORDER BY id DESC");
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback if database error occurs
    $artists = [];
}

// Base URL for the WMA United Africa platform
$siteUrl = "https://wmahub.com/wmaunitedafrica/";

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">

  <!-- Home Page - French version -->
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>?lang=fr</loc>
    <xhtml:link rel="alternate" hreflang="fr" href="<?= htmlspecialchars($siteUrl) ?>?lang=fr" />
    <xhtml:link rel="alternate" hreflang="en" href="<?= htmlspecialchars($siteUrl) ?>?lang=en" />
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($siteUrl) ?>" />
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>

  <!-- Home Page - English version -->
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>?lang=en</loc>
    <xhtml:link rel="alternate" hreflang="fr" href="<?= htmlspecialchars($siteUrl) ?>?lang=fr" />
    <xhtml:link rel="alternate" hreflang="en" href="<?= htmlspecialchars($siteUrl) ?>?lang=en" />
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($siteUrl) ?>" />
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>

  <!-- Dynamic Artists profiles -->
  <?php foreach ($artists as $artist): 
      $artistId = (int)$artist['id'];
  ?>
  <!-- Artist ID <?= $artistId ?> - French -->
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>?artist=<?= $artistId ?>&amp;lang=fr</loc>
    <xhtml:link rel="alternate" hreflang="fr" href="<?= htmlspecialchars($siteUrl) ?>?artist=<?= $artistId ?>&amp;lang=fr" />
    <xhtml:link rel="alternate" hreflang="en" href="<?= htmlspecialchars($siteUrl) ?>?artist=<?= $artistId ?>&amp;lang=en" />
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($siteUrl) ?>?artist=<?= $artistId ?>" />
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>

  <!-- Artist ID <?= $artistId ?> - English -->
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>?artist=<?= $artistId ?>&amp;lang=en</loc>
    <xhtml:link rel="alternate" hreflang="fr" href="<?= htmlspecialchars($siteUrl) ?>?artist=<?= $artistId ?>&amp;lang=fr" />
    <xhtml:link rel="alternate" hreflang="en" href="<?= htmlspecialchars($siteUrl) ?>?artist=<?= $artistId ?>&amp;lang=en" />
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($siteUrl) ?>?artist=<?= $artistId ?>" />
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <?php endforeach; ?>

</urlset>
