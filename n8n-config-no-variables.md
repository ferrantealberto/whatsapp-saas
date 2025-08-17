# 🔧 n8n Configuration SENZA VARIABILI

## 🚨 PROBLEMA RISOLTO
Il tuo piano n8n non supporta le variabili (`$vars`), quindi devi configurare valori hardcoded direttamente nel nodo.

---

## ⚙️ **CONFIGURAZIONE NODO "Send to WordPress API"**

### 📡 **HTTP Request Node Settings**

#### 1. **Method**: `POST`

#### 2. **URL**: 
```
https://TUODOMINIO.com/wp-json/wsp/v1/extract
```
**⚠️ SOSTITUISCI "TUODOMINIO.com" con il tuo vero dominio WordPress!**

Esempi:
- `https://miosito.com/wp-json/wsp/v1/extract`
- `https://www.miazienda.it/wp-json/wsp/v1/extract`

#### 3. **Authentication**: `None` (usiamo header custom)

#### 4. **Headers** (Clicca "Add Header"):

**Header 1:**
- **Name**: `X-API-Key`
- **Value**: `LA_TUA_API_KEY_WORDPRESS`

**Header 2:**
- **Name**: `Content-Type`  
- **Value**: `application/json`

**⚠️ SOSTITUISCI "LA_TUA_API_KEY_WORDPRESS" con la vera API key del plugin!**

#### 5. **Body**:
- **Body Type**: `JSON`
- **JSON**: `{{ JSON.stringify($json) }}`

#### 6. **Options** (Opzionale):
- **Timeout**: `30000`
- **Retry on Fail**: ✅ Enabled
- **Max Retries**: `3`

---

## 📝 **DOVE TROVARE I TUOI VALORI**

### 🔑 **API Key WordPress**:
1. **Login WordPress Admin**
2. **WhatsApp SaaS** → **Settings** 
3. **Copia l'API Key** (es: `wsp_abc123def456`)

### 🌐 **URL WordPress**:
Il tuo dominio WordPress (es: `https://miosito.com`)

---

## 📋 **CONFIGURAZIONE COMPLETA ESEMPIO**

```json
{
  "method": "POST",
  "url": "https://miosito.com/wp-json/wsp/v1/extract",
  "headers": {
    "X-API-Key": "wsp_abc123def456789",
    "Content-Type": "application/json"
  },
  "body": "{{ JSON.stringify($json) }}",
  "options": {
    "timeout": 30000
  }
}
```

---

## ✅ **VERIFICA CONFIGURAZIONE**

### Test 1: **Verifica URL Endpoint**
Apri nel browser: `https://TUODOMINIO.com/wp-json/wsp/v1/extract`

**Risultato atteso**: 
```json
{"error":"Missing API Key"}
```
**❌ Se vedi 404**: Plugin non attivo o URL sbagliato

### Test 2: **Test con Postman/Curl**
```bash
curl -X POST "https://TUODOMINIO.com/wp-json/wsp/v1/extract" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: LA_TUA_API_KEY" \
  -d '{"extracted_numbers":["393933930461"],"numbers_count":1}'
```

**Risultato atteso**:
```json
{"success":true,"numbers_saved":1}
```

---

## 🔧 **CONFIGURAZIONE STEP-BY-STEP n8n**

### Step 1: **Apri il Nodo HTTP Request**
1. Nel workflow, clicca sul nodo **"Send to WordPress API"**
2. Se non esiste, aggiungilo dopo **"Extract WhatsApp Numbers"**

### Step 2: **Configura URL**
```
Method: POST
URL: https://TUODOMINIO.com/wp-json/wsp/v1/extract
```

### Step 3: **Aggiungi Headers**
Clicca **"Add Header"** due volte:

**Header 1:**
```
Name: X-API-Key
Value: wsp_la_tua_vera_api_key
```

**Header 2:**
```
Name: Content-Type
Value: application/json
```

### Step 4: **Configura Body**
```
Send Body: ✅ Yes
Body Type: JSON
JSON Body: {{ JSON.stringify($json) }}
```

### Step 5: **Salva e Testa**
1. **Save** il nodo
2. **Save** il workflow  
3. **Execute Workflow** manualmente
4. **Controlla output** del nodo HTTP Request

---

## 🎯 **OUTPUT ATTESO**

### ✅ **Se Funziona**:
```json
{
  "success": true,
  "message": "Numbers saved successfully", 
  "numbers_saved": 1,
  "numbers_processed": ["393933930461"]
}
```

### ❌ **Se Non Funziona**:

**401 Unauthorized**:
- API Key sbagliata
- Controlla e ricopia dal WordPress

**404 Not Found**:
- URL sbagliato  
- Plugin WordPress non attivo

**500 Server Error**:
- Errore WordPress
- Controlla log errori del sito

---

## 🚀 **QUICK FIX**

### Se hai fretta, usa questa configurazione minimal:

1. **URL**: `https://TUOSITO.com/wp-json/wsp/v1/extract`
2. **Headers**: 
   - `X-API-Key: TUA_API_KEY`
   - `Content-Type: application/json`  
3. **Body**: `{{ JSON.stringify($json) }}`

**Sostituisci TUOSITO.com e TUA_API_KEY con i tuoi valori reali!**

Dopo questa configurazione, il numero **393933930461** dovrebbe apparire nel pannello WordPress! 🎯