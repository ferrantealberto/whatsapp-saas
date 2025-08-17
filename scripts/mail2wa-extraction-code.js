// CODICE COMPLETO PER IL NODO "Extract WhatsApp Numbers (FIXED)" IN N8N
// Questo codice deve essere copiato nel nodo JavaScript di n8n

// UPDATED PATTERN MATCHING FOR MAIL2WA EMAILS
// Extract WhatsApp numbers from email content with improved patterns

const items = $input.all();
const results = [];

// IMPROVED WhatsApp number patterns (includes Mail2Wa format)
const patterns = [
  // Italian mobile WITH +39 prefix and separators
  /(?:\+39[\s\-\.]?)?3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g,
  
  // Italian mobile WITHOUT separators (Mail2Wa format) - FIXED!
  /\b39[0-9]{10,12}\b/g,
  
  // Standard Italian mobile (10 digits starting with 3)  
  /\b3[0-9]{9}\b/g,
  
  // International WhatsApp format
  /\+[1-9]{1}[0-9]{1,3}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}/g,
  
  // General mobile with separators
  /3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g,
  
  // Mail2Wa Reply-To format - NEW!
  /([0-9]{10,12})@mail2wa\.it/g
];

for (const item of items) {
  try {
    // Get email content from different possible fields
    const emailContent = item.json.bodyText || 
                        item.json.bodyHtml ||
                        item.json.snippet ||
                        item.json.payload?.parts?.[0]?.body?.data ||
                        JSON.stringify(item.json);
    
    const subject = item.json.subject || 'No Subject';
    const sender = item.json.from || 'Unknown Sender';
    const replyTo = item.json.replyTo || item.json['reply-to'] || ''; // Check Reply-To header
    const messageId = item.json.id;
    
    console.log(`Processing email: ${subject}`);
    console.log(`Reply-To: ${replyTo}`);
    
    // Extract phone numbers using all patterns
    const foundNumbers = new Set();
    
    // Search in content, subject, and reply-to
    const searchTexts = [emailContent, subject, replyTo];
    
    searchTexts.forEach((text, index) => {
      if (text) {
        const textType = ['content', 'subject', 'reply-to'][index];
        console.log(`Searching in ${textType}: ${text.substring(0, 200)}...`);
        
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
                // Keep as is for Mail2Wa format
                foundNumbers.add(cleanNumber);
              } else if (cleanNumber.startsWith('3') && cleanNumber.length === 10) {
                // Add 39 prefix for standard Italian mobile
                foundNumbers.add('39' + cleanNumber);
              } else if (cleanNumber.length >= 10) {
                foundNumbers.add(cleanNumber);
              }
              
              console.log(`Pattern ${patternIndex} found in ${textType}: ${match} -> ${cleanNumber}`);
            });
          }
        });
      }
    });
    
    // Convert to array and validate
    const extractedNumbers = Array.from(foundNumbers).filter(number => {
      // Validate number format
      const isValid = /^39[0-9]{10,12}$/.test(number) || 
                     /^3[0-9]{9}$/.test(number) ||
                     /^\+?[1-9][0-9]{8,14}$/.test(number);
      
      if (!isValid) {
        console.log(`Invalid number format: ${number}`);
      }
      
      return isValid;
    });
    
    console.log(`Total numbers extracted: ${extractedNumbers.length}`);
    console.log(`Numbers: ${JSON.stringify(extractedNumbers)}`);
    
    // Determine source type
    let sourceType = 'email';
    if (replyTo.includes('mail2wa.it') || subject.toLowerCase().includes('mail2wa')) {
      sourceType = 'mail2wa';
    }
    
    // Create result object
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
    
    // Only add to results if we found numbers
    if (extractedNumbers.length > 0) {
      results.push({
        json: result
      });
      
      console.log(`✅ Successfully extracted ${extractedNumbers.length} numbers from ${sourceType} email`);
    } else {
      console.log(`⚠️ No valid numbers found in email: ${subject}`);
    }
    
  } catch (error) {
    console.error(`Error processing email: ${error.message}`);
    
    // Add error result
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

console.log(`Total results: ${results.length}`);
return results;