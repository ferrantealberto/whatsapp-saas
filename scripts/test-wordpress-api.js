// TEST CONNESSIONE API WORDPRESS
// Simula la chiamata che dovrebbe fare n8n

console.log('🔍 TEST CONNESSIONE WordPress API');
console.log('==================================');

// Dati di test (come quelli estratti da n8n)
const testData = {
  email_id: "198b3b2ffe97a59e",
  sender_email: "test@example.com",
  subject: "Test Email",
  reply_to: "393933930461@mail2wa.it",
  extracted_numbers: ["393933930461"],
  numbers_count: 1,
  source: "mail2wa",
  timestamp: new Date().toISOString(),
  processing_node: "extract_whatsapp_numbers_fixed"
};

console.log('📧 Dati da inviare:');
console.log(JSON.stringify(testData, null, 2));

// Funzione per testare API WordPress
async function testWordPressAPI() {
  console.log('\n🔗 CONTROLLI PRELIMINARI');
  console.log('========================');
  
  // Verifica variabili necessarie per n8n
  const requiredVars = [
    'WORDPRESS_API_URL',
    'WORDPRESS_API_KEY'
  ];
  
  console.log('📋 Variabili n8n richieste:');
  requiredVars.forEach(varName => {
    console.log(`- ${varName}: [DEVE ESSERE CONFIGURATA IN N8N]`);
  });
  
  console.log('\n📍 URL endpoint previsto:');
  console.log('{{ $vars.WORDPRESS_API_URL }}/wp-json/wsp/v1/extract');
  
  console.log('\n🔑 Headers richiesti:');
  console.log('- X-API-Key: {{ $vars.WORDPRESS_API_KEY }}');
  console.log('- Content-Type: application/json');
  
  console.log('\n📤 Corpo richiesta (JSON):');
  console.log(JSON.stringify(testData));
  
  console.log('\n✅ RISPOSTA WORDPRESS ATTESA:');
  console.log(JSON.stringify({
    success: true,
    message: "Numbers saved successfully",
    numbers_saved: 1,
    numbers_processed: ["393933930461"],
    source: "mail2wa"
  }, null, 2));
  
  console.log('\n🚨 POSSIBILI ERRORI:');
  console.log('===================');
  
  const commonErrors = [
    {
      error: 'API Key invalid',
      solution: 'Verifica $vars.WORDPRESS_API_KEY in n8n'
    },
    {
      error: 'Endpoint not found (404)',
      solution: 'Verifica che il plugin WSP sia attivo in WordPress'
    },
    {
      error: 'CORS error',
      solution: 'Aggiungi n8n domain ai CORS allowed origins'
    },
    {
      error: 'Database connection failed',
      solution: 'Controlla connessione database WordPress'
    },
    {
      error: 'Plugin not activated',
      solution: 'Attiva plugin WhatsApp SaaS in WordPress admin'
    }
  ];
  
  commonErrors.forEach((item, index) => {
    console.log(`${index + 1}. ❌ ${item.error}`);
    console.log(`   💡 Soluzione: ${item.solution}\n`);
  });
  
  console.log('\n🔧 DEBUGGING STEPS PER N8N:');
  console.log('============================');
  console.log('1. Verifica il nodo "Send to WordPress API"');
  console.log('2. Controlla che sia connesso dopo "Extract WhatsApp Numbers"');
  console.log('3. Verifica variabili: $vars.WORDPRESS_API_URL e $vars.WORDPRESS_API_KEY');
  console.log('4. Controlla logs del nodo HTTP Request per errori');
  console.log('5. Testa endpoint manualmente con Postman/curl');
  
  console.log('\n📋 COMANDO CURL PER TEST MANUALE:');
  console.log('curl -X POST "YOUR_WORDPRESS_URL/wp-json/wsp/v1/extract" \\');
  console.log('  -H "Content-Type: application/json" \\');
  console.log('  -H "X-API-Key: YOUR_API_KEY" \\');
  console.log('  -d \'' + JSON.stringify(testData) + '\'');
}

// Esegui test
testWordPressAPI().catch(console.error);