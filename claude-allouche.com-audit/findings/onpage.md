# On-Page SEO — 50/100

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

