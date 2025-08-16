# 🚀 Guida Installazione Completa - WhatsApp SaaS Plugin

**Guida step-by-step per l'installazione e configurazione del plugin WordPress e workflow n8n**

---

## 📋 **Prerequisiti**

### **Server Requirements**
```bash
✅ WordPress 5.0+ (testato fino a 6.3+)
✅ PHP 7.4+ (raccomandato PHP 8.1+)
✅ MySQL 5.7+ / MariaDB 10.2+
✅ mod_rewrite abilitato (per API REST)
✅ SSL Certificate (HTTPS obbligatorio per webhook)
✅ Memoria PHP: minimo 256MB (raccomandato 512MB)
```

### **Servizi Esterni**
```bash
✅ Account Mail2Wa.it (per invio WhatsApp)
✅ n8n instance (cloud o self-hosted)
✅ Account Gmail con OAuth (per estrazione email)
✅ Google Sheets (opzionale, per logging)
```

---

## 🎯 **Fase 1: Installazione Plugin WordPress**

### **Metodo 1: Upload da Admin (Raccomandato)**

1. **Download Plugin**
   ```bash
   # Scarica l'ultima release dal repository
   wget https://github.com/username/whatsapp-saas-plugin/archive/main.zip
   ```

2. **Upload in WordPress**
   ```bash
   WordPress Admin > Plugin > Aggiungi nuovo > Carica plugin
   Seleziona: whatsapp-saas-plugin.zip
   Clicca: Installa ora > Attiva plugin
   ```

3. **Verifica Installazione**
   ```bash
   # Controlla menu WordPress admin
   Dashboard > WhatsApp SaaS (dovrebbe apparire nel menu)
   ```

### **Metodo 2: FTP Upload**

```bash
# 1. Extract plugin
unzip whatsapp-saas-plugin.zip

# 2. Upload via FTP
cd /wp-content/plugins/
rsync -av whatsapp-saas-plugin/ ./whatsapp-saas-plugin/

# 3. Set permissions
chmod -R 755 whatsapp-saas-plugin/
chown -R www-data:www-data whatsapp-saas-plugin/

# 4. Activate in WordPress Admin
WordPress Admin > Plugin > Attiva "WhatsApp SaaS Plugin"
```

### **Metodo 3: WP-CLI**

```bash
# Download e installa
wp plugin install whatsapp-saas-plugin.zip
wp plugin activate whatsapp-saas-plugin

# Verifica attivazione
wp plugin list --status=active
```

---

## ⚙️ **Fase 2: Configurazione Base Plugin**

### **1. Accedi alle Impostazioni**
```bash
WordPress Admin > WhatsApp SaaS > Impostazioni
```

### **2. Configurazione API**
```php
// Impostazioni base richieste
API Key Plugin: demo-api-key-9lz721sv0xTjFNVA
Mail2Wa API Key: [Ottieni da mail2wa.it]
Mail2Wa Webhook URL: https://api.mail2wa.it/webhook/send

// URL Callback per n8n
WordPress API URL: https://tuosito.com/wp-json/wsp/v1/extract
```

### **3. Configurazione Messaggi**
```php
// Template messaggio benvenuto
Messaggio Default: "🎉 Ciao {nome}! Il tuo numero {numero} è stato registrato con successo."

// Impostazioni invio
Delay tra messaggi: 2 secondi (per evitare rate limiting)
Tentativi fallimenti: 3
Timeout API: 30 secondi
```

### **4. Configurazione Crediti**
```php
// Piani predefiniti (modificabili)
Starter: 500 crediti - €29.99
Professional: 2000 crediti - €99.99
Enterprise: 5000 crediti - €199.99
Unlimited: 25000 crediti - €499.99

// Impostazioni automatiche
Soglia alert: 100 crediti
Ricarica automatica: Attivata
Email alert: admin@tuosito.com
```

---

## 🔧 **Fase 3: Setup Account Mail2Wa.it**

### **1. Registrazione Account**
```bash
1. Visita https://mail2wa.it
2. Crea account business
3. Verifica email
4. Completa profilo azienda
```

### **2. Configurazione WhatsApp Business**
```bash
1. Connetti numero WhatsApp Business
2. Verifica numero con SMS/chiamata
3. Abilita API access
4. Genera API Key
```

### **3. Ottenimento API Key**
```bash
# Location API Key in Mail2Wa dashboard
Dashboard > API Settings > Generate Key
Copia: wml2wa_live_abc123xyz789...

# Testa connessione API
curl -X POST https://api.mail2wa.it/v1/test \
  -H "Authorization: Bearer wml2wa_live_abc123xyz789" \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

---

## 🤖 **Fase 4: Setup n8n Workflow**

### **1. Prerequisiti n8n**
```bash
# n8n Cloud (raccomandato)
https://app.n8n.cloud - Account Pro/Team per Gmail OAuth

