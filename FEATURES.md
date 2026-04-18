# Documentation des nouvelles fonctionnalités IA — EduTest

> Version : branch `v2AI` — Avril 2026

---

## Sommaire

0. [Installation & mise en route](#0-installation--mise-en-route)
1. [Explications de cours par IA](#1-explications-de-cours-par-ia)
2. [Tableau de progression](#2-tableau-de-progression)
3. [Recommandations personnalisées de révision](#3-recommandations-personnalisées-de-révision)
4. [Accès aux fonctionnalités](#4-accès-aux-fonctionnalités)
5. [Architecture technique](#5-architecture-technique)

---

## 0. Installation & mise en route

### Prérequis
- PHP 8.2+
- Composer
- MySQL 8+
- Symfony CLI (recommandé) ou serveur PHP intégré
- Une clé API Groq valide → [console.groq.com](https://console.groq.com)

---

### Étape 1 — Récupérer la branche

```bash
git fetch origin
git checkout v2AI
git pull origin v2AI
```

---

### Étape 2 — Installer les dépendances

```bash
composer install
```

---

### Étape 3 — Configurer les variables d'environnement

Créer (ou compléter) le fichier `.env.local` à la racine du projet :

```env
# Clé API Groq (obligatoire pour les features IA)
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Base de données MySQL
DATABASE_URL="mysql://root:@127.0.0.1:3306/edutest_db?serverVersion=8.0.32&charset=utf8mb4"
```

> **Ne pas mettre la clé dans `.env`** — ce fichier est commité. Utilisez uniquement `.env.local` (ignoré par git).

---

### Étape 4 — Créer/mettre à jour la base de données

```bash
# Créer la base si elle n'existe pas encore
php bin/console doctrine:database:create

# Appliquer toutes les migrations
php bin/console doctrine:migrations:migrate
```

> Aucune nouvelle migration n'est requise pour les features IA de cette branche — tout est calculé depuis les données existantes.

---

### Étape 5 — Vider le cache

```bash
php bin/console cache:clear
```

---

### Étape 6 — Lancer l'application

```bash
# Avec la CLI Symfony (recommandé)
symfony server:start

# Ou avec PHP intégré
php -S localhost:8000 -t public/
```

L'application est accessible sur **http://localhost:8000**

---

## 1. Explications de cours par IA

### Description
Permet à un étudiant d'obtenir une explication pédagogique détaillée sur n'importe quel sujet, adaptée à son niveau.

### Accès
- **Navbar étudiant** → bouton **Explications**
- **URL** : `/ai/explain`

### Utilisation

1. Saisir le sujet dans le champ texte (ex : *"La photosynthèse"*, *"Les intégrales"*, *"Le droit des contrats"*)
2. Choisir le niveau dans le sélecteur :
   - **Débutant** — vocabulaire simple, analogies du quotidien
   - **Intermédiaire** — équilibre entre accessibilité et précision
   - **Avancé** — terminologie technique, nuances approfondies
3. Cliquer sur **Expliquer**
4. L'IA génère une explication structurée en Markdown avec :
   - Introduction accessible
   - Concepts clés avec analogies
   - Exemples concrets
   - Erreurs courantes à éviter
   - Points essentiels à retenir

### Options disponibles sur le résultat
| Bouton | Action |
|--------|--------|
| Imprimer | Ouvre la boîte de dialogue d'impression |
| Écouter | Lecture audio via synthèse vocale (français) |
| Stop | Arrête la lecture audio |
| Générer une fiche de révision | Redirige vers `/ai/revision` avec le même sujet pré-rempli |

### Notes
- Les réponses sont mises en cache 1 heure côté serveur (même prompt = réponse instantanée)
- La synthèse vocale utilise l'API Web Speech intégrée au navigateur (pas de serveur)

---

## 2. Tableau de progression

### Description
Affiche la progression d'un étudiant par compétence (sujet d'examen), classée en trois catégories selon le score moyen obtenu.

### Catégories

| Catégorie | Seuil | Couleur |
|-----------|-------|---------|
| Maîtrisée | Score moyen ≥ 75% | Vert |
| En progression | Score moyen 50–74% | Orange |
| À travailler | Score moyen < 50% | Rouge |

> Le score est calculé automatiquement à partir de tous les examens **soumis** par l'étudiant, groupés par titre d'examen.

---

### Vue étudiant

**Accès** : Navbar → **Ma progression** | URL : `/student/progress`

**Contenu affiché :**
- **3 compteurs** en haut (maîtrisées / en progression / à travailler)
- **Graphique doughnut** (répartition visuelle des compétences)
- **Barres de progression** colorées par catégorie, avec le score et le nombre d'examens passés
- **Bouton "Réviser"** sur chaque compétence à travailler → redirige vers l'assistant de révision IA
- **Recommandations IA personnalisées** en bas de page (voir section 3)

---

### Vue enseignant

**Accès** : Navbar → **Progression** | URL : `/teacher/students/progress`

**Contenu affiché :**
- Statistiques globales (total maîtrisées / en progression / à travailler sur tous les étudiants)
- Tableau de tous les étudiants avec :
  - Compteurs par catégorie
  - Mini-barre tricolore de répartition
  - Bouton **Détail** pour voir la fiche complète d'un étudiant

**Vue détaillée par étudiant** : `/teacher/students/{id}/progress`
- Même affichage que la vue étudiant (sans les recommandations IA)
- Accessible depuis le bouton Détail dans le tableau enseignant

---

## 3. Recommandations personnalisées de révision

### Description
L'IA analyse le profil de progression de l'étudiant et génère un plan de révision sur mesure, directement affiché en bas de la page **Ma progression**.

### Contenu généré par l'IA
1. **Diagnostic** — résumé du profil de l'étudiant
2. **Priorités de révision** — conseils concrets pour les domaines faibles
3. **Stratégies de progression** — comment progresser sur les domaines intermédiaires
4. **Consolidation des points forts** — comment maintenir les acquis
5. **Plan hebdomadaire suggéré** — organisation concrète du temps de révision

### Déclenchement
Les recommandations sont générées automatiquement à chaque visite de `/student/progress`, à partir des données réelles de progression. Si l'étudiant n'a pas encore soumis d'examens, aucune recommandation n'est affichée.

---

## 4. Accès aux fonctionnalités

### Étudiant

| Fonctionnalité | Accès navbar | URL directe |
|----------------|-------------|-------------|
| Explication de cours | Bouton **Explications** | `/ai/explain` |
| Ma progression | Bouton **Ma progression** | `/student/progress` |
| Révision IA (existant) | Bouton **Révision** | `/ai/revision` |

### Enseignant

| Fonctionnalité | Accès navbar | URL directe |
|----------------|-------------|-------------|
| Progression des étudiants | Bouton **Progression** | `/teacher/students/progress` |
| Détail d'un étudiant | Via tableau progression | `/teacher/students/{id}/progress` |

---

## 5. Architecture technique

### Fichiers créés

```
src/
├── Service/
│   └── ProgressService.php          # Calcul de progression par sujet
├── Controller/
│   └── ProgressController.php       # Routes /student/progress et /teacher/students/...
templates/
├── ollama/
│   └── explain.html.twig            # Page explication de cours
├── progress/
│   ├── student.html.twig            # Dashboard progression étudiant
│   ├── teacher_overview.html.twig   # Vue globale enseignant
│   └── teacher_student_detail.html.twig  # Détail par étudiant
```

### Fichiers modifiés

```
src/Service/GroqService.php              # +2 méthodes IA
src/Controller/OllamaController.php      # +1 route /ai/explain
templates/_partials/navbar.html.twig     # Liens Explications, Ma progression, Progression
templates/exam_pass/list.html.twig       # Bouton Voir mes résultats (nettoyage)
```

### Nouvelles méthodes GroqService

| Méthode | Description | Cache |
|---------|-------------|-------|
| `generateLessonExplanation(topic, level)` | Explication pédagogique structurée | 1h |
| `generatePersonalizedRecommendations(weak, inProgress, strong)` | Plan de révision personnalisé | 1h |

### Logique de calcul de progression (`ProgressService`)

```
Pour chaque examen SUBMITTED de l'étudiant :
  score (%) = (finalGrade / 20) × 100
  groupement par → exam.title (= sujet/compétence)

Score moyen par sujet :
  ≥ 75%  → mastered   (maîtrisée)
  50–74% → inProgress (en progression)
  < 50%  → toWork     (à travailler)
```

### Dépendances frontend
- **Chart.js 4** (CDN) — graphique doughnut sur la page progression étudiant
- **marked.js** (CDN) — rendu Markdown des explications et recommandations
- **Bootstrap Icons** — icônes des boutons et badges
