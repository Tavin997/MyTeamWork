/**
 * MyTeamWork - HomePage View Behaviors
 * Apenas interações leves de apresentação
 */

;(function($, window, document) {
    'use strict';

    class ViewBehavior {
        constructor() {
            this.cache = {
                navbar: $('#mainNav'),
                navLinks: $('#navbarContent .nav-link')
            };
            
            this.init();
        }

        init() {
            this.handleScroll();
            this.bindEvents();
        }

        bindEvents() {
            // Scroll com throttle
            $(window).on('scroll', () => this.throttle(this.handleScroll, 100)());

            // Navegação suave
            this.cache.navLinks.on('click', (e) => this.smoothScroll(e));
        }

        handleScroll() {
            const scrollTop = $(window).scrollTop();
            
            if (scrollTop > 60) {
                this.cache.navbar.css({
                    'background': 'rgba(255, 255, 255, 0.98)',
                    'border-bottom': '1px solid #E5E7EB'
                });
            } else {
                this.cache.navbar.css({
                    'background': 'rgba(255, 255, 255, 0.92)',
                    'border-bottom': '1px solid rgba(229, 231, 235, 0.5)'
                });
            }

            this.updateActiveLink(scrollTop);
        }

        updateActiveLink(scrollTop) {
            const sections = ['home', 'features', 'pricing', 'contact'];
            let current = 'home';

            for (let section of sections) {
                const element = $(`#${section}`);
                if (element.length) {
                    const offset = element.offset().top - 100;
                    if (scrollTop >= offset) {
                        current = section;
                    }
                }
            }

            this.cache.navLinks.removeClass('active');
            $(`#navbarContent .nav-link[href="#${current}"]`).addClass('active');
        }

        smoothScroll(e) {
            e.preventDefault();
            const target = $(e.currentTarget.attr('href'));
            
            if (target.length) {
                const offset = target.offset().top - 72;
                $('html, body').animate({
                    scrollTop: offset
                }, 500, 'easeInOutQuad');
            }
        }

        throttle(callback, limit) {
            let timeout = null;
            return function(...args) {
                if (!timeout) {
                    timeout = setTimeout(() => {
                        callback.apply(this, args);
                        timeout = null;
                    }, limit);
                }
            };
        }
    }

    // Inicializa
    let instance = null;
    $(document).ready(() => {
        if (!instance) {
            instance = new ViewBehavior();
        }
    });

})(jQuery, window, document);