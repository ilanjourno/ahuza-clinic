# Plan d'action SEO — claude-allouche.com

## Critical
- [ ] **LCP très lent sur mobile** (Performance (CWV)) — Compression + cache (voir Technique), images optimisées, CSS allégé.

## High
- [ ] **Attribut lang faux et pas de RTL** (Technical SEO) — Appeler App::setLocale($locale) dans SetLocale et ajouter dir="rtl" quand la locale est he.
- [ ] **hreflang des articles pointent vers l'accueil** (Technical SEO) — Générer les alternates à partir des traductions existantes de l'article + x-default ; les ajouter aussi au sitemap (xhtml:link).
- [ ] **38 liens internes en 404** (Technical SEO) — N'afficher que les langues disponibles pour l'article (ou renvoyer vers l'accueil de la langue).
- [ ] **Pas de compression ni de cache navigateur** (Technical SEO) — Servir via nginx + php-fpm (ou FrankenPHP) avec gzip/brotli et Cache-Control: public, max-age=31536000, immutable sur /build/assets.
- [ ] **Signaux E-E-A-T faibles sur les articles (YMYL)** (Content Quality) — Afficher sur chaque article : auteur (Dr Claude Allouche, gynécologue-obstétricien) avec lien vers une bio, date de publication et de mise à jour réelles ; corriger la date des cartes.
- [ ] **Mentions obsolètes « Ahuza » dans le contenu** (Content Quality) — Mettre à jour FAQ, articles et mots-clés depuis le dashboard (3 langues).
- [ ] **Pas de H1 sur les articles** (On-Page SEO) — Rendre le titre de l'article en <h1> dans Articles/Show et démarrer le contenu en <h2>.
- [ ] **Aucune donnée structurée** (Schema / Structured Data) — Ajouter Physician + MedicalClinic (2 adresses, téléphone, spécialité, langues parlées, sameAs Facebook/LinkedIn) sur l'accueil ; MedicalWebPage/Article avec author, datePublished, dateModified sur les articles ; FAQPage n'apporte plus de rich result mais reste lisible par les moteurs IA.
- [ ] **CSS et JS lourds** (Performance (CWV)) — Retirer les modules SCSS non utilisés du thème, purger le CSS, découper le JS.
- [ ] **Entité médecin non structurée** (AI Search Readiness) — Schema Physician + page « À propos » dédiée avec diplômes, affiliations (Laniado, Clalit), langues.
- [ ] **Illustrations d'articles non optimisées** (Images) — Redimensionner/convertir en WebP à l'upload (ou via un service d'images) ; servir des tailles adaptées (srcset).
- [ ] **Citations NAP obsolètes** (Local SEO (hors score)) — Mettre à jour LinkedIn, Facebook et les annuaires ; demander la réindexation dans Google Search Console.
- [ ] **Fiches Google Business Profile à créer/mettre à jour** (Local SEO (hors score)) — Une fiche GBP par cabinet, catégorie « Gynécologue-obstétricien », mêmes nom/adresse/téléphone que le site, lien vers la page de la langue.

## Medium
- [ ] **Temps de réponse serveur irrégulier** (Technical SEO) — Même correctif que ci-dessus ; envisager min-instances=1 sur Cloud Run pour éviter les cold starts.
- [ ] **Accueil dupliqué sur /** (Technical SEO) — Rediriger / vers la langue détectée (302) et déclarer / en x-default, ou canonical / → /fr ; retirer / du sitemap.
- [ ] **Sitemap figé entre deux déploiements** (Technical SEO) — Régénérer le sitemap à la publication d'un article, ou lancer `schedule:work` dans supervisord.
- [ ] **Couverture linguistique incomplète** (Content Quality) — Prioriser la traduction hébreu des articles les plus consultés.
- [ ] **Meta descriptions trop longues ou dupliquées** (On-Page SEO) — Rédiger une description de 140–155 caractères par article ; tronquer proprement à défaut.
- [ ] **Titres trop longs** (On-Page SEO) — Raccourcir les titres d'articles (≈ 50 caractères avant le suffixe).
- [ ] **Pas de lazy loading ni de dimensions** (Images) — loading="lazy" hors écran + width/height explicites.
- [ ] **Visibilité hébreu quasi nulle** (Local SEO (hors score)) — Fiches GBP et annuaires israéliens (easy.co.il, B144) en hébreu.

## Low
- [ ] **Domaine www non résolu** (Technical SEO) — Ajouter un enregistrement DNS www et rediriger en 301 vers le domaine nu.
- [ ] **En-têtes de sécurité absents** (Technical SEO) — Ajouter ces en-têtes au serveur web ; désactiver expose_php.
- [ ] **Pages courtes** (Content Quality) — Étoffer ou fusionner les articles trop courts.
- [ ] **Open Graph imprécis** (On-Page SEO) — og:type=article (+ article:published_time) et locales au format langue_PAYS.
- [ ] **Titre hébreu de l'accueil au féminin** (On-Page SEO) — Corriger la clé title de he.json en « רופא נשים מומחה ».
- [ ] **Pas de llms.txt** (AI Search Readiness) — Publier un llms.txt listant les pages clés (accueil, à propos, articles phares) dans les 3 langues.
- [ ] **Textes alternatifs manquants ou génériques** (Images) — Alt descriptifs (ou alt="" si décoratif) et aria-label sur les liens icônes.

## Calendrier
### Phase 1 : Correctifs critiques — Semaine 1
- Servir le site avec compression et cache (nginx/php-fpm ou FrankenPHP)
- lang/dir corrects par langue
- hreflang par article + x-default, drapeaux limités aux traductions existantes
- H1 sur les articles
- Fiches Google Business Profile pour les 2 cabinets, mise à jour LinkedIn/Facebook, réindexation GSC

### Phase 2 : Améliorations à fort impact — Semaines 2-3
- JSON-LD Physician/MedicalClinic + Article/MedicalWebPage
- Auteur, dates réelles et bio sur les articles
- Retirer « Ahuza » des FAQ, articles et mots-clés
- Optimisation des illustrations d'articles (WebP, srcset, lazy)
- Redirection / → langue détectée, sitemap régénéré à la publication avec alternates

### Phase 3 : Contenu et autorité — Mois 2
- Meta descriptions 140–155 car. et titres < 60 car.
- Traduire en hébreu les 25 articles manquants
- Alléger le CSS du thème
- Annuaires israéliens en hébreu, llms.txt

### Phase 4 : Suivi — Continu
- Google Search Console (couverture, hreflang, requêtes locales)
- Core Web Vitals terrain (CrUX) une fois le trafic suffisant
- Avis Google réguliers sur les 2 fiches

