# Performance (CWV) — 30/100

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

