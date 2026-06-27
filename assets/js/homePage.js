/**
 * MyTeamWork - HomePage View Behaviors
 * Version: 1.0.0
 * Description: APENAS comportamentos de apresentação e interações leves da VIEW
 * Toda lógica de negócio e comunicação com backend é responsabilidade do CONTROLLER
 */

;(function($, window, document, undefined) {
    'use strict';

    /**
     * ViewBehavior - Gerencia APENAS comportamentos de apresentação
     * Sem lógica de negócio, sem chamadas AJAX, sem manipulação de dados
     */
    class ViewBehavior {
        constructor() {
            // Cache de elementos da VIEW
            this.cache = {
                navbar: $('#mainNav'),
                navLinks: $('#navbarContent .nav-link'),
                featureCards: $('.feature-card'),
                pricingCards: $('.pricing-card'),
                contactForm: $('#contactForm')
            };

            // Configurações de apresentação
            this.config = {
                scrollThreshold: 100,
                animationDelay: 300
            };

            // Inicializa comportamentos da VIEW
            this.init();
        }

        /**
         * Inicializa todos os comportamentos da VIEW
         */
        init() {
            console.log('[VIEW] Inicializando comportamentos de apresentação...');
            this.bindViewEvents();
            this.handleScroll();
        }

        /**
         * Vincula APENAS eventos de apresentação
         */
        bindViewEvents() {
            // Scroll com throttle para performance
            $(window).on('scroll', () => this.throttle(this.handleScroll, 100)());

            // Navegação suave (apresentação)
            this.cache.navLinks.on('click', (e) => this.smoothScroll(e));

            // Efeito hover nos cards (apresentação)
            this.cache.featureCards.on('mouseenter', function() {
                $(this).find('.feature-icon').css('transform', 'scale(1.1)');
            }).on('mouseleave', function() {
                $(this).find('.feature-icon').css('transform', 'scale(1)');
            });

            // Validação básica de formulário (apresentação)
            if (this.cache.contactForm.length) {
                this.cache.contactForm.on('submit', (e) => {
                    const name = $('#contactName').val().trim();
                    const email = $('#contactEmail').val().trim();
                    const message = $('#contactMessage').val().trim();

                    if (!name || !email || !message) {
                        e.preventDefault();
                        this.showFieldFeedback('Por favor, preencha todos os campos.', 'warning');
                        return;
                    }

                    if (!this.isValidEmail(email)) {
                        e.preventDefault();
                        this.showFieldFeedback('Por favor, insira um e-mail válido.', 'warning');
                        return;
                    }

                    // VIEW: Apenas exibe feedback, não processa dados
                    this.showFieldFeedback('Mensagem enviada com sucesso!', 'success');
                    // O formulário seguirá seu fluxo normal para o Controller
                });
            }
        }

        /**
         * Scroll suave para âncoras (comportamento de VIEW)
         */
        smoothScroll(e) {
            e.preventDefault();
            const target = $(e.currentTarget.attr('href'));
            
            if (target.length) {
                const offset = target.offset().top - 76;
                $('html, body').animate({
                    scrollTop: offset
                }, 600, 'easeInOutQuad');
            }
        }

        /**
         * Efeito de scroll no navbar (apresentação)
         */
        handleScroll() {
            const scrollTop = $(window).scrollTop();
            const threshold = this.config.scrollThreshold;

            if (scrollTop > threshold) {
                this.cache.navbar
                    .addClass('shadow-lg')
                    .css('background', 'rgba(255, 255, 255, 0.98) !important');
            } else {
                this.cache.navbar
                    .removeClass('shadow-lg')
                    .css('background', 'rgba(255, 255, 255, 0.95) !important');
            }

            this.updateActiveLink(scrollTop);
        }

        /**
         * Atualiza link ativo baseado na seção visível (apresentação)
         */
        updateActiveLink(scrollTop) {
            const sections = ['home', 'features', 'pricing', 'contact'];
            let current = 'home';
            const windowHeight = $(window).height();

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

        /**
         * Validação de e-mail (utilitário da VIEW)
         */
        isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        /**
         * Feedback visual para o usuário (apresentação)
         */
        showFieldFeedback(message, type = 'info') {
            // Remove feedbacks anteriores
            $('.view-feedback').remove();

            const colors = {
                info: 'alert-info',
                success: 'alert-success',
                warning: 'alert-warning',
                danger: 'alert-danger'
            };

            const feedbackHtml = `
                <div class="view-feedback alert ${colors[type] || colors.info} alert-dismissible fade show mt-3" role="alert">
                    <i class="fas fa-${type === 'info' ? 'info-circle' : type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'times-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            // Adiciona feedback no formulário
            if (this.cache.contactForm.length) {
                this.cache.contactForm.prepend(feedbackHtml);
            }

            // Auto-fechar após 5 segundos
            setTimeout(() => {
                $('.view-feedback').alert('close');
            }, 5000);
        }

        /**
         * Throttle para otimizar eventos
         */
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

        /**
         * Limpeza de recursos da VIEW
         */
        destroy() {
            console.log('[VIEW] Limpando comportamentos...');
            $(window).off('scroll');
            this.cache.navLinks.off('click');
            if (this.cache.contactForm.length) {
                this.cache.contactForm.off('submit');
            }
        }
    }

    // ================================================
    // INSTÂNCIA DA VIEW (Apenas comportamentos de apresentação)
    // ================================================
    let viewInstance = null;

    $(document).ready(function() {
        if (!viewInstance) {
            viewInstance = new ViewBehavior();
        }
    });

    // Exposição para debug (apenas em desenvolvimento)
    if (window.DEV_MODE) {
        window.MyTeamWorkView = {
            instance: viewInstance,
            version: '1.0.0'
        };
    }

})(jQuery, window, document);