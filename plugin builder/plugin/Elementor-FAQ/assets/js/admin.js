/**
 * Elementor FAQ - Admin JavaScript
 * Version: 1.0.1
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        initShortcodeCopy();
        initQARepeater();
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

    function initQARepeater() {
        var $wrapper = $('#efaq-qa-items');
        var itemIndex = $wrapper.find('.efaq-qa-item').length;

        $('#efaq-add-item').on('click', function(e) {
            e.preventDefault();
            
            var template = `
                <div class="efaq-qa-item" data-index="${itemIndex}">
                    <div class="efaq-qa-header">
                        <span class="efaq-qa-number">${itemIndex + 1}.</span>
                        <button type="button" class="efaq-remove-item button">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                    <div class="efaq-qa-field">
                        <label>Question</label>
                        <textarea name="efaq_qa_items[${itemIndex}][question]" rows="2" placeholder="Enter your question here..."></textarea>
                    </div>
                    <div class="efaq-qa-field">
                        <label>Answer</label>
                        <textarea name="efaq_qa_items[${itemIndex}][answer]" rows="4" placeholder="Enter the answer here..."></textarea>
                    </div>
                </div>
            `;
            
            $wrapper.append(template);
            itemIndex++;
            updateRemoveButtons();
        });

        $(document).on('click', '.efaq-remove-item', function(e) {
            e.preventDefault();
            
            var $item = $(this).closest('.efaq-qa-item');
            $item.slideUp(200, function() {
                $(this).remove();
                reindexItems();
                updateRemoveButtons();
            });
        });

        function reindexItems() {
            $wrapper.find('.efaq-qa-item').each(function(index) {
                var $item = $(this);
                $item.attr('data-index', index);
                $item.find('.efaq-qa-number').text((index + 1) + '.');
                $item.find('textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
            itemIndex = $wrapper.find('.efaq-qa-item').length;
        }

        function updateRemoveButtons() {
            var $items = $wrapper.find('.efaq-qa-item');
            var count = $items.length;
            $items.find('.efaq-remove-item').toggle(count > 1);
        }

        updateRemoveButtons();
    }

})(jQuery);
