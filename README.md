<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Livewire-4-4E56A6?logo=livewire&logoColor=white" alt="Livewire 4">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-38BDF8?logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/DB-SQLite-003B57?logo=sqlite&logoColor=white" alt="SQLite">
  <img src="https://img.shields.io/badge/Data-TMDB_API-01D277?logo=themoviedatabase&logoColor=white" alt="TMDB API">
</p>

<h1 align="center">WatchMovie</h1>

<p align="center">
  Une application personnelle de gestion de liste de films à voir, basée sur l'API TMDB.<br>
  Laravel 13 + Livewire 4 — mono-utilisateur, sans compte, à héberger soi-même.
</p>

---

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration TMDB](#configuration-tmdb)
- [Routes de l'application](#routes-de-lapplication)
- [Modèle de données](#modèle-de-données)
- [Détails techniques](#détails-techniques)
- [Limites connues](#limites-connues)
- [Licence](#licence)

## Fonctionnalités

### 🔎 Recherche (`/recherche`)
- Recherche de films via l'API TMDB, sur 4 champs combinables : **titre**, **réalisateur**, **acteur** et **studio**.
- Un champ pilote la requête TMDB (priorité titre > réalisateur > acteur > studio), les autres champs remplis affinent le résultat côté application.
- Recherche par acteur : distinction rôle **joué** / **doublage**, avec compteur par catégorie.
- Filtre par année minimale.
- Chaque résultat affiche l'affiche, l'année, la note TMDB, le réalisateur (ou le studio en mode studio) et les 3 premiers acteurs.
- Ajout à la liste personnelle directement depuis une carte de résultat, avec un tag adapté à la date de sortie :
  - **+ Cinéma** si le film n'est pas encore sorti ou l'est depuis moins de 60 jours.
  - **Déjà vue** / **+ Streaming** / **Revoir** au-delà de ce délai.
- Depuis la modale de détails, ajout en un clic de **toute une saga TMDB** (ex. Star Wars, Toy Story) non encore présente dans la liste, chaque film étant classé cinéma/streaming selon sa propre date de sortie.

### 📋 Tableau de bord (`/tableau-de-bord`)
- Liste personnelle des films ajoutés, filtrable par statut (**à voir**, **déjà vu**, **à revoir**, filtres cumulables) et par source (**cinéma**, **streaming**).
- Filtres additionnels par genre, réalisateur et studio (listes déroulantes, valeurs déduites de la liste).
- Tri au choix : priorité, ajout récent, année, note TMDB, alphabétique.
- Compteurs par statut/source.
- Priorité (Haute/Moyenne/Basse) réglable par film.
- Notation personnelle par étoiles (1 à 5), disponible une fois le film marqué comme vu ou à revoir ; cliquer à nouveau sur l'étoile déjà sélectionnée efface la note.
- Changement de statut et suppression d'un film depuis la liste.
- Bouton **🎲 Surprends-moi** : ouvre la fiche d'un film "à voir" pris au hasard dans la liste.
- Les films marqués **déjà vus** sont retirés de la grille principale et regroupés dans une section repliable "Déjà vus" (masquée par défaut) ; ils réapparaissent dans la grille normale si on les sélectionne explicitement via le filtre de statut.

### 🎬 Sorties cinéma (`/a-venir`)
- Sorties en salle en France sur les deux prochains mois (types de sortie « limitée » et « large » TMDB), regroupées par mois.

### 🪟 Modale de détails
- Résumé, genre, durée, note, réalisateur, casting.
- Bande-annonce YouTube intégrée, en français si disponible (repli automatique en langue originale sinon).
- Films similaires suggérés (recommandations TMDB).
- Si le film appartient à une saga TMDB, proposition d'ajouter toute la collection en un clic.

## Stack technique

| Composant | Détail |
|---|---|
| Framework | Laravel 13 |
| Frontend réactif | Livewire 4 (pas de SPA JS séparée) |
| Styles | Tailwind CSS 4 (via Vite), Alpine.js (fourni par Livewire) |
| Base de données | SQLite par défaut (`DB_CONNECTION=sqlite`) |
| Source de données films | [The Movie Database (TMDB)](https://www.themoviedb.org/) — API v3/v4 |
| Cache | Pilote Laravel configuré (`database` par défaut) — utilisé pour mettre en cache les résultats de recherche et les fiches films |

## Prérequis

- PHP 8.3 ou supérieur
- Composer
- Node.js + npm (pour compiler les assets Tailwind avec Vite)
- Un jeton d'accès à l'API TMDB (gratuit, voir plus bas)

## Installation

```bash
git clone <url-du-dépôt> watchmovie
cd watchmovie
composer setup
```

La commande `composer setup` (définie dans `composer.json`) enchaîne :
1. `composer install`
2. copie de `.env.example` vers `.env`
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install` puis `npm run build`

Il ne reste plus qu'à renseigner le jeton TMDB dans `.env` (voir ci-dessous), puis lancer :

```bash
php artisan serve
```

En développement, `composer dev` lance en parallèle le serveur PHP, la queue, les logs (`pail`) et Vite en mode watch.

## Configuration TMDB

L'application ne fonctionne pas sans jeton TMDB. Dans `.env` :

```env
TMDB_API_TOKEN=votre-jeton
TMDB_API_URL=https://api.themoviedb.org/3/
```

Un jeton se récupère gratuitement sur [themoviedb.org](https://www.themoviedb.org/settings/api). Les deux formats TMDB sont supportés :
- clé API v3 (chaîne de 32 caractères) ;
- jeton de lecture v4 (JWT) — détecté automatiquement et envoyé en `Bearer`.

Si `TMDB_API_TOKEN` est absent, chaque appel à l'API TMDB renvoie une erreur explicite affichée dans l'interface plutôt que de planter.

## Routes de l'application

| Route | Composant Livewire | Description |
|---|---|---|
| `/` | `home` | Page d'accueil |
| `/recherche` | `search.index` | Recherche TMDB et ajout à la liste |
| `/tableau-de-bord` | `watchlist.dashboard` | Liste personnelle |
| `/a-venir` | `upcoming.index` | Sorties cinéma à venir |

Aucune authentification n'est mise en place : l'application est prévue pour un usage personnel, en local ou sur un hébergement privé.

## Modèle de données

Table unique `watchlist_items` :

| Champ | Type | Détail |
|---|---|---|
| `tmdb_id` | string, unique | Identifiant TMDB du film |
| `title`, `year`, `poster_url`, `type`, `genre`, `runtime`, `plot`, `imdb_rating` | — | Métadonnées récupérées depuis TMDB au moment de l'ajout |
| `director`, `actors`, `studio` | string | Réalisateur, 3 premiers acteurs, studio(s)/société(s) de production (liste séparée par des virgules) |
| `status` | string | `to_watch`, `watched` ou `to_rewatch` |
| `source` | string | `cinema` ou `streaming` |
| `watched_at` | datetime, nullable | Renseigné automatiquement au passage en « déjà vu » ou « à revoir » |
| `priority` | integer | 1 (haute) à 3 (basse), réglable depuis le tableau de bord — pilote le tri par défaut |
| `note` | string, nullable | Champ libre présent en base, sans interface dédiée pour l'instant |
| `personal_rating` | integer, nullable | Note personnelle 1 à 5, réglable par étoiles une fois le film vu ou à revoir |

## Détails techniques

- **Recherche multi-champs** : selon le champ principal, l'application interroge `search/movie`, `search/person` (+ `movie_credits`) ou `search/company` (+ `discover/movie`), puis enrichit chaque résultat (réalisateur, casting, studio) via un pool de requêtes HTTP concurrentes (`Http::pool`).
- **Double niveau de cache** :
  - un cache par recherche (requête + mode + année minimale), 6 heures ;
  - un cache par film (réalisateur/casting/studio), 7 jours, partagé entre toutes les recherches — un film déjà rencontré dans une recherche précédente n'est jamais re-téléchargé.
- **Pagination studio parallélisée** : la première page détermine le nombre total de pages, les pages suivantes sont récupérées en une seule vague via `Http::pool` plutôt qu'en séquence.
- **Bande-annonce** : récupérée via `append_to_response=credits,videos` sur l'endpoint `movie/{id}`, avec repli sur un second appel non filtré par langue si aucune vidéo française n'existe.
- **Films similaires & sagas** : les recommandations TMDB (`movie/{id}/recommendations`, 6 films max) sont mises en cache 3 jours ; l'ajout d'une saga entière (`collection/{id}`) est également mis en cache 3 jours et ignore les films déjà présents dans la liste.

## Limites connues

- Films uniquement (pas de séries TV).
- Mono-utilisateur, sans compte ni partage de liste.
- Champ `note` (texte libre) présent en base mais sans interface pour le modifier.
- Pas de suite de tests dédiée à l'application (seuls les tests d'exemple par défaut de Laravel sont présents).
- Pas d'intégration continue configurée.

---

<sub>Ce produit utilise l'API TMDB mais n'est ni approuvé ni certifié par TMDB.</sub>
