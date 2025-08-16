/**
 * WhatsApp SaaS Pro - Admin Scripts
 */

(function($) {
    'use strict';

    // Inizializzazione
    $(document).ready(function() {
        initAdminFunctions();
    });

    function initAdminFunctions() {
        // Copy to Clipboard
        window.copyToClipboard = function(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ Copiato negli appunti!');
            }).catch(() => {
                // Fallback per browser più vecchi
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('✅ Copiato negli appunti!');
            });
        };
    }

})(jQuery);
