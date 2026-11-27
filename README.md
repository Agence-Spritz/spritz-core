# Spritz Core
Base de fonctionnalités communes pour les thèmes Spritz.
Installation via Composer: `composer require spritz/spritz-core`


## Données structurées (JSON-LD)

`json-ld/jsonld-generator.php` ajoute au `<head>` un graphe qui complète celui de Yoast : l'entreprise (`LocalBusiness`, ou une par adresse de `org_locations`), son catalogue de services (`OfferCatalog`, relié par `hasOfferCatalog`) et ses adhésions (`memberOf`), d'après les réglages du site (Apparence > Personnaliser > Informations du site).

- **Société** : l'entreprise renvoie à `#organization` (`parentOrganization`). Quand Yoast décrit la société (Réglages > Représentation du site : société, avec un nom), c'est son nœud ; sinon le plugin produit lui-même un nœud `Organization` (nom du site, raison sociale, URL, logo, réseaux), et la référence ne pointe jamais dans le vide.
- **Réseaux** (`facebook`, `twitter`, `instagram`, `linkedin`, `youtube`, `tiktok`) en `sameAs`.
- **Téléphone** au format international (`spritz_jsonld_telephone()`) : « (0) » retiré, numéro national converti d'après le pays (BE, FR, LU, CH, NL, DE).
- **Filtre `spritz_jsonld_graph`** : les nœuds avant la sortie, pour les compléter par site (type plus précis, zone desservie, identifiants…).
