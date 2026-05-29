# Guide d'installation — EduTest v2AI

> Branche : `v2AI` — Mise à jour : Avril 2026  
> Ce guide couvre toutes les nouvelles fonctionnalités ajoutées depuis la branche `main`.

---

## Sommaire

1. [Prérequis](#1-prérequis)
2. [Installation depuis zéro](#2-installation-depuis-zéro)
3. [Mise à jour depuis main](#3-mise-à-jour-depuis-main)
4. [Variables d'environnement](#4-variables-denvironnement)
5. [Migrations base de données](#5-migrations-base-de-données)
6. [Dossiers & permissions](#6-dossiers--permissions)
7. [Nouvelles fonctionnalités — Guide d'utilisation](#7-nouvelles-fonctionnalités--guide-dutilisation)
8. [Résolution de problèmes](#8-résolution-de-problèmes)

---

## 1. Prérequis

| Outil | Version minimale |
|-------|-----------------|
| PHP | 8.2+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| Symfony CLI | Dernière version (recommandé) |
| Node.js | Optionnel (pas de build JS requis) |

Clé API Groq obligatoire pour toutes les fonctions IA → [console.groq.com](https://console.groq.com)

---

## 2. Installation depuis zéro

```bash
# 1. Cloner le dépôt sur la branche v2AI
git clone <url-du-repo> edutest
cd edutest
git checkout v2AI

# 2. Installer les dépendances PHP
composer install

# 3. Copier et configurer les variables d'environnement
cp .env .env.local
# → Éditer .env.local (voir section 4)

# 4. Créer la base de données et appliquer les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Créer les dossiers nécessaires
mkdir -p public/uploads/avatars

# 6. Vider le cache
php bin/console cache:clear

# 7. Lancer le serveur
symfony server:start
# ou : php -S localhost:8000 -t public/
```

---

## 3. Mise à jour depuis main

```bash
# Se placer sur v2AI
git fetch origin
git checkout v2AI
git pull origin v2AI

# Mettre à jour les dépendances
composer install

# Appliquer les nouvelles migrations (Classe, profilePicture)
php bin/console doctrine:migrations:migrate

# Créer le dossier uploads si absent
mkdir -p public/uploads/avatars

# Vider le cache
php bin/console cache:clear
```

---

## 4. Variables d'environnement

Fichier `.env.local` à la racine (ne jamais commiter ce fichier) :

```env
# Base de données
DATABASE_URL="mysql://root:@127.0.0.1:3306/edutest_db?serverVersion=8.0.32&charset=utf8mb4"

# Clé API Groq — obligatoire pour toutes les features IA
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Environnement
APP_ENV=dev
APP_SECRET=changez_cette_valeur_en_production
```

> La clé Groq est disponible sur [console.groq.com](https://console.groq.com) → API Keys → Create API Key.  
> Le modèle utilisé est `llama-3.1-8b-instant` (gratuit sur le tier Groq).

---

## 5. Migrations base de données

Deux nouvelles migrations sont incluses dans `v2AI` :

| Migration | Contenu |
|-----------|---------|
| `Version20260330134050` | Ajout de la table `classe` (niveau, nom, année, année scolaire) |
| `Version20260330145158` | Ajout de `profile_picture` et `classe_id` sur la table `user` |

```bash
# Vérifier l'état des migrations
php bin/console doctrine:migrations:status

# Appliquer toutes les migrations en attente
php bin/console doctrine:migrations:migrate
```

---

## 6. Dossiers & permissions

Le dossier `public/uploads/avatars/` doit exister et être accessible en écriture par le serveur web.

```bash
mkdir -p public/uploads/avatars
chmod 775 public/uploads/avatars        # Linux/Mac
# Sur Windows : aucune action nécessaire si le serveur tourne en local
```

Les photos de profil uploadées sont stockées dans ce dossier sous la forme `<uuid>.<ext>`.  
Taille maximale autorisée : **2 Mo**. Formats acceptés : JPG, PNG, WebP.

---

## 7. Nouvelles fonctionnalités — Guide d'utilisation

### 7.1 Gestion multi-classes (Admin)

**Accès** : Navbar Admin → **Classes** | URL : `/admin/classes`

Permet de créer et gérer des classes pour organiser les étudiants.

| Action | Description |
|--------|-------------|
| Créer une classe | Choisir le niveau (BTS, BUT, Licence, Licence Pro, Master, Autre), nom, année, année scolaire |
| Ajouter un étudiant | Depuis la fiche de la classe → recherche par nom/email → bouton Ajouter |
| Retirer un étudiant | Bouton Retirer sur chaque ligne étudiant (AJAX, sans rechargement) |
| Affecter un examen à toute une classe | Navbar Enseignant → **Affecter à une classe** → sélectionner examen + classe |

> Les étudiants déjà affectés à un examen sont automatiquement ignorés lors d'une affectation par classe.

---

### 7.2 Photo de profil (Étudiants & Enseignants)

**Accès** : Clic sur l'avatar en haut à droite → **Mon profil** | URL : `/profile`

- Cliquer sur l'avatar pour sélectionner une photo (JPG/PNG/WebP, max 2 Mo)
- L'upload est **automatique** dès la sélection du fichier
- Un bouton **Supprimer la photo** apparaît si une photo est définie
- Si aucune photo : l'initiale du prénom s'affiche sur fond dégradé

---

### 7.3 Chatbot IA (tous les rôles connectés)

**Accès** : Bouton flottant en bas à droite de toutes les pages

- Limite : **10 messages par jour** (remise à zéro à minuit, stockée dans le navigateur)
- Le chatbot conserve l'historique de la conversation en cours
- Utilise le modèle Groq `llama-3.1-8b-instant` en mode conversationnel
- Pas de cache : chaque message est envoyé avec le contexte complet

---

### 7.4 Rapport IA (Admin)

**Accès** : Navbar Admin → **Rapport IA** | URL : `/admin/ai-insights`

L'IA analyse les données de la plateforme et génère un rapport avec :
- Vue d'ensemble des statistiques (étudiants, enseignants, examens, taux de soumission)
- Identification des sujets en difficulté
- Recommandations pédagogiques pour l'établissement

---

### 7.5 Tableau de progression étudiant

**Accès** : Navbar étudiant → **Outils IA** → **Ma progression** | URL : `/student/progress`

- **3 compteurs** avec pourcentages (maîtrisées / en progression / à travailler)
- **Graphique doughnut** avec pourcentages dans le tooltip au survol
- **Barres de progression** avec score et nombre d'examens par sujet
- **Recommandations IA personnalisées** avec lecture vocale (Web Speech API)

**Vue enseignant** : Navbar → **Progression** | URL : `/teacher/students/progress`
- Vue globale de tous les étudiants avec mini-barres tricolores
- Bouton **Détail** pour voir la progression complète d'un étudiant

---

### 7.6 Relevé de notes (Étudiants)

**Accès** : Navbar → **Mes examens** → **Voir mes résultats** → bouton **Relevé de notes**  
URL directe : `/student/transcript`

Ouvre un nouvel onglet avec un document imprimable contenant :
- Photo de profil + nom, email, classe de l'étudiant
- Statistiques : examens passés, moyenne générale, réussis / à repasser
- Tableau de tous les examens soumis avec note et mention (Très bien / Bien / Passable…)
- Bouton **Imprimer / Enregistrer en PDF** (via impression navigateur)

---

### 7.7 Afficher/Masquer le mot de passe

Disponible sur les pages **Connexion** (`/login`) et **Créer un compte** (`/register`).  
Cliquer sur l'icône œil à droite du champ mot de passe pour basculer entre affiché/masqué.

---

### 7.8 Explications de cours par IA

**Accès** : Navbar étudiant → **Outils IA** → **Explications** | URL : `/ai/explain`

1. Saisir un sujet (ex : *"Les intégrales"*, *"Le protocole TCP/IP"*)
2. Choisir le niveau : Débutant / Intermédiaire / Avancé
3. L'IA génère une explication structurée en Markdown
4. Options : Écouter (synthèse vocale), Imprimer, Générer une fiche de révision

---

## 8. Résolution de problèmes

### Erreur "Variable 'route_name' does not exist"

Le cache Symfony contient une version périmée des routes.

```bash
php bin/console cache:clear
# Puis redémarrer le serveur Symfony
```

### Photo de profil ne s'affiche pas

Vérifier que le dossier `public/uploads/avatars/` existe et que le serveur peut y écrire.

```bash
mkdir -p public/uploads/avatars
```

### Les features IA ne répondent pas

1. Vérifier que `GROQ_API_KEY` est définie dans `.env.local`
2. Vérifier la connectivité internet (l'API Groq est externe)
3. Vérifier les logs : `tail -f var/log/dev.log`

### Migrations échouent

```bash
# Vérifier l'état
php bin/console doctrine:migrations:status

# En cas de conflit, forcer la version
php bin/console doctrine:migrations:version --add --all
php bin/console doctrine:migrations:migrate
```

### Le serveur affiche des pages en cache après mise à jour

```bash
rm -rf var/cache/dev
php bin/console cache:warmup
```
Puis faire **Ctrl+Shift+R** dans le navigateur (hard reload).

---

> Document généré pour EduTest v2AI — Avril 2026