# n8n Self-Hosted
Docker: docker run -it --rm --name n8n -p 5678:5678 n8nio/n8n
NPM: npm install n8n -g && n8n start
```

### **2. Import Workflow**
```bash
1. Scarica: n8n-whatsapp-workflow.json
2. n8n Dashboard > Import from file
3. Seleziona file JSON
4. Clicca Import
```

### **3. Configurazione Gmail OAuth**
```bash
# 1. Google Cloud Console
1. Vai a https://console.cloud.google.com
2. Crea nuovo progetto o seleziona esistente
3. Abilita Gmail API
4. Crea credenziali OAuth 2.0
5. Scarica client_secret.json

# 2. n8n Credentials
1. n8n > Credentials > Add Credential
2. Seleziona: Gmail OAuth2 API
3. Inserisci Client ID e Client Secret
4. Autorizza accesso Gmail
5. Testa connessione
```

### **4. Environment Variables n8n**
```javascript
// Settings > Environment Variables
WORDPRESS_API_URL=https://tuosito.com
WORDPRESS_API_KEY=demo-api-key-9lz721sv0xTjFNVA
MAIL2WA_API_KEY=wml2wa_live_abc123xyz789
GOOGLE_SHEET_ID=1Abc123Xyz789... // opzionale
COMPANY_NAME=La Tua Azienda
```

### **5. Attivazione Workflow**
```bash
1. n8n Dashboard > Workflows
2. Trova: "WhatsApp SaaS - Email to WhatsApp Numbers Extraction"
3. Clicca: Activate
4. Verifica scheduling: ogni 15 minuti
```

---

## 📊 **Fase 5: Setup Google Sheets (Opzionale)**

### **1. Creazione Sheet**
```bash
1. Vai a https://sheets.google.com
2. Crea nuovo foglio: "WhatsApp Extraction Log"
3. Headers riga 1:
   A1: timestamp | B1: email_id | C1: sender_email
   D1: subject | E1: numbers_extracted | F1: numbers_saved
   G1: api_success | H1: processing_status
```

### **2. Ottenimento Sheet ID**
```bash
# URL Sheet: https://docs.google.com/spreadsheets/d/SHEET_ID/edit
# Estrai SHEET_ID dalla URL
Sheet ID: 1Abc123Xyz789DefGhi456Jkl

# Aggiungi a n8n environment variables
GOOGLE_SHEET_ID=1Abc123Xyz789DefGhi456Jkl
```

### **3. Configurazione Permissions**
```bash
1. Sheet > Condividi
2. Aggiungi email account n8n
3. Permessi: Editor
4. Invia invito
```

---

## ✅ **Fase 6: Testing & Validazione**

### **1. Test Plugin WordPress**
```bash
# 1. Test Dashboard
WordPress Admin > WhatsApp SaaS > Dashboard
Verifica: Widget statistiche caricano correttamente

# 2. Test API manuale
curl -X GET https://tuosito.com/wp-json/wsp/v1/ping \
  -H "X-API-Key: demo-api-key-9lz721sv0xTjFNVA"
# Expected: {"success":true,"version":"1.0.2"}
```

### **2. Test n8n Workflow**
```bash
# 1. Esecuzione manuale
n8n Dashboard > Workflows > WhatsApp SaaS > Execute Workflow

# 2. Controlla logs
n8n > Executions > Ultima esecuzione
Verifica: Tutti i nodi verde (successo)

# 3. Test email
Invia email di test con numero WhatsApp alla casella Gmail configurata
Contenuto: "Il mio numero WhatsApp è 3331234567"
```

### **3. Test End-to-End**
```bash
# 1. Invia email test
FROM: test@example.com
TO: tua-email@gmail.com
SUBJECT: Test WhatsApp
BODY: "Ciao! Contattami su WhatsApp: +39 333 123 4567"

# 2. Attendi elaborazione (max 15 min)
# 3. Controlla WordPress
Dashboard > Numeri WhatsApp > Lista
Verifica: Nuovo numero aggiunto

# 4. Controlla Google Sheets (se configurato)
Verifica: Nuova riga con log elaborazione
```

---

## 🔧 **Troubleshooting Installazione**

### **Plugin Non Si Attiva**
```bash
# Controlla requirements PHP
php -v # Versione >= 7.4
php -m | grep mysqli # MySQL extension presente

# Controlla permessi file
ls -la wp-content/plugins/whatsapp-saas-plugin/
# Tutti i file dovrebbero essere leggibili da www-data

# Controlla error log
tail -f wp-content/debug.log
```

### **API Non Risponde**
```bash
# 1. Controlla mod_rewrite
apache2ctl -M | grep rewrite
# Dovrebbe mostrare: rewrite_module

# 2. Controlla permalink
WordPress Admin > Impostazioni > Permalink
Seleziona: Nome articolo (/%postname%/)
Salva modifiche

# 3. Test diretto API
curl -I https://tuosito.com/wp-json/
# Dovrebbe restituire 200 OK
```

### **n8n Non Si Connette**
```bash
# 1. Controlla credenziali Gmail
n8n > Credentials > Gmail OAuth2 API > Test

