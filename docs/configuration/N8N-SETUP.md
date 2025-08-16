# 🤖 Guida Configurazione n8n Workflow

**Setup completo del workflow n8n per automazione estrazione numeri WhatsApp da Gmail**

---

## 📋 **Prerequisiti n8n**

### **Opzione 1: n8n Cloud (Raccomandato)**
```bash
✅ Account n8n Cloud (Pro/Team per Gmail OAuth)
✅ URL: https://app.n8n.cloud
✅ Benefits: 
   - Setup rapido
   - Maintenance automatico
   - SSL incluso
   - Backup automatici
```

### **Opzione 2: n8n Self-Hosted**
```bash
✅ Server con Docker o Node.js
✅ Dominio con SSL certificate
✅ Minimum requirements:
   - RAM: 2GB
   - CPU: 2 cores
   - Storage: 20GB
   - Network: Stabile per webhook
```

---

## 🚀 **Fase 1: Setup n8n Instance**

### **n8n Cloud Setup**
```bash
1. Registrazione: https://app.n8n.cloud
2. Scegli piano: Pro ($20/mese) o Team ($50/mese)
3. Verifica email e completa profilo
4. Accedi dashboard: https://[your-tenant].app.n8n.cloud
```

### **n8n Self-Hosted Setup**
```bash
# Docker Compose (raccomandato)
version: '3.8'
services:
  n8n:
    image: n8nio/n8n:latest
    container_name: n8n
    restart: unless-stopped
    ports:
      - "5678:5678"
    environment:
      - N8N_BASIC_AUTH_ACTIVE=true
      - N8N_BASIC_AUTH_USER=admin
      - N8N_BASIC_AUTH_PASSWORD=secure_password
      - N8N_HOST=n8n.yourdomain.com
      - N8N_PROTOCOL=https
      - N8N_PORT=5678
      - WEBHOOK_URL=https://n8n.yourdomain.com
    volumes:
      - n8n_data:/home/node/.n8n
    networks:
      - n8n_network

volumes:
  n8n_data:

networks:
  n8n_network:
```

```bash
# Avvio
docker-compose up -d

# Verifica
curl https://n8n.yourdomain.com/healthz
```

---

## 📥 **Fase 2: Import Workflow**

### **1. Download Workflow File**
```bash
# From repository
wget https://raw.githubusercontent.com/username/whatsapp-saas-plugin/main/n8n-whatsapp-workflow.json

# Or copy from project
cp n8n-whatsapp-workflow.json ~/Downloads/
```

### **2. Import in n8n**
```bash
1. n8n Dashboard > Workflows
2. Click "Import from file"
3. Select: n8n-whatsapp-workflow.json
4. Click "Import"
5. Workflow name: "WhatsApp SaaS - Email to WhatsApp Numbers Extraction"
```

### **3. Verifica Import**
```bash
# Controlla nodi presenti:
✅ Schedule Every 15 Minutes (Cron)
✅ Gmail - Get New Emails (Gmail)
✅ Extract WhatsApp Numbers (Code)
✅ Send to WordPress API (HTTP Request)
✅ Process API Response (Code)
✅ Log to Google Sheets (Google Sheets)
✅ Filter Only Successful Extractions (IF)
✅ Send WhatsApp Messages (HTTP Request)
✅ Generate Summary Report (Code)
```

---

## 🔑 **Fase 3: Configurazione Credenziali**

### **Gmail OAuth Credentials**

#### **1. Google Cloud Console Setup**
```bash
1. Vai a: https://console.cloud.google.com
2. Crea/seleziona progetto
3. API & Services > Library
4. Cerca e abilita: "Gmail API"
5. API & Services > Credentials
6. Create Credentials > OAuth 2.0 Client IDs
7. Application type: Web application
8. Name: "n8n WhatsApp SaaS Integration"
```

#### **2. OAuth Redirect URIs**
```bash
# n8n Cloud
Authorized redirect URIs:
https://[your-tenant].app.n8n.cloud/rest/oauth2-credential/callback

# n8n Self-Hosted  
Authorized redirect URIs:
https://n8n.yourdomain.com/rest/oauth2-credential/callback
```

