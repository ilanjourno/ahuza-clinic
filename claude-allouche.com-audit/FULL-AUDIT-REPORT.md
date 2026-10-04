# Audit SEO — claude-allouche.com

*Audit du 4 octobre 2026 · 293 URL explorées (255 pages HTML) · Lighthouse mobile sur 2 pages*

## Synthèse

**Score SEO global : 44/100**

Type d'activité : Cabinet médical local (YMYL) — gynécologue-obstétricien, 2 adresses (Raanana, Netanya), site trilingue fr/en/he.

| Catégorie | Poids | Score |
|---|---|---|
| Technical SEO | 22 % | 48/100 |
| Content Quality | 23 % | 58/100 |
| On-Page SEO | 20 % | 50/100 |
| Schema / Structured Data | 10 % | 10/100 |
| Performance (CWV) | 10 % | 30/100 |
| AI Search Readiness | 10 % | 45/100 |
| Images | 5 % | 40/100 |

### 5 problèmes prioritaires
1. LCP très lent sur mobile (Performance (CWV))
2. Attribut lang faux et pas de RTL (Technical SEO)
3. hreflang des articles pointent vers l'accueil (Technical SEO)
4. 38 liens internes en 404 (Technical SEO)
5. Pas de compression ni de cache navigateur (Technical SEO)

### 5 gains rapides
1. Corriger lang/dir dans app.blade.php via SetLocale
2. Rendre le titre d'article en H1
3. Masquer les drapeaux des traductions absentes (supprime 38 liens 404)
4. Remplacer la date codée en dur des cartes d'articles par published_at
5. Ajouter le JSON-LD Physician/MedicalClinic sur l'accueil

### Limites de l'audit
- Pas de données Google Search Console / GA4 / CrUX
- PageSpeed Insights indisponible (quota) : Lighthouse local avec throttling mobile simulé
- Fiches Google Business Profile non vérifiables sans accès

---

## Technical SEO — 48/100

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


---

## Content Quality — 58/100

## Ce qui fonctionne
- 247 articles médicaux, médiane ~660 mots, jusqu'à 2 500 mots
- 70 articles disponibles dans les 3 langues
- Parcours de l'auteur détaillé (formation, 25 ans à Évreux, Laniado)

## Problèmes

### [High] Signaux E-E-A-T faibles sur les articles (YMYL)
Les pages articles n'affichent ni auteur, ni date de publication/mise à jour, ni qualifications ; la carte d'article affiche « Nov 26, 2023 » codé en dur pour tous les articles et un lien auteur vers #.

**Correctif :** Afficher sur chaque article : auteur (Dr Claude Allouche, gynécologue-obstétricien) avec lien vers une bio, date de publication et de mise à jour réelles ; corriger la date des cartes.

### [High] Mentions obsolètes « Ahuza » dans le contenu
3 réponses de FAQ et la signature de plusieurs articles mentionnent encore la clinique Ahuza ; les mots-clés d'articles contiennent « Ahuza Clinic » ; les liens Google Maps des FAQ sont peut-être à mettre à jour.

**Correctif :** Mettre à jour FAQ, articles et mots-clés depuis le dashboard (3 langues).

### [Medium] Couverture linguistique incomplète
13 articles uniquement en français, 12 sans version hébreu.

**Correctif :** Prioriser la traduction hébreu des articles les plus consultés.

### [Low] Pages courtes
18 articles sous 300 mots (gabarit compris), le plus court 144 mots (/he/articles/menopause1).

**Correctif :** Étoffer ou fusionner les articles trop courts.


---

## On-Page SEO — 50/100

## Ce qui fonctionne
- Titres et meta descriptions présents sur toutes les pages
- Titres d'articles uniques, suffixés « - Dr Claude Allouche »

## Problèmes

### [High] Pas de H1 sur les articles
233 des 247 pages articles n'ont aucun H1 (le titre n'est rendu que dans <title>) ; une page en a 3.

**Correctif :** Rendre le titre de l'article en <h1> dans Articles/Show et démarrer le contenu en <h2>.

### [Medium] Meta descriptions trop longues ou dupliquées
240/255 dépassent 160 caractères ; 36 groupes de doublons (17 pages FR reprennent le texte « À propos » faute de description).

**Correctif :** Rédiger une description de 140–155 caractères par article ; tronquer proprement à défaut.

### [Medium] Titres trop longs
126/255 titres dépassent 60 caractères et seront tronqués dans Google.

**Correctif :** Raccourcir les titres d'articles (≈ 50 caractères avant le suffixe).

### [Low] Open Graph imprécis
og:type vaut « website » sur les articles ; og:locale vaut « fr »/« he » au lieu de fr_FR / he_IL / en_US.

