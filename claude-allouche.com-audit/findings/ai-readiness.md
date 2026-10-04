# AI Search Readiness — 45/100

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