#### **3. Download Credentials**
```bash
1. Click "Download JSON"
2. Save as: gmail_oauth_credentials.json
3. Note: client_id and client_secret
```

#### **4. n8n Gmail Credential Setup**
```bash
1. n8n > Credentials > Add Credential
2. Search: "Gmail OAuth2 API"
3. Credential Name: "Gmail WhatsApp SaaS"
4. Insert:
   - Client ID: [from JSON]
   - Client Secret: [from JSON]
5. Click "Connect my account"
6. Authorize Google account access
7. Test connection: Should show "Connected"
```

### **HTTP Header Auth Credential (WordPress API)**
```bash
1. n8n > Credentials > Add Credential
2. Search: "HTTP Header Auth"
3. Credential Name: "WordPress WhatsApp API"
4. Header Name: X-API-Key
5. Header Value: demo-api-key-9lz721sv0xTjFNVA
6. Save credential
```

### **Google Sheets Credential (Opzionale)**
```bash
1. n8n > Credentials > Add Credential
2. Search: "Google Sheets OAuth2 API"
3. Credential Name: "Google Sheets Logging"
4. Use same OAuth setup as Gmail
5. Authorize Google Sheets access
```

---

## ⚙️ **Fase 4: Environment Variables**

### **Configurazione Variables**
```bash
# n8n Dashboard > Settings > Environment Variables
# Aggiungi le seguenti variabili:

WORDPRESS_API_URL=https://tuosito.com
WORDPRESS_API_KEY=demo-api-key-9lz721sv0xTjFNVA
MAIL2WA_WEBHOOK_URL=https://api.mail2wa.it/webhook/send
MAIL2WA_API_KEY=wml2wa_live_abc123xyz789
GOOGLE_SHEET_ID=1Abc123Xyz789DefGhi456Jkl
COMPANY_NAME=La Tua Azienda
```

### **Variabili Avanzate (Opzionali)**
```bash
# Filtri email Gmail
GMAIL_SEARCH_QUERY=has:attachment OR WhatsApp OR numero OR telefono OR contatto

# Rate limiting
API_REQUEST_DELAY=2000  # milliseconds tra richieste
MAX_RETRY_ATTEMPTS=3

# Debugging
DEBUG_MODE=false
LOG_LEVEL=info
```

---

## 🔧 **Fase 5: Configurazione Nodi Workflow**

### **1. Cron Schedule Node**
```bash
Node: "Schedule Every 15 Minutes"
Configuration:
- Mode: Every 15 Minutes
- Cron Expression: 0 */15 * * * *
- Timezone: Europe/Rome
```

### **2. Gmail Node**
```bash
Node: "Gmail - Get New Emails"
Configuration:
- Credential: Gmail WhatsApp SaaS
- Resource: Message
- Operation: Get All  
- Return All: true
- Additional Fields:
  - Format: full
  - Query: {{ $vars.GMAIL_SEARCH_QUERY }}
  - Received After: {{ $now.minus({days: 1}).toISO() }}
```

### **3. Code Node - Extract Numbers**
```javascript
// Node: "Extract WhatsApp Numbers"
// Il codice è già incluso nel workflow importato
// Estrae numeri usando pattern regex avanzati:

const patterns = [
  // Italian mobile with +39
  /(?:\+39[\s\-\.]?)?3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g,
  // International WhatsApp
  /\+[1-9]{1}[0-9]{1,3}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}/g,
  // General mobile
  /3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g
];
```

### **4. HTTP Request Node - WordPress API**
```bash
Node: "Send to WordPress API"
Configuration:
- Method: POST
- URL: {{ $vars.WORDPRESS_API_URL }}/wp-json/wsp/v1/extract
- Authentication: Predefined Credential Type > HTTP Header Auth
- Credential: WordPress WhatsApp API
- Body Content Type: JSON
- JSON Body: {{ JSON.stringify($json) }}
- Timeout: 30000ms
- Retry: 3 attempts
```

