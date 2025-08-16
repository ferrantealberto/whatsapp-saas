/**
 * JavaScript Admin per WhatsApp SaaS Plugin
 * ✅ COMPLETAMENTE FUNZIONALE - Tutte le sezioni operative
 */

(function($) {
    'use strict';

    let wspAjax = window.wsp_ajax || {};

    $(document).ready(function() {
        initDashboard();
        initEventHandlers();
    });

    function initDashboard() {
        if ($('.wsp-stats-grid').length) {
            loadDashboardStats();
            // Auto-refresh ogni 30 secondi
            setInterval(loadDashboardStats, 30000);
        }
    }

    function loadDashboardStats() {
        $.ajax({
            url: wspAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'wsp_get_stats',
                nonce: wspAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatsDisplay(response.data);
                }
            },
            error: function() {
                console.log('Errore nel caricamento statistiche');
            }
        });
    }

    function updateStatsDisplay(data) {
        const stats = data.stats || {};
        const credits = data.credits || 0;

        $('.wsp-stat-card').each(function() {
            const $card = $(this);
            const $number = $card.find('h3');
            const text = $card.find('p').text().toLowerCase();

            if (text.includes('totali')) {
                animateNumber($number, stats.total_numbers || 0);
            } else if (text.includes('oggi')) {
                animateNumber($number, stats.numbers_today || 0);
            } else if (text.includes('messaggi')) {
                animateNumber($number, stats.total_messages || 0);
            } else if (text.includes('crediti')) {
                animateNumber($number, credits);
            }
        });
    }

    function animateNumber($element, targetNumber) {
        const currentNumber = parseInt($element.text().replace(/[^0-9]/g, '')) || 0;
        const increment = Math.ceil((targetNumber - currentNumber) / 20);
        
        if (currentNumber !== targetNumber) {
            $element.text(number_format(currentNumber + increment));
            setTimeout(() => animateNumber($element, targetNumber), 50);
        } else {
            $element.text(number_format(targetNumber));
        }
    }

    function initEventHandlers() {
        // Test API connection
        $('#wsp-test-api').on('click', function(e) {
            e.preventDefault();
            testAPIConnection();
        });

        // Conferma azioni pericolose
        $('.wsp-confirm').on('click', function(e) {
            if (!confirm('Sei sicuro di voler continuare?')) {
                e.preventDefault();
            }
        });
    }

    function testAPIConnection() {
        const $button = $('#wsp-test-api');
        const originalText = $button.text();
        
        $button.prop('disabled', true).html('<span class="wsp-loading"></span> Testing...');

        fetch(wspAjax.site_url + '/wp-json/wsp/v1/ping')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $button.after('<span class="wsp-success"> ✅ API Attiva</span>');
                } else {
                    $button.after('<span class="wsp-error"> ❌ API Non Risponde</span>');
                }
            })
            .catch(error => {
                $button.after('<span class="wsp-error"> ❌ Errore: ' + error.message + '</span>');
            })
            .finally(() => {
                $button.prop('disabled', false).text(originalText);
                setTimeout(() => {
                    $button.siblings('.wsp-success, .wsp-error').remove();
                }, 5000);
            });
    }

    function showNotice(message, type = 'info') {
        const noticeClass = `wsp-notice ${type}`;
        const notice = $(`<div class="${noticeClass}" style="display:none;">${message}</div>`);
        
        $('.wrap h1').after(notice);
        notice.slideDown();
        
        setTimeout(() => {
            notice.slideUp(() => notice.remove());
        }, 5000);
    }

    function number_format(number) {
        return new Intl.NumberFormat('it-IT').format(number);
    }

    // Esporta funzioni globali
    window.wspTestAPI = function() {
        testAPIConnection();
    };

    window.wspSendWelcome = function(numberId) {
        $.ajax({
            url: wspAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'wsp_send_welcome_message',
                nonce: wspAjax.nonce,
                number_id: numberId
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Messaggio di benvenuto inviato!', 'success');
                } else {
                    showNotice('Errore nell\'invio: ' + response.message, 'error');
                }
            }
        });
    };

})(jQuery);