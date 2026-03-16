/**
 * Elementor FAQ Widget - Frontend JavaScript
 * Version: 1.0.0
 */
(function($) {
    'use strict';

    class ElementorFAQ {
        constructor($wrapper) {
            this.$wrapper = $wrapper;
            this.$items = $wrapper.find('.efaq-item');
            this.$searchInput = $wrapper.find('.efaq-search-input');
            this.$categoryTabs = $wrapper.find('.efaq-category-tab');
            this.$categorySelect = $wrapper.find('.efaq-category-select');
            this.$noResults = null;
            
            this.toggleMode = $wrapper.data('toggle-mode') || 'single';
            this.defaultExpand = $wrapper.data('default-expand') || 'none';
            this.animationSpeed = $wrapper.data('animation-speed') || 300;
            
            this.currentCategory = 'all';
            this.searchTerm = '';
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.handleDefaultExpand();
        }
        
        bindEvents() {
            this.$wrapper.on('click', '.efaq-item-question', (e) => {
                e.preventDefault();
                this.toggleItem($(e.currentTarget).closest('.efaq-item'));
            });
            
            if (this.$searchInput.length) {
                this.$searchInput.on('input', this.debounce(() => {
                    this.searchTerm = this.$searchInput.val().toLowerCase().trim();
                    this.filterItems();
                }, 300));
            }
            
            this.$categoryTabs.on('click', (e) => {
                e.preventDefault();
                const $tab = $(e.currentTarget);
                this.setActiveCategory($tab.data('category'));
                
                this.$categoryTabs.removeClass('active');
                $tab.addClass('active');
                
                if (this.$categorySelect.length) {
                    this.$categorySelect.val($tab.data('category'));
                }
            });
            
            if (this.$categorySelect.length) {
                this.$categorySelect.on('change', () => {
                    const category = this.$categorySelect.val();
                    this.setActiveCategory(category);
                    
                    this.$categoryTabs.removeClass('active');
                    this.$categoryTabs.filter('[data-category="' + category + '"]').addClass('active');
                });
            }
        }
        
        toggleItem($item) {
            const isOpen = $item.hasClass('active');
            
            if (this.toggleMode === 'single' && !isOpen) {
                this.$items.filter('.active').removeClass('active');
            }
            
            $item.toggleClass('active', !isOpen);
        }
        
        handleDefaultExpand() {
            if (this.defaultExpand === 'none') {
                return;
            }
            
            const visibleItems = this.$items.filter(':visible');
            
            if (this.defaultExpand === 'first' && visibleItems.length > 0) {
                visibleItems.first().addClass('active');
            } else if (this.defaultExpand === 'all') {
                visibleItems.addClass('active');
            }
        }
        
        setActiveCategory(category) {
            this.currentCategory = category;
            this.filterItems();
        }
        
        filterItems() {
            let hasResults = false;
            
            this.$items.each((index, item) => {
                const $item = $(item);
                const matchesSearch = this.matchesSearch($item);
                const matchesCategory = this.matchesCategory($item);
                
                if (matchesSearch && matchesCategory) {
                    $item.removeClass('hidden');
                    hasResults = true;
                } else {
                    $item.addClass('hidden');
                }
            });
            
            this.toggleNoResults(!hasResults);
            
            if (this.defaultExpand === 'first') {
                const $visibleItems = this.$items.filter(':visible:not(.hidden)');
                this.$items.removeClass('active');
                if ($visibleItems.length > 0) {
                    $visibleItems.first().addClass('active');
                }
            }
        }
        
        matchesSearch($item) {
            if (!this.searchTerm) {
                return true;
            }
            
            const title = $item.find('.efaq-item-title').text().toLowerCase();
            const answer = $item.find('.efaq-item-answer-content').text().toLowerCase();
            
            return title.includes(this.searchTerm) || answer.includes(this.searchTerm);
        }
        
        matchesCategory($item) {
            if (this.currentCategory === 'all') {
                return true;
            }
            
            return $item.hasClass('efaq-category-' + this.currentCategory);
        }
        
        toggleNoResults(show) {
            if (show) {
                if (!this.$noResults) {
                    this.$noResults = $('<div class="efaq-no-results"></div>');
                    this.$noResults.text(
                        (window.elementorFAQ && window.elementorFAQ.i18n && window.elementorFAQ.i18n.noResults) 
                        || 'No FAQs found matching your search.'
                    );
                    this.$wrapper.find('.efaq-items').append(this.$noResults);
                }
                this.$noResults.show();
            } else if (this.$noResults) {
                this.$noResults.hide();
            }
        }
        
        debounce(func, wait) {
            let timeout;
            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
    }
    
    $(document).ready(function() {
        $('.efaq-wrapper').each(function() {
            new ElementorFAQ($(this));
        });
    });
    
    $(window).on('elementor/frontend/init', function() {
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/elementor_faq.default', function($scope) {
                const $wrapper = $scope.find('.efaq-wrapper');
                if ($wrapper.length) {
                    new ElementorFAQ($wrapper);
                }
            });
        }
    });
    
})(jQuery);
