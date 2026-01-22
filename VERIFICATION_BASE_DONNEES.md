# Vérification de cohérence - Base de données

## 📋 Résumé de la vérification

Ce document liste les vérifications à effectuer pour s'assurer que la configuration Doctrine (entités PHP) est cohérente avec le schéma de la base de données en production.

---

## ✅ Configuration Doctrine actuelle

### Connexions configurées

1. **Connexion principale (`default`)** :
   - Variable d'environnement : `DATABASE_URL`
   - Charset : `utf8mb4`
   - Base de données : `demande_terrain` (ou celle définie dans `DATABASE_URL`)

2. **Connexion électeurs (`electeurs`)** :
   - Variable d'environnement : `ELECTEURS_DATABASE_URL`
   - Charset : `utf8mb4`
   - Base de données : `elections2` (ou celle définie dans `ELECTEURS_DATABASE_URL`)

### Entités mappées

Toutes les entités dans `src/Entity/` sont automatiquement mappées via `auto_mapping: true`.

---

## 📊 Tables principales identifiées

### Tables dans la base `demande_terrain` (connexion `default`)

| Table SQL | Entité PHP | Statut |
|-----------|------------|--------|
| `gs_mairie_users` | `App\Entity\User` | ✅ |
| `gs_mairie_demande_terrains` | `App\Entity\Request` | ✅ **CORRIGÉ** : Mapping mis à jour pour correspondre au nom SQL |
| `gs_mairie_localites` | `App\Entity\Localite` | ✅ |
| `gs_mairie_lotissements` | `App\Entity\Lotissement` | ✅ |
| `gs_mairie_lots` | `App\Entity\Lots` | ✅ |
| `gs_mairie_parcelle` | `App\Entity\Parcelle` | ✅ |
| `gs_mairie_attribuation_parcelle` | `App\Entity\AttributionParcelle` | ✅ |
| `gs_mairie_attribuation_historiques` | `App\Entity\AttributionParcelleStatusHistory` | ✅ |
| `gs_mairie_titre_fonciers` | `App\Entity\TitreFoncier` | ✅ |
| `gs_mairie_documents` | `App\Entity\DocumentGenere` | ✅ |
| `gs_mairie_signatures` | `App\Entity\Signature` | ✅ |
| `gs_mairie_reset_password_requests` | `App\Entity\ResetPasswordRequest` | ✅ |
| `gs_mairie_plan_lotissements` | `App\Entity\PlanLotissement` | ✅ |
| `gs_mairie_articles_terrains` | `App\Entity\Article` | ✅ |
| `gs_mairie_categories_terrains` | `App\Entity\CategorieArticle` | ✅ |
| `gs_mairie_images_article` | `App\Entity\ImageArticle` | ✅ |
| `gs_mairie_configurations` | `App\Entity\Configuration` | ✅ |
| `gs_mairie_audit_log` | `App\Entity\AuditLog` | ✅ |
| `contact_messages` | `App\Entity\ContactMessage` | ✅ |
| `doctrine_migration_versions` | Géré par Doctrine | ✅ |

### Tables supprimées / obsolètes

| Table SQL | Raison | Action |
|-----------|--------|--------|
| `refresh_tokens` | Refresh token désactivé | ⚠️ Peut rester en base (non utilisée) |

---

## 🔍 Vérifications à effectuer en production

### 1. Vérifier que toutes les tables existent

Sur le serveur, exécuter :

```bash
docker-compose exec mysql mysql -u gl_user -pKaolack@2025 demande_terrain -e "SHOW TABLES;"
```

**Résultat attendu** : Toutes les tables listées ci-dessus doivent être présentes.

---

### 2. Vérifier la cohérence du schéma Doctrine

Sur le serveur, exécuter :

```bash
docker-compose exec php php bin/console doctrine:schema:validate --env=prod
```

**Résultat attendu** :
- `[OK] The mapping files are correct.`
- `[OK] The database schema is in sync with the mapping files.`

Si des erreurs apparaissent, elles indiqueront les colonnes manquantes ou en trop.

---

### 3. Vérifier les migrations

Sur le serveur, exécuter :

```bash
docker-compose exec php php bin/console doctrine:migrations:status --env=prod
```

**Vérifier** :
- Toutes les migrations sont exécutées (`Executed` = `Yes`)
- Aucune migration en attente

---

### 4. Vérifier les colonnes critiques

#### Table `gs_mairie_users`

