# 🔧 Debug n8n → WordPress Integration

## 🚨 PROBLEMA IDENTIFICATO
I numeri vengono **estratti correttamente** da n8n ma **NON vengono salvati in WordPress**.

```json
// ✅ ESTRAZIONE FUNZIONA
{
  "extracted_numbers": ["393933930461"],
  "numbers_count": 1,
  "source": "email"
}
// ❌ MA NON ARRIVA A WORDPRESS
```

## 🔍 VERIFICA STEP-BY-STEP

### Step 1: Controlla il Workflow n8n
1. **Apri il tuo workflow n8n**
2. **Verifica la connessione** tra i nodi:
   ```
   Extract WhatsApp Numbers → Send to WordPress API → Process API Response
   ```
3. **Il nodo "Send to WordPress API" è connesso?**

### Step 2: Verifica Configurazione HTTP Request Node
Nel nodo **"Send to WordPress API"**:

#### 🔗 URL Configuration
```
URL: {{ $vars.WORDPRESS_API_URL }}/wp-json/wsp/v1/extract
```
**Verifica**:
- [ ] `$vars.WORDPRESS_API_URL` è configurato?
- [ ] Non ha `/` finale (es: `https://tuosito.com` ✅ non `https://tuosito.com/` ❌)

#### 🔑 Headers Configuration
```
X-API-Key: {{ $vars.WORDPRESS_API_KEY }}
Content-Type: application/json
```
**Verifica**:
- [ ] `$vars.WORDPRESS_API_KEY` è configurato?
- [ ] API Key è corretta (stessa del plugin WordPress)?

#### 📤 Body Configuration
```
Body Type: JSON
Body Content: {{ JSON.stringify($json) }}
```

### Step 3: Verifica Variabili n8n
1. **Vai in n8n Settings → Variables**
2. **Controlla che esistano**:
   - `WORDPRESS_API_URL` = `https://tuodominio.com`
   - `WORDPRESS_API_KEY` = `la_tua_api_key_wordpress`

### Step 4: Verifica Plugin WordPress
1. **Login WordPress Admin**
2. **Plugin → WhatsApp SaaS Plugin**
3. **Verifica sia ATTIVO** ✅
4. **Vai in WhatsApp SaaS → Settings**
5. **Controlla API Key** (deve essere uguale a quella in n8n)

### Step 5: Test Endpoint WordPress
**Testa manualmente l'endpoint**:
```bash
curl -X POST "https://tuodominio.com/wp-json/wsp/v1/extract" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: LA_TUA_API_KEY" \
  -d '{
    "email_id": "test123",
    "sender_email": "test@example.com", 
    "extracted_numbers": ["393933930461"],
    "numbers_count": 1,
    "source": "test"
  }'
```

**Risposta attesa**:
```json
{
  "success": true,
  "message": "Numbers saved successfully",
  "numbers_saved": 1
}
```

## 🚨 ERRORI COMUNI E SOLUZIONI

### ❌ Error: "API Key invalid"
**Soluzione**:
1. Verifica `$vars.WORDPRESS_API_KEY` in n8n
2. Confronta con API Key in WordPress admin
3. Rigenera API Key se necessario

### ❌ Error: "404 Not Found"
**Soluzione**:
1. Plugin WordPress non attivo → **Attivalo**
2. URL endpoint sbagliato → **Controlla $vars.WORDPRESS_API_URL**
3. Permalink WordPress non aggiornati → **Settings → Permalinks → Save**

### ❌ Error: "CORS Policy"
**Soluzione**:
1. Aggiungi dominio n8n ai CORS allowed origins
2. O disabilita CORS nel plugin WordPress

### ❌ Error: "500 Internal Server Error"
**Soluzione**:
1. Errore database WordPress
2. Controlla log errori WordPress
3. Verifica permessi database

## 🔧 DEBUG LOGS n8n

### Come vedere i logs del nodo HTTP Request:
1. **Esegui il workflow manualmente**
2. **Clicca sul nodo "Send to WordPress API"**
3. **Guarda il tab "Output"** per vedere:
   - Response status (200, 404, 500, etc.)
   - Response body
   - Error messages

### Logs da cercare:
```javascript
// ✅ SUCCESS
{
  "status": 200,
  "body": {
    "success": true,
    "numbers_saved": 1
  }
}

// ❌ FAILED
{
  "status": 401,
  "body": {
    "error": "Invalid API Key"
  }
}
```

## 💡 SOLUZIONI RAPIDE

### 🔧 Fix #1: Ricrea API Key
1. **WordPress Admin → WhatsApp SaaS → Settings**
2. **Generate New API Key**
3. **Copia la nuova key**
4. **n8n → Settings → Variables → WORDPRESS_API_KEY** → **Aggiorna**

### 🔧 Fix #2: Verifica URL
1. **Testa URL manualmente**: `https://tuosito.com/wp-json/wsp/v1/extract`
2. **Deve restituire error "Missing API Key"** (non 404)
3. **Se 404**: Plugin non attivo o permalink da aggiornare

### 🔧 Fix #3: Simplifica Test
**Crea nodo HTTP Request semplificato per test**:
```
Method: POST
URL: https://tuosito.com/wp-json/wsp/v1/extract
Headers: 
  X-API-Key: la_tua_key
  Content-Type: application/json
Body: 
{
  "extracted_numbers": ["393933930461"],
  "numbers_count": 1,
  "source": "test"
}
```

## 📞 NEXT STEPS

1. **Verifica variabili n8n** ($vars.WORDPRESS_API_URL e $vars.WORDPRESS_API_KEY)
2. **Controlla connessione nodi** nel workflow
3. **Testa endpoint WordPress** manualmente
4. **Guarda logs n8n** per errori HTTP
5. **Verifica plugin WordPress** sia attivo

Una volta risolto, il numero **393933930461** dovrebbe apparire nel pannello WordPress! 🎯