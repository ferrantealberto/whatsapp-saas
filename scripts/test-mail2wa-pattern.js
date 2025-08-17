// TEST SPECIFICO PER PATTERN MAIL2WA
// Testa i pattern regex per estrarre numeri dalle email Mail2Wa

console.log('🧪 TEST PATTERN MAIL2WA');
console.log('========================');

// Email di test reale fornita dall'utente
const testEmail = {
  replyTo: '393511845192@mail2wa.it',
  subject: 'Test Mail2Wa',
  content: 'Email contenente il numero 393511845192 dal sistema Mail2Wa'
};

console.log('📧 Dati email di test:');
console.log('- Reply-To:', testEmail.replyTo);
console.log('- Subject:', testEmail.subject);
console.log('- Content:', testEmail.content);

// Pattern regex migliorati
const improvedPatterns = [
  {
    name: 'Italian mobile WITH +39 prefix and separators',
    regex: /(?:\+39[\s\-\.]?)?3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g
  },
  {
    name: 'Italian mobile WITHOUT separators (Mail2Wa format)',
    regex: /\b39[0-9]{10,12}\b/g
  },
  {
    name: 'Standard Italian mobile (10 digits starting with 3)',
    regex: /\b3[0-9]{9}\b/g
  },
  {
    name: 'International WhatsApp format',
    regex: /\+[1-9]{1}[0-9]{1,3}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}/g
  },
  {
    name: 'General mobile with separators',
    regex: /3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g
  },
  {
    name: 'Mail2Wa Reply-To format',
    regex: /([0-9]{10,12})@mail2wa\.it/g
  }
];

console.log('\n🔍 TESTING PATTERN EXTRACTION');
console.log('==============================');

const allFoundNumbers = new Set();
let totalMatches = 0;

// Testa ogni campo dell'email
const searchFields = [
  { name: 'Reply-To', value: testEmail.replyTo },
  { name: 'Subject', value: testEmail.subject },
  { name: 'Content', value: testEmail.content }
];

searchFields.forEach(field => {
  console.log(`\n📍 Testing field: ${field.name}`);
  console.log(`   Value: "${field.value}"`);
  
  improvedPatterns.forEach((pattern, index) => {
    const matches = field.value.match(pattern.regex);
    if (matches) {
      console.log(`   ✅ Pattern ${index}: ${pattern.name}`);
      matches.forEach(match => {
        totalMatches++;
        console.log(`      Found: "${match}"`);
        
        // Clean the number
        let cleanNumber = match.replace(/[\s\-\.@mail2wa\.it]/g, '');
        
        // Handle Reply-To format (extract number before @)
        if (match.includes('@mail2wa.it')) {
          cleanNumber = match.split('@')[0];
        }
        
        // Normalize Italian numbers
        if (cleanNumber.startsWith('39') && cleanNumber.length >= 11) {
          allFoundNumbers.add(cleanNumber);
        } else if (cleanNumber.startsWith('3') && cleanNumber.length === 10) {
          allFoundNumbers.add('39' + cleanNumber);
        } else if (cleanNumber.length >= 10) {
          allFoundNumbers.add(cleanNumber);
        }
        
        console.log(`      Cleaned: "${cleanNumber}"`);
      });
    } else {
      console.log(`   ❌ Pattern ${index}: No matches`);
    }
  });
});

// Validate extracted numbers
const validNumbers = Array.from(allFoundNumbers).filter(number => {
  const isValid = /^39[0-9]{10,12}$/.test(number) || 
                 /^3[0-9]{9}$/.test(number) ||
                 /^\+?[1-9][0-9]{8,14}$/.test(number);
  return isValid;
});

console.log('\n📊 RISULTATI FINALI');
console.log('===================');
console.log(`📈 Total matches found: ${totalMatches}`);
console.log(`📱 Unique numbers extracted: ${allFoundNumbers.size}`);
console.log(`✅ Valid numbers: ${validNumbers.length}`);
console.log(`📋 Numbers list: ${JSON.stringify(validNumbers)}`);

// Test specifico per il numero target
const targetNumber = '393511845192';
const isTargetFound = validNumbers.includes(targetNumber);

console.log('\n🎯 TARGET NUMBER TEST');
console.log('=====================');
console.log(`🔍 Looking for: ${targetNumber}`);
console.log(`${isTargetFound ? '✅' : '❌'} Found: ${isTargetFound}`);

if (isTargetFound) {
  console.log('🎉 SUCCESS! Mail2Wa pattern extraction is working correctly!');
} else {
  console.log('⚠️  FAILED! Mail2Wa pattern needs adjustment');
}

console.log('\n💾 EXPORT FOR N8N');
console.log('==================');
const exportData = {
  test_email: testEmail,
  patterns_tested: improvedPatterns.length,
  matches_found: totalMatches,
  valid_numbers: validNumbers,
  target_found: isTargetFound,
  success: isTargetFound && validNumbers.length > 0
};

console.log('Copy this object to n8n for testing:');
console.log(JSON.stringify(exportData, null, 2));