# EduTest v3 — Guide des nouvelles fonctionnalités

> Branche : `v3` — Roadmap Avril 2026  
> Prérequis : avoir installé et configuré v2AI ([voir INSTALL_v2AI.md](INSTALL_v2AI.md))

---

## Sommaire

1. [Génération de QCM par IA (Enseignant)](#1-génération-de-qcm-par-ia-enseignant)
2. [Correction des réponses ouvertes par IA](#2-correction-des-réponses-ouvertes-par-ia)
3. [Notifications en temps réel](#3-notifications-en-temps-réel)
4. [Badges & Gamification (Étudiant)](#4-badges--gamification-étudiant)
5. [Mode examen sécurisé (Plein écran)](#5-mode-examen-sécurisé-plein-écran)
6. [Tableau de bord étudiant amélioré](#6-tableau-de-bord-étudiant-amélioré)
7. [Export PDF des examens](#7-export-pdf-des-examens)
8. [Installation & migration v3](#8-installation--migration-v3)

---

## 1. Génération de QCM par IA (Enseignant)

### Description
Un enseignant peut générer automatiquement des questions QCM à partir d'un sujet et d'un niveau de difficulté. Les questions générées sont directement intégrées à un examen existant ou nouveau.

### Accès
- Lors de la création/édition d'un examen → bouton **Générer des questions avec l'IA**
- URL : `/exam/{id}/ai-generate`

### Utilisation

1. Ouvrir un examen en création ou en édition
2. Cliquer sur **Générer des questions avec l'IA**
3. Remplir le formulaire :
   - **Sujet** : ex. *"Les réseaux TCP/IP"*, *"Le droit des obligations"*
   - **Nombre de questions** : 3 à 20
   - **Difficulté** : Facile / Moyen / Difficile
4. Cliquer sur **Générer**
5. L'IA propose les questions avec leurs choix et la bonne réponse pré-cochée
6. L'enseignant peut modifier, supprimer ou valider chaque question avant d'ajouter

### Format généré par l'IA
```
Question : Quel protocole opère au niveau de la couche transport du modèle OSI ?
Choix A : HTTP       ← incorrect
Choix B : TCP        ← correct ✓
Choix C : ARP        ← incorrect
Choix D : Ethernet   ← incorrect
```

### Notes techniques
- Appelle `GroqService::generateQcmQuestions(topic, count, difficulty)`
- Les questions sont parsées depuis le JSON retourné par l'IA
- Un aperçu interactif permet de modifier avant validation

---

## 2. Correction des réponses ouvertes par IA

### Description
Pour les questions de type **texte libre**, l'IA analyse la réponse de l'étudiant et attribue automatiquement une note partielle, accompagnée d'un commentaire pédagogique.

### Accès
- Automatique lors de la soumission d'un examen contenant des questions ouvertes
- L'enseignant peut consulter et corriger manuellement depuis : `/teacher/exam/{id}/corrections`

### Fonctionnement

| Étape | Qui | Action |
|-------|-----|--------|
| Soumission | Étudiant | Répond à la question ouverte |
| Correction auto | IA | Note de 0 à max_points + commentaire |
| Révision | Enseignant | Peut ajuster la note et le commentaire |
| Finalisation | Enseignant | Valide → note définitive calculée |

### Vue enseignant — Page de correction
- Liste de toutes les réponses ouvertes avec la proposition de note de l'IA
- Champ modifiable pour ajuster la note
- Champ commentaire pour laisser un feedback à l'étudiant
- Bouton **Tout valider** pour accepter toutes les propositions IA

### Vue étudiant — Résultat détaillé
- Pour chaque réponse ouverte : note obtenue + commentaire de l'enseignant (ou de l'IA)

---

## 3. Notifications en temps réel

### Description
Les utilisateurs reçoivent des notifications dans l'application pour les événements importants.

### Déclencheurs

| Événement | Destinataire | Message |
|-----------|-------------|---------|
| Examen affecté | Étudiant | *"Un nouvel examen vous a été assigné : {titre}"* |
| Examen soumis | Enseignant | *"{étudiant} a rendu sa copie pour {examen}"* |
| Note disponible | Étudiant | *"Votre note pour {examen} est disponible : {note}/20"* |
| Compte approuvé | Étudiant/Enseignant | *"Votre compte a été approuvé. Bienvenue !"* |

### Interface
- **Cloche** dans la navbar avec badge rouge indiquant le nombre de non-lues
- **Dropdown** listant les 5 dernières notifications au clic
- **Page complète** `/notifications` pour tout l'historique
- Notifications marquées comme lues automatiquement à l'ouverture

### Notes techniques
- Nouvelle entité `Notification` (destinataire, message, type, lu, createdAt)
- Polling toutes les 30 secondes via fetch (`/api/notifications/unread-count`)
- Ou implémentation WebSocket/Mercure pour temps réel natif

---

## 4. Badges & Gamification (Étudiant)

### Description
Les étudiants débloquent des badges selon leurs performances et leur activité sur la plateforme.

### Liste des badges

| Badge | Icône | Condition de déblocage |
|-------|-------|----------------------|
| Premier pas | 🎯 | Soumettre son premier examen |
| Série parfaite | ⭐ | Obtenir 20/20 sur un examen |
| Assidu | 📅 | Soumettre 5 examens |
| Expert | 🏆 | Avoir ≥ 3 compétences maîtrisées |
| Curieux | 🤖 | Utiliser l'IA 10 fois |
| En progrès | 📈 | Passer une compétence de "à travailler" à "maîtrisée" |
| Perfectionniste | 💎 | Moyenne générale ≥ 16/20 |

### Accès
- **Profil étudiant** → section Badges | URL : `/profile#badges`
- Notification automatique lors du déblocage d'un badge

### Notes techniques
- Nouvelle entité `Badge` et `UserBadge` (ManyToMany avec User)
- Service `BadgeService::checkAndAward(User $student)` appelé après chaque soumission
- Les badges non débloqués s'affichent en grisé sur le profil

---

## 5. Mode examen sécurisé (Plein écran)

### Description
Renforcement du dispositif anti-triche existant avec un mode plein écran forcé pendant la passation d'examen.

### Fonctionnement

1. Au démarrage de l'examen → demande d'activation du mode plein écran (API Fullscreen)
2. Si l'étudiant quitte le plein écran → avertissement immédiat + compteur incrémenté
3. Au bout de **3 sorties** → soumission automatique de la copie
4. Détection combinée avec le système anti-triche existant (onglets, copier-coller)

### Tableau de bord enseignant — colonne ajoutée

| Étudiant | Examen | Onglet | Copie | Plein écran | Statut |
|----------|--------|--------|-------|-------------|--------|
| Dupont   | SQL    | 0      | 0     | 1 sortie    | OK |
| Martin   | SQL    | 3      | 2     | 5 sorties   | Suspect 🚩 |

### Configuration par examen
L'enseignant peut activer/désactiver le mode plein écran obligatoire lors de la création de l'examen.

---

## 6. Tableau de bord étudiant amélioré

### Description
Refonte de la page **Mes examens** avec un vrai tableau de bord visuel.

### Contenu du nouveau dashboard

**Bloc 1 — Résumé personnel**
- Moyenne générale avec jauge colorée
- Nombre d'examens passés / en attente
- Badge de niveau (Débutant / Intermédiaire / Avancé) calculé depuis la progression

**Bloc 2 — Examens à venir**
- Carte pour chaque examen assigné avec statut, date limite, durée
- Bouton **Commencer** si dans la fenêtre de passage

**Bloc 3 — Derniers résultats**
- Les 3 derniers examens soumis avec note et mention
- Lien rapide vers le détail

**Bloc 4 — Objectif IA du jour**
- L'IA suggère un sujet à réviser en priorité selon la progression

### Accès
- URL : `/student/dashboard` (remplace `/student/exams` comme page principale)

---

## 7. Export PDF des examens

### Description
Génération d'un PDF complet d'un examen pour archivage papier ou distribution hors ligne.

### Types d'export disponibles

| Export | Contenu | Accès |
|--------|---------|-------|
| Sujet vierge | Questions sans les bonnes réponses | Enseignant → `/exam/{id}/export/pdf` |
| Corrigé type | Questions + bonnes réponses | Enseignant → `/exam/{id}/export/pdf?mode=correction` |
| Copie étudiant | Réponses + note + commentaires | Étudiant → `/student/result/{id}/pdf` |

### Notes techniques
- Utilise la librairie **mPDF** (déjà dans `composer.json`)
- Template Twig dédié avec CSS `@media print` optimisé
- Générés à la volée, pas de stockage sur le serveur

---

## 8. Installation & migration v3

```bash
# 1. Passer sur la branche v3
git fetch origin
git checkout v3
git pull origin v3

# 2. Mettre à jour les dépendances
composer install

# 3. Appliquer les nouvelles migrations
php bin/console doctrine:migrations:migrate
# Nouvelles tables : notification, badge, user_badge
# Nouvelles colonnes : exam.fullscreen_required, answer.ai_grade, answer.ai_comment

# 4. Vider le cache
php bin/console cache:clear

# 5. Relancer le serveur
symfony server:start
```

### Nouvelles variables `.env.local` (optionnelles)

```env
# Pour les notifications temps réel via Mercure (optionnel)
MERCURE_URL=https://example.com/.well-known/mercure
MERCURE_PUBLIC_URL=https://example.com/.well-known/mercure
MERCURE_JWT_SECRET=changez_cette_valeur
```

### Nouvelles entités créées

```
src/Entity/
├── Notification.php         # Notifications in-app
├── Badge.php                # Définition des badges
└── UserBadge.php            # Badges débloqués par étudiant

src/Service/
├── BadgeService.php         # Logique de déblocage des badges
├── NotificationService.php  # Création et lecture des notifications
└── AiGraderService.php      # Correction IA des réponses ouvertes

src/Controller/
├── NotificationController.php    # /notifications, /api/notifications/...
├── BadgeController.php           # /profile/badges
└── AiQuestionController.php      # /exam/{id}/ai-generate
```

---

> Document de roadmap EduTest v3 — Avril 2026  
> Les fonctionnalités sont indicatives et peuvent évoluer selon les priorités de développement.
