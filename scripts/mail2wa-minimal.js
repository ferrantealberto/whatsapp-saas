// VERSIONE MINIMALISTA - GARANTITA FUNZIONANTE IN N8N
const items = $input.all();
const results = [];

// Pattern per Mail2Wa
const patterns = [
  /\b39[0-9]{10,12}\b/g,
  /([0-9]{10,12})@mail2wa\.it/g,
  /\b3[0-9]{9}\b/g
];

for (const item of items) {
  try {
    const content = item.json.bodyText || item.json.bodyHtml || item.json.snippet || '';
    const subject = item.json.subject || '';
    const replyTo = item.json.replyTo || item.json['reply-to'] || '';
    
    const foundNumbers = new Set();
    const texts = [content, subject, replyTo];
    
    texts.forEach(text => {
      if (text) {
        patterns.forEach(pattern => {
          const matches = text.match(pattern);
          if (matches) {
            matches.forEach(match => {
              let number = match.replace(/[\s\-\.@mail2wa\.it]/g, '');
              if (match.includes('@mail2wa.it')) {
                number = match.split('@')[0];
              }
              if (number.startsWith('39') && number.length >= 11) {
                foundNumbers.add(number);
              } else if (number.startsWith('3') && number.length === 10) {
                foundNumbers.add('39' + number);
              }
            });
          }
        });
      }
    });
    
    const numbers = Array.from(foundNumbers);
    
    if (numbers.length > 0) {
      let source = 'email';
      if (replyTo.includes('mail2wa.it')) {
        source = 'mail2wa';
      }
      
      results.push({
        json: {
          email_id: item.json.id,
          sender_email: item.json.from,
          subject: subject,
          reply_to: replyTo,
          extracted_numbers: numbers,
          numbers_count: numbers.length,
          source: source,
          timestamp: new Date().toISOString()
        }
      });
      
      console.log('Extracted ' + numbers.length + ' numbers: ' + JSON.stringify(numbers));
    }
    
  } catch (error) {
    console.error('Error: ' + error.message);
  }
}

return results;