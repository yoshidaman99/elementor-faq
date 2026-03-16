/**
 * Elementor FAQ - Admin JavaScript
 * Version: 1.0.0
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize admin functionality
        initShortcodeCopy();
    });

    function initShortcodeCopy() {
        $('.efaq-shortcode-display').on('click', function() {
            var $this = $(this);
            var text = $this.text();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopyFeedback($this);
                });
            } else {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showCopyFeedback($this);
            }
        });
    }

    function showCopyFeedback($element) {
        var originalBg = $element.css('background-color');
        $element.css('background-color', '#d4edda');
        setTimeout(function() {
            $element.css('background-color', originalBg);
        }, 500);
    }

})(jQuery);
