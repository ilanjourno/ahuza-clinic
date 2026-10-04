# Technical SEO — 48/100

## Ce qui fonctionne
- Rendu serveur (Inertia SSR) : le contenu complet est dans le HTML initial
- robots.txt propre, bloque /dashboard, déclare le sitemap
- HTTP → HTTPS en 1 redirection
- Sitemap de 254 URL, toutes en 200, avec lastmod et images
- Canonicals présents et en absolu sur 254/255 pages

## Problèmes

### [High] Attribut lang faux et pas de RTL
Les 255 pages ont <html lang="en">, y compris /fr et /he ; aucune page hébreu n'a dir="rtl". app.blade.php lit app()->getLocale() alors que SetLocale ne fait que Session::put.

**Correctif :** Appeler App::setLocale($locale) dans SetLocale et ajouter dir="rtl" quand la locale est he.

### [High] hreflang des articles pointent vers l'accueil
246 pages articles déclarent hreflang fr/en/he vers /fr, /en, /he (GuestLayout) au lieu des traductions de l'article ; aucun x-default.

**Correctif :** Générer les alternates à partir des traductions existantes de l'article + x-default ; les ajouter aussi au sitemap (xhtml:link).

### [High] 38 liens internes en 404
Les drapeaux de langue lient vers des traductions inexistantes (13 articles FR seuls, 12 sans hébreu) : 25 en /he, 13 en /en.

**Correctif :** N'afficher que les langues disponibles pour l'article (ou renvoyer vers l'accueil de la langue).

### [High] Pas de compression ni de cache navigateur
HTML (140 Ko), CSS (431 Ko) et JS (328 Ko) servis sans gzip/brotli ; assets hashés sans Cache-Control. La prod tourne sur `php artisan serve` (serveur de dev PHP) via supervisord.

**Correctif :** Servir via nginx + php-fpm (ou FrankenPHP) avec gzip/brotli et Cache-Control: public, max-age=31536000, immutable sur /build/assets.

### [Medium] Temps de réponse serveur irrégulier
TTFB moyen 1,05 s sur 255 pages, pic à 5,97 s (rendu SSR + serveur mono-processus).

**Correctif :** Même correctif que ci-dessus ; envisager min-instances=1 sur Cloud Run pour éviter les cold starts.

### [Medium] Accueil dupliqué sur /
/ sert le contenu FR avec un canonical vers lui-même et figure dans le sitemap, en doublon de /fr.

**Correctif :** Rediriger / vers la langue détectée (302) et déclarer / en x-default, ou canonical / → /fr ; retirer / du sitemap.

### [Medium] Sitemap figé entre deux déploiements
Le sitemap est généré au build Docker ; la tâche planifiée quotidienne ne tourne pas (supervisord ne lance pas le scheduler). Les articles publiés entre deux déploiements n'y sont pas.

**Correctif :** Régénérer le sitemap à la publication d'un article, ou lancer `schedule:work` dans supervisord.

### [Low] Domaine www non résolu
www.claude-allouche.com ne résout pas en DNS.

**Correctif :** Ajouter un enregistrement DNS www et rediriger en 301 vers le domaine nu.

### [Low] En-têtes de sécurité absents
Pas de HSTS, CSP, X-Content-Type-Options, Referrer-Policy ; X-Powered-By: PHP/8.3 exposé.

**Correctif :** Ajouter ces en-têtes au serveur web ; désactiver expose_php.