### **5. Google Sheets Node**
```bash
Node: "Log to Google Sheets"
Configuration:
- Credential: Google Sheets Logging  
- Resource: Sheet
- Operation: Append or Update
- Document ID: {{ $vars.GOOGLE_SHEET_ID }}
- Sheet Name: WhatsApp Extraction Log
- Key Row: 1
- Data Mode: Auto-map data
```

### **6. IF Node - Filter Success**
```bash
Node: "Filter Only Successful Extractions"
Configuration:
- Conditions: AND
- Condition 1:
  - Left Value: {{ $json.numbers_extracted }}
  - Operation: Larger (>)
  - Right Value: 0
```

### **7. HTTP Request Node - WhatsApp Sender**
```bash
Node: "Send WhatsApp Messages"
Configuration:
- Method: POST
- URL: {{ $vars.MAIL2WA_WEBHOOK_URL }}
- Headers:
  - Authorization: Bearer {{ $vars.MAIL2WA_API_KEY }}
  - Content-Type: application/json
- Body:
{
  "numbers": {{ JSON.stringify($('Extract WhatsApp Numbers').item.json.extracted_numbers) }},
  "message": "Ciao! Abbiamo ricevuto la tua email. Ti contatteremo presto!",
  "sender_name": "{{ $vars.COMPANY_NAME }}"
}
```

---

## ✅ **Fase 6: Testing Workflow**

### **1. Test Singoli Nodi**

#### **Test Gmail Connection**
```bash
1. Click su nodo "Gmail - Get New Emails"
2. Click "Execute Node"
3. Verifica output: Lista email recenti
4. Check: Nessun errore di autenticazione
```

#### **Test Pattern Extraction**
```bash
1. Prepara email test con numero: "Il mio WhatsApp è 3331234567"
2. Click su nodo "Extract WhatsApp Numbers"  
3. Click "Execute Node"
4. Verifica output: Array con numero estratto
```

#### **Test WordPress API**
```bash
1. Verifica input da nodo precedente
2. Click su nodo "Send to WordPress API"
3. Click "Execute Node"  
4. Expected response: {"success":true,"numbers_saved":1}
5. Check WordPress Dashboard per nuovo numero
```

### **2. Test Workflow Completo**

#### **Execution Manuale**
```bash
1. Workflow > Execute Workflow
2. Wait for completion (30-60 seconds)
3. Check execution log:
   - Green nodes = Success
   - Red nodes = Error
4. Review output di ogni nodo
```

#### **Test Email End-to-End**
```bash
# 1. Invia email test
TO: your-gmail@gmail.com (account configurato in n8n)
SUBJECT: Test WhatsApp Extraction
BODY: "Ciao! Contattami su WhatsApp: +39 333 123 4567 per info"

# 2. Attendi 15 minuti (prossima esecuzione cron)
# 3. Check WordPress Dashboard
WordPress Admin > WhatsApp SaaS > Numeri WhatsApp
Verify: Nuovo numero +39 333 123 4567 presente

# 4. Check Google Sheets (se configurato)
Verify: Nuova riga con log di elaborazione
```

---

## 🔍 **Troubleshooting n8n**

### **Gmail OAuth Issues**
```bash
# Error: "Invalid credentials"
1. Check Gmail API enabled in Google Cloud Console
2. Verify OAuth redirect URI matches n8n URL
3. Re-authorize credential in n8n
4. Test with fresh browser/incognito

# Error: "Quota exceeded"  
1. Google Cloud Console > APIs & Services > Quotas
2. Gmail API > Requests per day: Check usage
3. Request quota increase if needed
```

### **WordPress API Connection Issues**
```bash
# Error: "API key invalid"
1. WordPress Admin > WhatsApp SaaS > Impostazioni
2. Verify API key matches n8n environment variable
3. Test manually:
curl -H "X-API-Key: your-key" https://site.com/wp-json/wsp/v1/ping

# Error: "Connection timeout"
1. Check WordPress SSL certificate valid
2. Verify no firewall blocking n8n IP
3. Increase timeout in HTTP Request node
```