Colonnes obligatoires :
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `username` (VARCHAR(180), UNIQUE)
- `email` (VARCHAR(255), UNIQUE, nullable)
- `password` (VARCHAR(255))
- `roles` (JSON ou TEXT)
- `date_naissance` (DATE, NOT NULL)
- `enabled` (BOOLEAN, nullable)
- `activeted` (BOOLEAN, nullable)

#### Table `gs_mairie_demandes`

Colonnes obligatoires :
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `type_demande` (VARCHAR)
- `statut` (VARCHAR)
- `date_creation` (DATETIME)
- `utilisateur_id` (INT, FOREIGN KEY vers `gs_mairie_users.id`)

#### Table `gs_mairie_audit_log`

Colonnes obligatoires :
- `id` (BIGINT, PRIMARY KEY, AUTO_INCREMENT)
- `created_at` (DATETIME, NOT NULL)
- `event` (VARCHAR(100), NOT NULL)
- Index sur `created_at`, `actor_id`, `event`, `entity_class`, `entity_id`, `request_id`

---

## ⚠️ Points d'attention

### 1. Refresh Token (désactivé)

- ✅ Le bundle `gesdinet/jwt-refresh-token-bundle` a été retiré de `config/bundles.php`
- ✅ L'entité `App\Entity\RefreshToken` a été supprimée
- ✅ Le subscriber `JwtLoginSuccessSubscriber` a été supprimé
- ⚠️ La table `refresh_tokens` peut encore exister en base (non bloquant, mais peut être supprimée si souhaité)

**Action optionnelle** (si tu veux nettoyer complètement) :
```sql
DROP TABLE IF EXISTS refresh_tokens;
```

---

### 2. Charset et collation

Toutes les tables doivent utiliser :
- Charset : `utf8mb4`
- Collation : `utf8mb4_unicode_ci`

**Vérification** :
```sql
SELECT TABLE_NAME, TABLE_COLLATION 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'demande_terrain' 
AND TABLE_COLLATION != 'utf8mb4_unicode_ci';
```

---

### 3. Index manquants

Vérifier que les index définis dans les entités existent en base :

**Exemple pour `AuditLog`** :
```sql
SHOW INDEX FROM gs_mairie_audit_log;
```

Index attendus :
- `idx_auditlog_created` sur `created_at`
- `idx_auditlog_actor` sur `actor_id`
- `idx_auditlog_event` sur `event`
- `idx_auditlog_entity` sur `entity_class`, `entity_id`
- `idx_auditlog_request` sur `request_id`

---

## 🔧 Commandes de diagnostic

### Lister toutes les tables

```bash
docker-compose exec mysql mysql -u gl_user -pKaolack@2025 demande_terrain -e "SHOW TABLES;"
```

### Vérifier la structure d'une table

```bash
docker-compose exec mysql mysql -u gl_user -pKaolack@2025 demande_terrain -e "DESCRIBE gs_mairie_users;"
```

### Compter les enregistrements

```bash
docker-compose exec mysql mysql -u gl_user -pKaolack@2025 demande_terrain -e "SELECT COUNT(*) FROM gs_mairie_users;"
```

### Vérifier les clés étrangères

```bash
docker-compose exec mysql mysql -u gl_user -pKaolack@2025 demande_terrain -e "SELECT TABLE_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'demande_terrain' AND REFERENCED_TABLE_NAME IS NOT NULL;"
```

---

## ✅ Checklist de vérification

- [ ] Toutes les tables listées existent en base
- [ ] `doctrine:schema:validate` retourne `[OK]`
- [ ] Toutes les migrations sont exécutées
- [ ] Charset `utf8mb4_unicode_ci` sur toutes les tables
- [ ] Index définis dans les entités existent en base
- [ ] Clés étrangères correctement configurées
- [ ] Table `refresh_tokens` supprimée (optionnel)

---

## 🚨 En cas d'incohérence détectée

1. **Identifier le problème** : colonne manquante, type incorrect, index manquant, etc.

2. **Créer une migration** :
   ```bash
   php bin/console doctrine:migrations:diff
   ```

3. **Vérifier la migration générée** dans `migrations/VersionXXXXXX.php`

4. **Appliquer en production** :
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction --env=prod
   ```

5. **Re-vérifier** avec `doctrine:schema:validate`

---

## 📝 Notes

- La base de données `elections2` (connexion `electeurs`) est utilisée uniquement pour la lecture des données électorales, pas pour les entités Doctrine principales.
- Les migrations Doctrine sont stockées dans `migrations/` et suivies dans la table `doctrine_migration_versions`.
