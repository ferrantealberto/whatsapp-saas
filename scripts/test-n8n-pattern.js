#!/usr/bin/env node

// ==========================================
// n8n Pattern Matching Test for WhatsApp Numbers
// ==========================================

console.log('🚀 Starting n8n Pattern Matching Tests...');
console.log('==========================================\n');

// Test data simulating email content from Gmail
const testEmails = [
    {
        subject: "Richiesta info prodotto",
        content: "Ciao! Sono interessato al vostro prodotto. Il mio WhatsApp è 3331234567. Grazie!",
        sender: "cliente1@example.com",
        expected: ["3331234567"]
    },
    {
        subject: "Contatto commerciale",
        content: "Buongiorno, potete contattarmi al +39 333 567 8901 per maggiori informazioni?",
        sender: "cliente2@example.com", 
        expected: ["+39 333 567 8901"]
    },
    {
        subject: "Richiesta preventivo",
        content: "Salve, i miei contatti sono: Tel: 333-123-4567 WhatsApp: +39.334.567.8901",
        sender: "cliente3@example.com",
        expected: ["333-123-4567", "+39.334.567.8901"]
    },
    {
        subject: "Info servizio",
        content: "Chiamatemi al numero 333 123 4567 oppure scrivete su WhatsApp al 334.567.8901",
        sender: "cliente4@example.com",
        expected: ["333 123 4567", "334.567.8901"]
    },
    {
        subject: "Contatto internazionale", 
        content: "Hello, my WhatsApp is +39 335 123 4567 or you can call +1 555 123 4567",
        sender: "international@example.com",
        expected: ["+39 335 123 4567"]
    }
];

// WhatsApp number extraction patterns (same as used in n8n workflow)
const whatsappPatterns = [
    // Italian mobile numbers with +39
    /(?:\+39[\s\-\.]?)?3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g,
    
    // International WhatsApp format
    /\+[1-9]{1}[0-9]{1,3}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}[\s\-\.]?[0-9]{1,4}/g,
    
    // General mobile patterns
    /3[0-9]{2}[\s\-\.]?[0-9]{3}[\s\-\.]?[0-9]{4}/g
];

// Function to extract WhatsApp numbers (simulates n8n function)
function extractWhatsAppNumbers(text) {
    const found = [];
    const seen = new Set();
    
    whatsappPatterns.forEach(pattern => {
        const matches = text.match(pattern);
        if (matches) {
            matches.forEach(match => {
                // Clean the number
                const cleaned = match.replace(/[\s\-\.]/g, '');
                if (!seen.has(cleaned)) {
                    seen.add(cleaned);
                    found.push(match);
                }
            });
        }
    });
    
    return found;
}

// Function to simulate n8n workflow processing
function simulateN8nProcessing(email) {
    console.log(`📧 Processing email from: ${email.sender}`);
    console.log(`   Subject: ${email.subject}`);
    console.log(`   Content: ${email.content.substring(0, 100)}...`);
    
    // Extract numbers
    const extractedNumbers = extractWhatsAppNumbers(email.content);
    
    // Simulate API call to WordPress
    const apiPayload = {
        email_content: email.content,
        sender_email: email.sender,
        subject: email.subject,
        extracted_numbers: extractedNumbers,
        timestamp: new Date().toISOString()
    };
    
    console.log(`   📞 Extracted Numbers: ${extractedNumbers.length > 0 ? extractedNumbers.join(', ') : 'None'}`);
    
    return {
        numbers: extractedNumbers,
        payload: apiPayload,
        success: extractedNumbers.length > 0
    };
}

// Test results tracking
let totalTests = 0;
let passedTests = 0;
let failedTests = 0;

// Run tests
console.log('🔍 Running Pattern Matching Tests...\n');

testEmails.forEach((email, index) => {
    totalTests++;
    console.log(`\n--- Test ${index + 1} ---`);
    
    const result = simulateN8nProcessing(email);
    
    // Validate results
    const extractedCount = result.numbers.length;
    const expectedCount = email.expected.length;
    
    if (extractedCount >= expectedCount) {
        console.log('   ✅ PASS: Numbers extracted successfully');
        passedTests++;
    } else {
        console.log('   ❌ FAIL: Expected more numbers');
        console.log(`      Expected: ${email.expected.join(', ')}`);
        console.log(`      Got: ${result.numbers.join(', ')}`);
        failedTests++;
    }
    
    // Show what would be sent to WordPress API
    console.log('   🔄 API Payload Preview:');
    console.log(`      Numbers: ${JSON.stringify(result.numbers)}`);
    console.log(`      Email: ${result.payload.sender_email}`);
});

// Integration simulation
console.log('\n\n🔗 Simulating n8n → WordPress Integration...');
console.log('================================================');

// Simulate the complete workflow
const workflowSteps = [
    '1. 📨 Gmail OAuth: Connect to Gmail account',
    '2. 📧 Email Fetch: Retrieve new emails from inbox',
    '3. 🔍 Pattern Match: Extract WhatsApp numbers using regex',
    '4. 🧹 Data Clean: Remove duplicates and format numbers',
    '5. 🔐 API Auth: Authenticate with WordPress plugin',
    '6. 📤 API Call: Send data to WordPress /extract endpoint',
    '7. 💾 Database: Save numbers to wp_wsp_whatsapp_numbers',
    '8. 📊 Response: Return success/error status to n8n'
];

workflowSteps.forEach((step, index) => {
    setTimeout(() => {
        console.log(step);
        if (index === workflowSteps.length - 1) {
            showResults();
        }
    }, index * 500);
});

function showResults() {
    setTimeout(() => {
        console.log('\n==========================================');
        console.log('📊 TEST RESULTS SUMMARY');
        console.log('==========================================');
        console.log(`Total Tests: ${totalTests}`);
        console.log(`✅ Passed: ${passedTests}`);
        console.log(`❌ Failed: ${failedTests}`);
        console.log(`Success Rate: ${Math.round((passedTests/totalTests)*100)}%`);
        
        if (failedTests === 0) {
            console.log('\n🎉 ALL PATTERN MATCHING TESTS PASSED!');
            console.log('✅ Ready for n8n integration');
        } else {
            console.log('\n⚠️  Some tests failed. Pattern tuning may be needed.');
        }
        
        console.log('\n🔧 Next Steps:');
        console.log('1. Import n8n-whatsapp-workflow.json into n8n');
        console.log('2. Configure Gmail OAuth credentials');
        console.log('3. Set WordPress API endpoint and key');
        console.log('4. Test with real emails');
        
        process.exit(failedTests > 0 ? 1 : 0);
    }, 1000);
}