### **Workflow Execution Issues**
```bash
# Error: "Workflow not triggering"
1. Check cron schedule active
2. Verify workflow status: Active
3. Check n8n execution logs
4. Test manual execution first

# Error: "Node execution failed"
1. Click on failed node
2. Review error message
3. Check input data format
4. Verify credentials and permissions
```

---

## 📊 **Monitoring & Maintenance**

### **Execution Logs**
```bash
# n8n Dashboard
1. Workflows > WhatsApp SaaS workflow
2. Executions tab
3. Review recent executions:
   - Green = Success
   - Red = Failed
   - Yellow = Warning

# Log retention
- n8n Cloud: 30 days
- Self-hosted: Configurable
```

### **Performance Monitoring**
```bash
# Key Metrics to Monitor
- Execution frequency: Every 15 minutes
- Success rate: Target > 95%
- Average execution time: < 60 seconds
- Gmail API quota usage: < 80% daily limit
- WordPress API response time: < 5 seconds
```

### **Alerts Setup**
```bash
# n8n Cloud: Built-in alerts
Settings > Notifications > Configure alerts for:
- Workflow failures
- Quota exceeded
- Long execution times

# Self-hosted: Custom monitoring
# Use tools like Prometheus/Grafana or simple curl checks
*/5 * * * * curl -f https://n8n.domain.com/healthz || mail -s "n8n down" admin@domain.com
```

---

## 🔄 **Updates & Maintenance**

### **Workflow Updates**
```bash
# Update from repository
1. Download new n8n-whatsapp-workflow.json
2. n8n > Workflows > Import from file
3. Choose: Replace existing workflow
4. Review changes and test
5. Reactivate workflow if needed
```

### **Credential Renewal**
```bash
# Gmail OAuth (expires annually)
1. n8n > Credentials > Gmail WhatsApp SaaS
2. Click "Reconnect"
3. Re-authorize Google account
4. Test workflow execution

# API Key Rotation (recommended every 6 months)
1. Generate new WordPress API key
2. Update n8n environment variable
3. Update workflow HTTP Request nodes
4. Test connection
```

### **Backup & Recovery**
```bash
# Export workflow
1. n8n > Workflows > WhatsApp SaaS
2. Click "Download" 
3. Save JSON file securely

# Export credentials (metadata only)
1. Document credential names and types
2. Store OAuth client IDs/secrets securely
3. Document environment variables

# Recovery procedure
1. Re-import workflow JSON
2. Recreate credentials with same names
3. Restore environment variables
4. Test complete workflow
```

---

## 📈 **Ottimizzazione Performance**

### **Gmail API Optimization**
```bash
# Reduce API calls
- Use specific Gmail search queries
- Limit date range for email retrieval
- Implement smart caching (store processed email IDs)

# Query examples
GMAIL_SEARCH_QUERY=newer_than:1d (WhatsApp OR numero OR telefono)
```

### **Workflow Optimization**
```bash
# Reduce execution time
- Use batch processing for multiple numbers
- Implement early exit for empty results
- Cache frequently accessed data

# Memory optimization
- Limit email content size (first 1000 chars)
- Process emails in smaller batches
- Clear variables when not needed
```

### **Error Handling Enhancement**
```bash
# Add retry logic with exponential backoff
- HTTP Request nodes: Set retry attempts
- Add delay between retries
- Implement circuit breaker pattern

# Graceful degradation
- Continue processing other emails if one fails
- Log errors without stopping workflow
- Send summary reports with error counts
```

---

<div align="center">

### 🎯 **n8n Workflow Configurato e Attivo!**

Il tuo workflow n8n è ora completamente operativo e pronto per automatizzare l'estrazione dei numeri WhatsApp.

[![n8n Dashboard](https://img.shields.io/badge/Accedi-n8n%20Dashboard-orange.svg)](#)
[![Executions](https://img.shields.io/badge/Monitor-Workflow%20Executions-blue.svg)](#)
[![Support](https://img.shields.io/badge/Support-Workflow%20Issues-green.svg)](#)

*Il workflow si eseguirà automaticamente ogni 15 minuti per processare le nuove email Gmail e estrarre i numeri WhatsApp*

</div>