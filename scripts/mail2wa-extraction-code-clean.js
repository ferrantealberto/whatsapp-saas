// MAIL2WA FIX - CODICE PULITO PER N8N 
// Versione corretta senza errori di sintassi

const items = $input.all();
const results = [];

// Pattern migliorati per Mail2Wa
const patterns = [
  /(?:\+39[\s\-\.]?)?3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g,
  /\b39[0-9]{10,12}\b/g,
  /\b3[0-9]{9}\b/g,
  /\+[1-9]{1}[0-9]{1,3}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}/g,
  /3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g,
  /([0-9]{10,12})@mail2wa\.it/g
];

for (const item of items) {
  try {
    const emailContent = item.json.bodyText || 
                        item.json.bodyHtml ||
                        item.json.snippet ||
                        item.json.payload?.parts?.[0]?.body?.data ||
                        JSON.stringify(item.json);
    
    const subject = item.json.subject || 'No Subject';
    const sender = item.json.from || 'Unknown Sender';
    const replyTo = item.json.replyTo || item.json['reply-to'] || '';
    const messageId = item.json.id;
    
    console.log('Processing email: ' + subject);
    console.log('Reply-To: ' + replyTo);
    
    const foundNumbers = new Set();
    const searchTexts = [emailContent, subject, replyTo];
    
    searchTexts.forEach((text, index) => {
      if (text) {
        const textType = ['content', 'subject', 'reply-to'][index];
        console.log('Searching in ' + textType + ': ' + text.substring(0, 100) + '...');
        
        patterns.forEach((pattern, patternIndex) => {
          const matches = text.match(pattern);
          if (matches) {
            matches.forEach(match => {
              let cleanNumber = match.replace(/[\s\-\.@mail2wa\.it]/g, '');
              
              if (match.includes('@mail2wa.it')) {
                cleanNumber = match.split('@')[0];
              }
              
              if (cleanNumber.startsWith('39') && cleanNumber.length >= 11) {
                foundNumbers.add(cleanNumber);
              } else if (cleanNumber.startsWith('3') && cleanNumber.length === 10) {
                foundNumbers.add('39' + cleanNumber);
              } else if (cleanNumber.length >= 10) {
                foundNumbers.add(cleanNumber);
              }
              
              console.log('Pattern ' + patternIndex + ' found in ' + textType + ': ' + match + ' -> ' + cleanNumber);
            });
          }
        });
      }
    });
    
    const extractedNumbers = Array.from(foundNumbers).filter(number => {
      const isValid = /^39[0-9]{10,12}$/.test(number) || 
                     /^3[0-9]{9}$/.test(number) ||
                     /^\+?[1-9][0-9]{8,14}$/.test(number);
      
      if (!isValid) {
        console.log('Invalid number format: ' + number);
      }
      
      return isValid;
    });
    
    console.log('Total numbers extracted: ' + extractedNumbers.length);
    console.log('Numbers: ' + JSON.stringify(extractedNumbers));
    
    let sourceType = 'email';
    if (replyTo.includes('mail2wa.it') || subject.toLowerCase().includes('mail2wa')) {
      sourceType = 'mail2wa';
    }
    
    const result = {
      email_id: messageId,
      sender_email: sender,
      subject: subject,
      reply_to: replyTo,
      extracted_numbers: extractedNumbers,
      numbers_count: extractedNumbers.length,
      source: sourceType,
      timestamp: new Date().toISOString(),
      processing_node: 'extract_whatsapp_numbers_fixed'
    };
    
    if (extractedNumbers.length > 0) {
      results.push({
        json: result
      });
      
      console.log('Successfully extracted ' + extractedNumbers.length + ' numbers from ' + sourceType + ' email');
    } else {
      console.log('No valid numbers found in email: ' + subject);
    }
    
  } catch (error) {
    console.error('Error processing email: ' + error.message);
    
    results.push({
      json: {
        email_id: item.json.id || 'unknown',
        sender_email: item.json.from || 'unknown',
        subject: item.json.subject || 'unknown',
        extracted_numbers: [],
        numbers_count: 0,
        error: error.message,
        timestamp: new Date().toISOString(),
        processing_node: 'extract_whatsapp_numbers_fixed'
      }
    });
  }
}

console.log('Total results: ' + results.length);
return results;