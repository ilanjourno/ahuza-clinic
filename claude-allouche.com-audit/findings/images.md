# Images — 40/100

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

