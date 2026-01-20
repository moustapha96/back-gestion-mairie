# Corrections du système de Refresh Token

## ✅ Corrections apportées

### 1. **Subscriber JwtLoginSuccessSubscriber** - Améliorations

#### Avant
- Supprimait tous les tokens de l'utilisateur (même valides)
- Ne vérifiait pas le type de l'entité retournée
- Gestion d'erreur basique

#### Après
- ✅ **Suppression intelligente** : Supprime uniquement les tokens expirés ou invalides
- ✅ **Vérification de type** : Vérifie que l'entité retournée est bien `App\Entity\RefreshToken`
- ✅ **Gestion d'erreur améliorée** : Log des erreurs en mode développement
- ✅ **Création garantie** : Force la définition de `created_at` avant la sauvegarde

### 2. **Entité RefreshToken** - Améliorations

#### Avant
- `created_at` était nullable
- Seul `PrePersist` était utilisé

#### Après
- ✅ **Non nullable** : `created_at` n'est plus nullable pour garantir une valeur
- ✅ **PreUpdate ajouté** : Protège contre les modifications accidentelles
- ✅ **Initialisation garantie** : Le constructeur initialise toujours `created_at`

### 3. **Configuration services.yaml** - Priorités

- ✅ **Priorité configurée** : Le subscriber s'exécute avec une priorité de -10
- ✅ **Ordre d'exécution** : Le listener qui ajoute les données utilisateur s'exécute en premier (priorité 0)

## 🔧 Fonctionnement

### Lors de la connexion (`/api/login`)

1. **Authentification réussie** → Token JWT généré
2. **Listener AuthenticationSuccessListener** → Ajoute les données utilisateur à la réponse
3. **Subscriber JwtLoginSuccessSubscriber** → 
   - Supprime les tokens expirés de l'utilisateur
   - Crée un nouveau refresh token
   - Définit `created_at` explicitement
   - Sauvegarde via le RefreshTokenManager
   - Ajoute le refresh token à la réponse

### Lors du refresh (`/api/token/refresh`)

1. **Requête POST** avec `refresh_token` dans le body
2. **Bundle Gesdinet** → Valide le refresh token
3. **Nouveau token JWT** → Généré et retourné
4. **Nouveau refresh token** → Créé automatiquement par le bundle

## 📊 Avantages des corrections

### Sécurité
- ✅ Suppression uniquement des tokens expirés (permet plusieurs sessions)
- ✅ Vérification de type pour éviter les erreurs
- ✅ Gestion d'erreur sans exposer de détails sensibles

### Performance
- ✅ Suppression ciblée (seulement les tokens expirés)
- ✅ Pas de suppression inutile de tokens valides

### Fiabilité
- ✅ `created_at` toujours défini (triple sécurité : constructeur, PrePersist, setter explicite)
- ✅ Gestion d'erreur robuste
- ✅ Logs en développement pour le débogage

## 🧪 Tests recommandés

### Test 1 : Connexion
```bash
POST /api/login
{
  "email": "user@example.com",
  "password": "password"
}
```

**Résultat attendu** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "a1b2c3d4e5f6...",
  "user": { ... }
}
```

### Test 2 : Refresh token
```bash
POST /api/token/refresh
{
  "refresh_token": "a1b2c3d4e5f6..."
}
```

**Résultat attendu** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "nouveau_token..."
}
```

### Test 3 : Vérification base de données
```sql
SELECT * FROM refresh_tokens WHERE username = 'user@example.com';
```

**Vérifier** :
- ✅ `created_at` est défini
- ✅ `valid` est dans le futur (30 jours)
- ✅ `refresh_token` est unique

## 🔍 Dépannage

### Le refresh token n'est pas créé

1. Vérifier les logs :
```bash
tail -f var/log/dev.log | grep refresh
```

2. Vérifier que le subscriber est bien enregistré :
```bash
php bin/console debug:event-dispatcher lexik_jwt_authentication.on_authentication_success
```

3. Vérifier la configuration :
```bash
php bin/console debug:config gesdinet_jwt_refresh_token
```

### Erreur "Field 'created_at' doesn't have a default value"

1. Vérifier que la migration est appliquée :
```bash
php bin/console doctrine:migrations:status
```

2. Vérifier la structure de la table :
```sql
DESCRIBE refresh_tokens;
```

3. Si nécessaire, appliquer la migration :
```bash
php bin/console doctrine:migrations:migrate
```

## 📝 Notes importantes

- **Production** : Les erreurs sont silencieuses pour ne pas perturber l'utilisateur
- **Développement** : Les erreurs sont loggées pour faciliter le débogage
- **Multiple sessions** : Les tokens valides ne sont pas supprimés, permettant plusieurs sessions simultanées
- **Expiration** : Les tokens expirés sont automatiquement supprimés lors de la création d'un nouveau token
