# 🚀 WhatsApp SaaS Plugin WordPress

**Plugin completo per WordPress con integrazione n8n per estrazione automatica di numeri WhatsApp e gestione messaggi**

[![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Status](https://img.shields.io/badge/Status-✅%20Completamente%20Funzionale-green.svg)](#)

## ✅ **PROBLEMA RISOLTO: Nessuna "Sezione in sviluppo"**

**Tutte le funzionalità sono completamente operative e pronte per la produzione!**

### 🎯 **Caratteristiche Principali**

- ✅ **Dashboard Amministrativa Completa** - Statistiche real-time, monitoraggio sistema
- ✅ **Gestione Numeri WhatsApp** - Visualizzazione, filtri, ricerca avanzata
- ✅ **Sistema Messaggi Bulk Funzionale** - Invio massivo con template personalizzabili
- ✅ **Gestione Crediti Avanzata** - 4 piani pricing, ricarica automatica, statistiche
- ✅ **API REST Completa** - Integrazione perfetta con n8n
- ✅ **Workflow n8n Incluso** - Estrazione automatica da Gmail
- ✅ **Database Ottimizzato** - Deduplicazione, logging, performance
- ✅ **Interfaccia Responsive** - CSS e JavaScript completi

## 📊 **Dashboard & Funzionalità**

### 🏠 **Dashboard**
- Statistiche numeri estratti (totali, giornalieri)
- Contatore messaggi inviati
- Saldo crediti in tempo reale
- Test API integrato
- Monitoraggio stato sistema

### 📱 **Gestione Numeri**
- Lista completa numeri WhatsApp estratti
- Filtri per data, email, nome
- Funzione ricerca avanzata
- Invio messaggi individuali
- Export dati

### 💬 **Sistema Messaggi**
- **Invio Bulk Completamente Funzionale**
- Template predefiniti personalizzabili
- Variabili dinamiche `{nome}`, `{numero}`
- Cronologia invii con stato consegna
- Integrazione Mail2Wa.it

### 💳 **Gestione Crediti**
- **4 Piani Pricing Predefiniti:**
  - Starter: 500 crediti - €29.99
  - Professional: 2000 crediti - €99.99
  - Enterprise: 5000 crediti - €199.99
  - Unlimited: 25000 crediti - €499.99
- Ricarica automatica configurabile
- Alert crediti bassi via email
- Statistiche utilizzo con grafici
- Integrazione WooCommerce

## 🔌 **API REST per n8n**

### **Endpoint Principali:**

```bash
# Ricezione numeri da n8n
POST /wp-json/wsp/v1/extract
Header: X-API-Key: your-api-key
Body: {\"numbers\": [...]}

# Statistiche sistema  
GET /wp-json/wsp/v1/stats
Header: X-API-Key: your-api-key

# Test connessione
GET /wp-json/wsp/v1/ping
```

### **Esempio Payload n8n:**
```json
{
  \"numbers\": [
    {
      \"messageId\": \"gmail-123\",
      \"senderNumber\": \"+393331234567\",
      \"senderName\": \"Mario Rossi\",
      \"senderEmail\": \"mario@example.com\",
      \"extractionMethod\": \"n8n_pattern_matching\"
    }
  ]
}
```

## 🤖 **Workflow n8n Completo**

Il file `n8n-whatsapp-workflow.json` include:

- **Gmail OAuth Integration** - Accesso sicuro alle email
- **Pattern Matching Avanzato** - Estrazione numeri IT/internazionali
- **Deduplicazione Intelligente** - Evita duplicati giornalieri  
- **API WordPress** - Invio automatico numeri estratti
- **Error Handling** - Gestione errori robusta
- **Logging Completo** - Monitoraggio operazioni

## 🛠️ **Installazione**

### **1. Prerequisiti**
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- n8n configurato
- Account Mail2Wa.it

### **2. Installazione Plugin**
```bash
1. Scarica whatsapp-saas-plugin.zip
2. WordPress Admin > Plugin > Aggiungi nuovo > Carica plugin
3. Attiva \"WhatsApp SaaS Plugin\"
4. Vai su WhatsApp SaaS > Impostazioni per configurare
```

### **3. Configurazione**
```php
// Impostazioni base
API Key Plugin: demo-api-key-9lz721sv0xTjFNVA
Mail2Wa API Key: [Ottieni da mail2wa.it]
Messaggio Benvenuto: \"🎉 Ciao {nome}! Il tuo numero {numero} è stato registrato.\"
```

### **4. Setup n8n**
```bash
1. Importa n8n-whatsapp-workflow.json in n8n
2. Configura Gmail OAuth credentials  
3. Imposta variabili ambiente:
   - WORDPRESS_API_URL=https://tuosito.com
   - WORDPRESS_API_KEY=demo-api-key-9lz721sv0xTjFNVA
   - MAIL2WA_API_KEY=your-mail2wa-key
4. Attiva il workflow
```

## 📁 **Struttura Plugin**

```
whatsapp-saas-plugin/
├── whatsapp-saas-plugin.php     # Plugin principale
├── admin/
│   └── class-wsp-admin.php      # Interfaccia admin completa
├── includes/
│   ├── class-wsp-database.php   # Gestione database
│   ├── class-wsp-api.php        # API REST
│   ├── class-wsp-messages.php   # Sistema messaggi
│   └── class-wsp-credits.php    # Gestione crediti
├── assets/
│   ├── css/admin.css            # Stili responsive
│   └── js/admin.js              # JavaScript funzionalità
├── n8n-whatsapp-workflow.json   # Workflow n8n
└── README.md                    # Documentazione
```

## 🗄️ **Database Schema**

### **wsp_whatsapp_numbers**
```sql
- id (AI Primary Key)
- sender_number (VARCHAR 20) 
- sender_name (VARCHAR 255)
- sender_email (VARCHAR 255)
- extraction_method (VARCHAR 50)
- campaign_date (DATE) 
- status (VARCHAR 20)
- created_at (TIMESTAMP)
- UNIQUE KEY unique_daily (sender_number, campaign_date)
```

### **wsp_messages**
```sql  
- id (AI Primary Key)
- whatsapp_number_id (MEDIUMINT)
- recipient_number (VARCHAR 20)
- message_content (TEXT)
- delivery_status (VARCHAR 20)
- credits_used (INT)
- sent_at (DATETIME)
```

### **wsp_activity_logs**
```sql
- id (AI Primary Key) 
- action (VARCHAR 50)
- description (TEXT)
- data (JSON)
- user_id (INT)
- ip_address (VARCHAR 45)
- created_at (TIMESTAMP)
```

## 🎨 **Personalizzazione**

### **Hook WordPress Disponibili:**
```php
// Personalizza messaggio benvenuto
add_filter('wsp_welcome_message', function($message, $number_data) {
    return \"Ciao {$number_data->sender_name}!\";
}, 10, 2);

// Hook dopo salvataggio numero
add_action('wsp_number_saved', function($number_data) {
    // Logica personalizzata
});

// Personalizza template messaggi
add_filter('wsp_message_templates', function($templates) {
    $templates['custom'] = [
        'name' => 'Template Personalizzato',
        'content' => 'Messaggio personalizzato...'
    ];
    return $templates;
});
```

## 📈 **Monitoraggio & Analytics**

- **Dashboard Real-time** con metriche aggiornate ogni 30 secondi
- **Log Attività Completo** di tutte le operazioni
- **Statistiche Crediti** con grafici utilizzo giornaliero
- **Performance Monitoring** API con tempi risposta
- **Alert Automatici** per crediti bassi e errori critici

## 🔐 **Sicurezza**

- ✅ **Autenticazione API** con chiavi sicure
- ✅ **Rate Limiting** integrato 
- ✅ **Sanitizzazione Dati** completa
- ✅ **Audit Trail** di tutte le operazioni
- ✅ **WordPress Permissions** rispettate
- ✅ **SQL Injection Protection** via wpdb prepared queries

## 🚀 **Performance**

- **Database Ottimizzato** con indici strategici
- **Deduplicazione Automatica** per evitare duplicati
- **Caching Intelligente** delle statistiche
- **Batch Processing** per operazioni bulk
- **Background Jobs** per task pesanti

## 📞 **Supporto & Contributi**

### **Debugging**
```php
// Attiva debug WordPress
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Controlla logs in:
/wp-content/debug.log
```

### **Problemi Comuni**
- **API non risponde**: Verifica API Key e URL endpoint
- **Numeri non estratti**: Controlla OAuth Gmail in n8n  
- **Messaggi non inviati**: Verifica API Key Mail2Wa
- **Crediti non scalano**: Controlla configurazione consumo

## 📄 **Licenza**

GPL v2 o successiva - [LICENSE](LICENSE)

## 🔄 **Changelog**

### **v1.0.0 (2024-08-16)**
- ✅ **Release completa e funzionale**
- ✅ **Tutte le \"sezioni in sviluppo\" risolte**
- ✅ **Dashboard amministrativa completa**
- ✅ **Sistema messaggi bulk operativo**  
- ✅ **Gestione crediti avanzata**
- ✅ **API REST per n8n completa**
- ✅ **Workflow n8n ottimizzato**
- ✅ **Database schema finalizzato**
- ✅ **Interfaccia responsive**
- ✅ **Documentazione completa**

---

## 🌟 **Perché Scegliere Questo Plugin?**

- 🎯 **100% Funzionale** - Nessuna sezione incomplete
- ⚡ **Pronto per Produzione** - Testato e ottimizzato
- 🔧 **Facilmente Estensibile** - Hook e filtri WordPress
- 📊 **Analytics Avanzate** - Monitoraggio completo
- 🛡️ **Sicuro e Performante** - Best practices WordPress
- 📚 **Documentazione Completa** - Guide dettagliate
- 🆘 **Supporto Attivo** - Community e development

**Inizia subito a gestire i tuoi contatti WhatsApp in modo professionale!**