# ⚡ QUICK FIX - n8n SENZA VARIABILI

## 🎯 PROBLEMA: Il tuo piano n8n non supporta `$vars`

## 🚀 SOLUZIONE RAPIDA (5 minuti)

### 1. **Trova i Tuoi Valori** 📝

#### A) **API Key WordPress**:
1. Login WordPress Admin
2. **WhatsApp SaaS** → **Settings**  
3. **Copia API Key** (es: `wsp_abc123def456`)

#### B) **URL WordPress**:
Il tuo dominio (es: `https://miosito.com`)

---

### 2. **Configura Nodo n8n** ⚙️

#### Nel nodo **"Send to WordPress API"**:

**URL**: 
```
https://TUODOMINIO.com/wp-json/wsp/v1/extract
```
*Sostituisci TUODOMINIO.com con il tuo vero dominio!*

**Headers** (clicca "Add Header" due volte):
```
Header 1:
Name: X-API-Key  
Value: wsp_la_tua_vera_api_key

Header 2:  
Name: Content-Type
Value: application/json
```

**Body**:
```
Body Type: JSON
Content: {{ JSON.stringify($json) }}
```

---

### 3. **Esempio Concreto** 💡

Se il tuo sito è `https://miosito.com` e la tua API key è `wsp_abc123`:

**URL**: 
```
https://miosito.com/wp-json/wsp/v1/extract
```

**Headers**:
```
X-API-Key: wsp_abc123
Content-Type: application/json  
```

---

### 4. **Test Rapido** 🧪

1. **Salva** il nodo
2. **Salva** il workflow
3. **Execute Workflow** manualmente
4. **Controlla output** del nodo HTTP Request

**Output atteso**:
```json
{
  "success": true,
  "numbers_saved": 1
}
```

---

### 5. **Se Non Funziona** 🔧

#### ❌ **404 Error**: 
- Plugin WordPress non attivo
- URL sbagliato

#### ❌ **401 Error**:
- API Key sbagliata  
- Ricopia dal WordPress

#### ❌ **500 Error**:
- Errore server WordPress
- Controlla log errori sito

---

## ✅ **RISULTATO**

Dopo la configurazione, il numero **393933930461** apparirà in:
**WordPress Admin** → **WhatsApp SaaS** → **Numeri WhatsApp**

---

## 📞 **Se Hai Bisogno di Aiuto**

1. **Verifica plugin WordPress attivo**
2. **Testa URL**: apri `https://tuosito.com/wp-json/wsp/v1/extract` nel browser
3. **Dovrebbe dare**: `{"error":"Missing API Key"}` (non 404)
4. **Se 404**: Plugin non attivo o URL sbagliato

**Tempo necessario**: 5 minuti
**Difficoltà**: Facile ⭐⭐☆☆☆