**Correctif :** og:type=article (+ article:published_time) et locales au format langue_PAYS.

### [Low] Titre hébreu de l'accueil au féminin
« ד״ר קלוד אלוש - רופאת נשים מומחה » utilise « רופאת » (féminin).

**Correctif :** Corriger la clé title de he.json en « רופא נשים מומחה ».


---

## Schema / Structured Data — 10/100

## Problèmes

### [High] Aucune donnée structurée
0 page sur 255 contient du JSON-LD.

**Correctif :** Ajouter Physician + MedicalClinic (2 adresses, téléphone, spécialité, langues parlées, sameAs Facebook/LinkedIn) sur l'accueil ; MedicalWebPage/Article avec author, datePublished, dateModified sur les articles ; FAQPage n'apporte plus de rich result mais reste lisible par les moteurs IA.


---

## Performance (CWV) — 30/100

## Ce qui fonctionne
- CLS faible (0,07 accueil, 0 article)
- TBT faible (50 ms)

## Problèmes

### [Critical] LCP très lent sur mobile
Lighthouse mobile : accueil perf 54, FCP 7,7 s, LCP 16,3 s, 3,9 Mo ; article perf 55, FCP 9,0 s, LCP 9,9 s. Pas de données terrain CrUX (trafic insuffisant ou quota PSI épuisé).

**Correctif :** Compression + cache (voir Technique), images optimisées, CSS allégé.

### [High] CSS et JS lourds
CSS 422 Ko (thème complet : prism, swiper, plyr, docs…), 391 Ko de règles inutilisées ; JS 321 Ko dont 204 Ko inutilisés.

**Correctif :** Retirer les modules SCSS non utilisés du thème, purger le CSS, découper le JS.


---

## AI Search Readiness — 45/100

## Ce qui fonctionne
- Contenu rendu côté serveur, lisible sans JavaScript
- robots.txt n'exclut aucun crawler IA
- Articles longs en format questions/réponses, citables

## Problèmes

### [High] Entité médecin non structurée
Sans JSON-LD ni page auteur, les moteurs IA ne relient pas les articles au Dr Allouche, à ses cabinets et à ses qualifications.

**Correctif :** Schema Physician + page « À propos » dédiée avec diplômes, affiliations (Laniado, Clalit), langues.

### [Low] Pas de llms.txt
/llms.txt renvoie une redirection puis 404.

**Correctif :** Publier un llms.txt listant les pages clés (accueil, à propos, articles phares) dans les 3 langues.


---

## Images — 40/100

## Ce qui fonctionne
- Nouvelles images du site optimisées (logo 44 Ko, photos clinique 115–215 Ko)

## Problèmes

### [High] Illustrations d'articles non optimisées
Sur l'accueil, deux illustrations d'articles pèsent 695 Ko et 546 Ko (JPEG originaux sur GCS) ; miniature YouTube 426 Ko.

**Correctif :** Redimensionner/convertir en WebP à l'upload (ou via un service d'images) ; servir des tailles adaptées (srcset).

### [Medium] Pas de lazy loading ni de dimensions
Aucune image n'a loading="lazy" ; images sans width/height (signalé par Lighthouse).

**Correctif :** loading="lazy" hors écran + width/height explicites.

### [Low] Textes alternatifs manquants ou génériques
Icône WhatsApp sans alt (2 occurrences par page) ; « Flag », « Avatar » génériques sur toutes les pages ; liens sociaux sans nom accessible.

**Correctif :** Alt descriptifs (ou alt="" si décoratif) et aria-label sur les liens icônes.


---

## Local SEO (hors score)

## Problèmes

### [High] Citations NAP obsolètes
LinkedIn indique « Ahuza clinic Raanana » ; l'index Google affiche encore des titres « … - Ahuza » ; un annuaire français (mablouseblanche.fr) le place à Évreux ; « Clinic 4 You, Hamasger 8 » n'apparaît dans aucun annuaire.

**Correctif :** Mettre à jour LinkedIn, Facebook et les annuaires ; demander la réindexation dans Google Search Console.

### [High] Fiches Google Business Profile à créer/mettre à jour
Aucune fiche n'a été trouvée pour les nouvelles adresses (Raanana Hamasger 8, Netanya Migdal Hahaloutsim).

**Correctif :** Une fiche GBP par cabinet, catégorie « Gynécologue-obstétricien », mêmes nom/adresse/téléphone que le site, lien vers la page de la langue.

### [Medium] Visibilité hébreu quasi nulle
Aucun résultat pour « קלוד אלוש גינקולוג ».

**Correctif :** Fiches GBP et annuaires israéliens (easy.co.il, B144) en hébreu.

