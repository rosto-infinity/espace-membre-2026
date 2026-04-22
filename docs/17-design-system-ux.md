# 17 — Design Système et UX (User Experience)

Un code propre est essentiel, mais une interface agréable est ce qui rend votre application vivante. Dans ce projet, nous avons adopté un design **minimaliste, moderne et inspiré de Vue.js**.

---

## 1. Identité Visuelle (Palette de couleurs)

Nous utilisons des variables CSS pour garantir la cohérence sur toutes les pages.

```css
:root {
    --vue-green: #42b883;       /* Couleur principale d'action */
    --vue-green-dark: #35495e;  /* Couleur de texte sombre */
    --light-grey: #f1f5f9;      /* Couleur de fond */
    --error-red: #ef4444;       /* Alertes erreurs */
}
```

---

## 2. Composants Premium

### La Carte (`.card`)
C'est le conteneur principal. Elle a des bords arrondis, une ombre légère et un fond blanc pur pour se détacher du fond gris clair.

### Les Boutons (`.btn`)
Les boutons changent de couleur au survol (`hover`) et ont une légère animation de montée (`translateY`) pour donner une impression d'interactivité.

```css
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(66, 184, 131, 0.3);
}
```

---

## 3. Ergonomie et Navigation

### Indicateur de page active
Dans le header, le lien correspondant à la page actuelle est souligné. Cela aide l'utilisateur à savoir où il se trouve.

### Barre latérale (Sidebar)
Pour le dashboard, nous utilisons une sidebar à gauche. C'est un standard de l'industrie pour les applications de gestion, car cela libère de l'espace central pour les données.

---

## 4. Design Adaptatif (Responsive)

L'application doit fonctionner sur mobile. Nous utilisons :

- **Flexbox** pour aligner les éléments de navigation.
- **CSS Grid** pour les colonnes du dashboard.
- **Media Queries** (optionnel) pour masquer certains éléments sur petits écrans.

---

## 5. Micro-interactions (Feedbacks)

C'est le "petit plus" qui fait la différence :
- Les messages flash qui apparaissent doucement.
- Les champs de formulaire qui s'illuminent en vert quand on clique dessus (`:focus`).
- L'avatar par défaut qui utilise l'initiale de l'utilisateur.

---

## Pourquoi est-ce crucial pour un développeur junior ?

Les employeurs et les clients ne voient pas votre base de données en premier. Ils voient votre interface. Un design soigné montre que :
- Vous êtes **rigoureux**.
- Vous vous souciez de l'**utilisateur final**.
- Vous connaissez les **standards modernes du web**.

---

> 🎨 **Conseil** : Ne surchargez pas vos pages de couleurs. Utilisez une seule couleur "vibrante" (comme notre vert) pour les actions importantes, et restez neutre (gris, blanc, noir) pour le reste.
