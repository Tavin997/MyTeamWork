/**
 * MyTeamWork - Register View Behaviors
 * Com integração AJAX para o backend
 */

;(function($, window, document) {
    'use strict';

    class RegisterBehavior {
        constructor() {
            this.cache = {
                form: $('#registerForm'),
                name: $('#registerName'),
                email: $('#registerEmail'),
                password: $('#registerPassword'),
                confirmPassword: $('#registerConfirmPassword'),
                togglePassword: $('#togglePassword'),
                toggleConfirmPassword: $('#toggleConfirmPassword'),
                termsCheck: $('#termsCheck'),
                feedback: $('#registerFeedback'),
                registerBtn: $('#registerBtn')
            };

            // Configuração da API
            this.apiConfig = {
                baseUrl: window.location.origin + '/api/index.php?route=',
                endpoints: {
                    register: 'auth/register'
                }
            };

            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // Toggle password visibility
            this.cache.togglePassword.on('click', () => {
                this.togglePasswordVisibility(this.cache.password);
            });

            this.cache.toggleConfirmPassword.on('click', () => {
                this.togglePasswordVisibility(this.cache.confirmPassword);
            });

            // Real-time validation
            this.cache.password.on('input', () => {
                this.validatePassword();
            });

            this.cache.confirmPassword.on('input', () => {
                this.validateConfirmPassword();
            });

            // Submit form
            this.cache.form.on('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });

            // Enter key navigation
            this.cache.name.on('keydown', (e) => {
                if (e.key === 'Enter') {
                    this.cache.email.focus();
                }
            });

            this.cache.email.on('keydown', (e) => {
                if (e.key === 'Enter') {
                    this.cache.password.focus();
                }
            });

            this.cache.password.on('keydown', (e) => {
                if (e.key === 'Enter') {
                    this.cache.confirmPassword.focus();
                }
            });

            this.cache.confirmPassword.on('keydown', (e) => {
                if (e.key === 'Enter') {
                    this.cache.termsCheck.focus();
                }
            });

            // Auto-focus on name field
            this.cache.name.focus();
        }

        togglePasswordVisibility(input) {
            const type = input.attr('type') === 'password' ? 'text' : 'password';
            input.attr('type', type);
            
            const toggle = input.closest('.password-wrapper').find('.password-toggle');
            const icon = toggle.find('i');
            icon.toggleClass('fa-eye fa-eye-slash');
        }

        validatePassword() {
            const password = this.cache.password.val().trim();
            const hint = this.cache.password.closest('.form-group').find('.form-hint');

            if (password.length === 0) {
                hint.removeClass('error success');
                hint.text('Mínimo 6 caracteres');
                this.cache.password.removeClass('is-valid is-invalid');
                return;
            }

            if (password.length < 6) {
                hint.addClass('error').removeClass('success');
                hint.text('A senha deve ter no mínimo 6 caracteres');
                this.cache.password.addClass('is-invalid').removeClass('is-valid');
            } else {
                hint.addClass('success').removeClass('error');
                hint.text('✓ Senha válida');
                this.cache.password.addClass('is-valid').removeClass('is-invalid');
            }

            const confirm = this.cache.confirmPassword.val().trim();
            if (confirm.length > 0) {
                this.validateConfirmPassword();
            }
        }

        validateConfirmPassword() {
            const password = this.cache.password.val().trim();
            const confirm = this.cache.confirmPassword.val().trim();
            const hint = this.cache.confirmPassword.closest('.form-group').find('.form-hint');

            if (confirm.length === 0) {
                hint.removeClass('error success');
                hint.text('');
                this.cache.confirmPassword.removeClass('is-valid is-invalid');
                return;
            }

            if (password !== confirm) {
                hint.addClass('error').removeClass('success');
                hint.text('As senhas não coincidem');
                this.cache.confirmPassword.addClass('is-invalid').removeClass('is-valid');
            } else {
                hint.addClass('success').removeClass('error');
                hint.text('✓ Senhas coincidem');
                this.cache.confirmPassword.addClass('is-valid').removeClass('is-invalid');
            }
        }

        /**
         * 🔥 ENVIO DE DADOS PARA O BACKEND
         * Método principal que faz a requisição AJAX
         */
        handleSubmit() {
            const name = this.cache.name.val().trim();
            const email = this.cache.email.val().trim();
            const password = this.cache.password.val().trim();
            const confirmPassword = this.cache.confirmPassword.val().trim();

            // Validação no frontend
            if (!name || !email || !password || !confirmPassword) {
                this.showFeedback('Por favor, preencha todos os campos.', 'danger');
                return;
            }

            if (name.length < 2) {
                this.showFeedback('Nome deve ter pelo menos 2 caracteres.', 'danger');
                return;
            }

            if (!this.isValidEmail(email)) {
                this.showFeedback('Por favor, insira um e-mail válido.', 'danger');
                return;
            }

            if (password.length < 6) {
                this.showFeedback('A senha deve ter no mínimo 6 caracteres.', 'danger');
                return;
            }

            if (password !== confirmPassword) {
                this.showFeedback('As senhas não coincidem.', 'danger');
                return;
            }

            if (!this.cache.termsCheck.is(':checked')) {
                this.showFeedback('Você precisa concordar com os Termos de Serviço.', 'danger');
                return;
            }

            // Mostra loading
            this.setLoading(true);

            // ============================================
            // 📤 ENVIO DOS DADOS VIA AJAX
            // ============================================
            const registerData = {
                nome: name,
                email: email,
                senha: password
            };

            console.log('📤 Enviando dados para o backend:', registerData);

            $.ajax({
                url: this.apiConfig.baseUrl + this.apiConfig.endpoints.register,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(registerData),
                dataType: 'json',
                timeout: 10000,

                // ✅ Sucesso
                success: (response) => {
                    console.log('✅ Resposta do backend:', response);
                    
                    if (response.success) {
                        // Salva o token JWT no localStorage
                        if (response.data && response.data.token) {
                            localStorage.setItem('jwt_token', response.data.token);
                            localStorage.setItem('user_data', JSON.stringify(response.data.user));
                        }
                        
                        this.showFeedback(response.message || 'Conta criada com sucesso!', 'success');
                        
                        // Redireciona para o dashboard
                        setTimeout(() => {
                            window.location.href = 'index.php?route=dashboard';
                        }, 1500);
                    } else {
                        this.showFeedback(response.message || 'Erro ao criar conta', 'danger');
                        this.setLoading(false);
                    }
                },

                // ❌ Erro na requisição
                error: (xhr, status, error) => {
                    console.error('❌ Erro na requisição:', { xhr, status, error });
                    
                    let errorMessage = 'Erro ao conectar com o servidor. Tente novamente.';
                    
                    // Tenta extrair mensagem de erro da resposta
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 409) {
                        errorMessage = 'Este e-mail já está cadastrado.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Erro de conexão. Verifique sua internet.';
                    }
                    
                    this.showFeedback(errorMessage, 'danger');
                    this.setLoading(false);
                }
            });
        }

        /**
         * 🛠️ Utilitários
         */
        isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        setLoading(isLoading) {
            const btn = this.cache.registerBtn;
            if (isLoading) {
                btn.text('Criando conta...').prop('disabled', true);
                btn.prepend('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>');
            } else {
                btn.text('Criar conta').prop('disabled', false);
                btn.find('.spinner-border').remove();
            }
        }

        showFeedback(message, type = 'info') {
            const alertClass = {
                success: 'alert-success',
                danger: 'alert-danger',
                warning: 'alert-warning',
                info: 'alert-info'
            };

            const iconMap = {
                success: 'fa-check-circle',
                danger: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };

            const html = `
                <div class="alert ${alertClass[type]} alert-dismissible fade show" role="alert">
                    <i class="fas ${iconMap[type]} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            this.cache.feedback.html(html).addClass('show');

            setTimeout(() => {
                this.cache.feedback.removeClass('show').empty();
            }, 5000);
        }
    }

    // Inicializa
    let instance = null;
    $(document).ready(() => {
        if (!instance) {
            instance = new RegisterBehavior();
        }
    });

})(jQuery, window, document);