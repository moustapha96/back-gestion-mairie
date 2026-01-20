# Correction de la configuration Nginx

## Problèmes corrigés

1. ✅ **Directive `http2` dépréciée** : Utilisation de `http2 on;` séparément au lieu de `listen 443 ssl http2;`
2. ✅ **Directives SSL en double** : Retrait de `ssl_protocols` et `ssl_prefer_server_ciphers` car déjà dans `options-ssl-nginx.conf`

## Application des corrections

### 1. Copier la nouvelle configuration

```bash
sudo cp nginx/backendgl.kaolackcommune.sn.conf /etc/nginx/sites-available/backendgl.kaolackcommune.sn
```

### 2. Si le lien symbolique existe déjà, le supprimer puis le recréer

```bash
# Supprimer l'ancien lien
sudo rm /etc/nginx/sites-enabled/backendgl.kaolackcommune.sn

# Créer le nouveau lien
sudo ln -s /etc/nginx/sites-available/backendgl.kaolackcommune.sn /etc/nginx/sites-enabled/
```

### 3. Tester la configuration

```bash
sudo nginx -t
```

Vous devriez voir :
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

### 4. Recharger Nginx

```bash
sudo systemctl reload nginx
```

## Changements effectués

### Avant (avec erreurs)
```nginx
listen 443 ssl http2;  # ❌ Déprécié
ssl_protocols TLSv1.2 TLSv1.3;  # ❌ Déjà dans options-ssl-nginx.conf
ssl_prefer_server_ciphers on;  # ❌ Déjà dans options-ssl-nginx.conf
```

### Après (corrigé)
```nginx
listen 443 ssl;  # ✅ Syntaxe correcte
listen [::]:443 ssl;
http2 on;  # ✅ Directive séparée
# ssl_protocols et ssl_prefer_server_ciphers retirés car dans options-ssl-nginx.conf
```

## Vérification

Après le rechargement, vérifiez que tout fonctionne :

```bash
# Vérifier le statut de Nginx
sudo systemctl status nginx

# Vérifier les logs
sudo tail -f /var/log/nginx/backendgl.error.log
```
