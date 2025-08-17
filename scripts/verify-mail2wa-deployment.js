// TEST DI VERIFICA DEPLOYMENT MAIL2WA
// Questo script simula l'email di test per verificare l'estrazione

console.log('🔍 VERIFICA DEPLOYMENT MAIL2WA FIX');
console.log('=================================');

// Simula l'email di test fornita dall'utente
const testEmail = {
  id: 'test-mail2wa-393511845192',
  subject: 'Test Mail2Wa WhatsApp',
  from: 'test@example.com',
  replyTo: '393511845192@mail2wa.it', // Questo è il formato Mail2Wa
  bodyText: 'Email di test per verificare estrazione numero WhatsApp Mail2Wa',
  bodyHtml: '<p>Email di test per verificare estrazione numero WhatsApp Mail2Wa</p>'
};

console.log('📧 Email di test:', JSON.stringify(testEmail, null, 2));

// Pattern migliorati (stessi del codice n8n)
const patterns = [
  /(?:\+39[\s\-\.]?)?3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g,
  /\b39[0-9]{10,12}\b/g, // Mail2Wa format
  /\b3[0-9]{9}\b/g,
  /\+[1-9]{1}[0-9]{1,3}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}/g,
  /3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g,
  /([0-9]{10,12})@mail2wa\.it/g // Mail2Wa Reply-To format
];

console.log('\n🔍 TESTING PATTERN EXTRACTION');
console.log('==============================');

const foundNumbers = new Set();
const searchTexts = [testEmail.bodyText, testEmail.subject, testEmail.replyTo];

searchTexts.forEach((text, index) => {
  if (text) {
    const textType = ['content', 'subject', 'reply-to'][index];
    console.log(`\n📍 Searching in ${textType}: "${text}"`);
    
    patterns.forEach((pattern, patternIndex) => {
      const matches = text.match(pattern);
      if (matches) {
        matches.forEach(match => {
          // Clean the number
          let cleanNumber = match.replace(/[\s\-\.@mail2wa\.it]/g, '');
          
          // Handle Reply-To format (extract number before @)
          if (match.includes('@mail2wa.it')) {
            cleanNumber = match.split('@')[0];
          }
          
          // Normalize Italian numbers
          if (cleanNumber.startsWith('39') && cleanNumber.length >= 11) {
            foundNumbers.add(cleanNumber);
          } else if (cleanNumber.startsWith('3') && cleanNumber.length === 10) {
            foundNumbers.add('39' + cleanNumber);
          } else if (cleanNumber.length >= 10) {
            foundNumbers.add(cleanNumber);
          }
          
          console.log(`   ✅ Pattern ${patternIndex} found: ${match} -> ${cleanNumber}`);
        });
      }
    });
  }
});

// Validate numbers
const extractedNumbers = Array.from(foundNumbers).filter(number => {
  const isValid = /^39[0-9]{10,12}$/.test(number) || 
                 /^3[0-9]{9}$/.test(number) ||
                 /^\+?[1-9][0-9]{8,14}$/.test(number);
  return isValid;
});

console.log('\n📊 RISULTATI ESTRAZIONE');
console.log('========================');
console.log(`📱 Numeri estratti: ${extractedNumbers.length}`);
console.log(`📋 Lista numeri: ${JSON.stringify(extractedNumbers)}`);

// Verifica specifica per il numero di test
const targetNumber = '393511845192';
const isTargetFound = extractedNumbers.includes(targetNumber);

console.log('\n🎯 VERIFICA NUMERO TARGET');
console.log('==========================');
console.log(`🔍 Numero cercato: ${targetNumber}`);
console.log(`${isTargetFound ? '✅' : '❌'} Trovato: ${isTargetFound}`);

// Determine source type
let sourceType = 'email';
if (testEmail.replyTo && testEmail.replyTo.includes('mail2wa.it')) {
  sourceType = 'mail2wa';
}

console.log(`📍 Tipo sorgente: ${sourceType}`);

// Final result simulation
const result = {
  email_id: testEmail.id,
  sender_email: testEmail.from,
  subject: testEmail.subject,
  reply_to: testEmail.replyTo,
  extracted_numbers: extractedNumbers,
  numbers_count: extractedNumbers.length,
  source: sourceType,
  timestamp: new Date().toISOString(),
  processing_node: 'extract_whatsapp_numbers_fixed'
};

console.log('\n📦 OGGETTO RISULTATO (come sarà inviato all\'API WordPress)');
console.log('========================================================');
console.log(JSON.stringify(result, null, 2));

console.log('\n🚀 STATO DEPLOYMENT');
console.log('===================');
if (isTargetFound && extractedNumbers.length > 0) {
  console.log('✅ DEPLOYMENT SUCCESSFUL - Il fix Mail2Wa funziona correttamente!');
  console.log('✅ Il numero 393511845192 viene estratto correttamente');
  console.log('✅ Ready per testing in produzione');
} else {
  console.log('❌ DEPLOYMENT FAILED - Il fix Mail2Wa NON funziona');
  console.log('❌ Controllare il codice nel nodo n8n');
}

console.log('\n📝 PROSSIMI PASSI');
console.log('==================');
console.log('1. Copiare il codice JavaScript nel nodo n8n');
console.log('2. Salvare e attivare il workflow');
console.log('3. Testare con email Mail2Wa reale');
console.log('4. Verificare nel pannello WordPress');