# 2. Controlla environment variables
n8n > Settings > Environment Variables
Verifica tutte le variabili impostate

# 3. Controlla connessione WordPress
curl -X GET https://tuosito.com/wp-json/wsp/v1/ping \
  -H "X-API-Key: tua-api-key"
```

### **Mail2Wa Non Invia**
```bash
# 1. Controlla API key
curl -X POST https://api.mail2wa.it/v1/test \
  -H "Authorization: Bearer tua-api-key"

# 2. Controlla saldo account
Login Mail2Wa Dashboard > Billing
Verifica: Crediti disponibili

# 3. Controlla numero WhatsApp
Dashboard > WhatsApp Numbers
Verifica: Numero attivo e verificato
```

---

## 📈 **Ottimizzazione Performance**

### **Database Optimization**
```sql
-- Aggiungi indici per query veloci
ALTER TABLE wp_wsp_whatsapp_numbers ADD INDEX idx_created_at (created_at);
ALTER TABLE wp_wsp_messages ADD INDEX idx_status_sent (status, sent_at);

-- Pulizia dati vecchi (opzionale)
DELETE FROM wp_wsp_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### **Caching Configuration**
```php
// wp-config.php
define('WP_CACHE', true);
define('WSP_CACHE_STATS', true); // Cache statistiche dashboard

// .htaccess
<IfModule mod_expires.c>
    ExpiresByType application/json "access plus 5 minutes"
</IfModule>
```

### **PHP Optimization**
```php
// php.ini
memory_limit = 512M
max_execution_time = 300
post_max_size = 50M
upload_max_filesize = 50M
max_input_vars = 3000
```

---

## 🛡️ **Configurazione Sicurezza**

### **WordPress Hardening**
```php
// wp-config.php
define('DISALLOW_FILE_EDIT', true);
define('WP_DEBUG', false); // In produzione

// .htaccess
<Files "wp-config.php">
    Order allow,deny
    Deny from all
</Files>
```

### **API Security**
```bash
# 1. Cambia API Key default
WordPress Admin > WhatsApp SaaS > Impostazioni
Genera nuova API Key sicura (32+ caratteri)

# 2. Whitelist IP n8n (opzionale)
# Aggiungi in functions.php del tema:
add_filter('wsp_api_allowed_ips', function($ips) {
    return ['IP_N8N_SERVER', '203.0.113.0'];
});
```

### **SSL/HTTPS Setup**
```bash
# 1. Forza HTTPS WordPress
WordPress Admin > Impostazioni > Generali
URL WordPress: https://tuosito.com
URL Sito: https://tuosito.com

# 2. Redirect HTTP to HTTPS
# .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 📞 **Supporto Post-Installazione**

### **Monitoraggio Sistema**
```bash
# 1. Setup monitoring
WordPress Admin > WhatsApp SaaS > Dashboard
Abilita: "Monitoraggio automatico errori"

# 2. Email alerts
Configura email admin per notifiche:
- Crediti bassi (< 100)
- Errori API (> 5% rate)
- Workflow n8n falliti
```

### **Backup & Maintenance**
```bash
# 1. Backup database tables
mysqldump -u user -p database wp_wsp_whatsapp_numbers wp_wsp_messages wp_wsp_credits > wsp_backup.sql

# 2. Backup configurazioni
wp option get wsp_settings > wsp_config_backup.json

# 3. Update schedule
- Plugin WordPress: Mensile
- n8n workflow: Quando necessario
- Credenziali API: Ogni 6 mesi
```

---

## ✅ **Checklist Installazione Completa**

### **✅ WordPress Plugin**
- [ ] Plugin installato e attivato
- [ ] API Key configurata
- [ ] Mail2Wa API Key inserita
- [ ] Test API /ping successful
- [ ] Dashboard carica correttamente

### **✅ n8n Workflow**
- [ ] Workflow importato
- [ ] Gmail OAuth configurato
- [ ] Environment variables impostate
- [ ] Workflow attivato
- [ ] Prima esecuzione successful

### **✅ Integrazioni Esterne**
- [ ] Mail2Wa account attivo
- [ ] WhatsApp Business collegato
- [ ] Google Sheets configurato (opzionale)
- [ ] SSL/HTTPS attivo

### **✅ Testing**
- [ ] Test email → estrazione numero
- [ ] Test invio messaggio WhatsApp
- [ ] Test dashboard statistiche
- [ ] Test gestione crediti

---

<div align="center">

### 🎉 **Installazione Completata!**

Il tuo sistema WhatsApp SaaS è ora completamente operativo.

[![Dashboard](https://img.shields.io/badge/Accedi-WordPress%20Dashboard-blue.svg)](#)
[![n8n](https://img.shields.io/badge/Gestisci-Workflow%20n8n-orange.svg)](#)
[![Support](https://img.shields.io/badge/Supporto-GitHub%20Issues-green.svg)](#)

*Per supporto tecnico, consulta la [documentazione completa](../README.md) o apri un [GitHub Issue](https://github.com/username/whatsapp-saas-plugin/issues)*

</div>