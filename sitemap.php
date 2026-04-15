<?php
/**
 * OmniDownloader — Sitemap.xml Generator
 * Generates XML sitemap for Google, Bing, and other search engines
 * Access via: /sitemap.php
 */

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=86400'); // Cache por 24 horas

$siteUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'omnidownloader.com');
$languages = ['pt', 'en', 'es', 'ja'];
$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ' .
     'xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

// Página principal em todas as línguas
foreach ($languages as $lang) {
    $url = ($lang === 'pt') ? $siteUrl . '/' : $siteUrl . '/?lang=' . $lang;
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>$today</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>1.0</priority>\n";
    
    // Adicionar alternativas de idioma
    echo "    <xhtml:link rel=\"alternate\" hreflang=\"pt\" href=\"" . 
         htmlspecialchars($siteUrl . '/', ENT_XML1, 'UTF-8') . "\" />\n";
    if ($lang !== 'pt') {
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"" . 
             htmlspecialchars($siteUrl . '/?lang=en', ENT_XML1, 'UTF-8') . "\" />\n";
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"es\" href=\"" . 
             htmlspecialchars($siteUrl . '/?lang=es', ENT_XML1, 'UTF-8') . "\" />\n";
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"ja\" href=\"" . 
             htmlspecialchars($siteUrl . '/?lang=ja', ENT_XML1, 'UTF-8') . "\" />\n";
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . 
             htmlspecialchars($siteUrl . '/', ENT_XML1, 'UTF-8') . "\" />\n";
    }
    
    echo "  </url>\n";
}

echo '</urlset>' . "\n";
