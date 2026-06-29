/**
 * MyTeamWork - Login View Behaviors
 * Com integração AJAX para o backend
 */

;(function($, window, document) {
    'use strict';

    class LoginBehavior {
        constructor() {
            this.cache = {
                form: $('#loginForm'),
                email: $('#loginEmail'),
                password: $('#loginPassword'),
                togglePassword: $('#togglePassword'),
                rememberMe: $('#rememberMe'),
                feedback: $('#loginFeedback'),
                loginBtn: $('#loginBtn')
            };

            // Configuração da API
            this.apiConfig = {
                baseUrl: window.location.origin + '/api/index.php?route=',
                endpoints: {
                    login: 'auth/login'
                }
            };

            this.init();
        }

        init() {
            this.bindEvents();
            this.checkRemembered();
        }

        bindEvents() {
            // Toggle password visibility
            this.cache.togglePassword.on('click', () => {
                this.togglePasswordVisibility(this.cache.password);
            });

            // Submit form
            this.cache.form.on('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });

            // Auto-focus on email field
            this.cache.email.focus();

            // Enter key to submit
            this.cache.password.on('keydown', (e) => {
                if (e.key === 'Enter') {
                    this.cache.form.submit();
                }
            });
        }

        togglePasswordVisibility(input) {
            const type = input.attr('type') === 'password' ? 'text' : 'password';
            input.attr('type', type);
            
            const icon = this.cache.togglePassword.find('i');
            icon.toggleClass('fa-eye fa-eye-slash');
        }

        checkRemembered() {
            const remembered = localStorage.getItem('remembered_email');
            if (remembered) {
                this.cache.email.val(remembered);
                this.cache.rememberMe.prop('checked', true);
            }
        }

        /**
         * 🔥 ENVIO DE DADOS PARA O BACKEND
         * Método principal que faz a requisição AJAX
         */
        handleSubmit() {
            const email = this.cache.email.val().trim();
            const password = this.cache.password.val().trim();

            // Validação no frontend
            if (!email || !password) {
                this.showFeedback('Por favor, preencha todos os campos.', 'danger');
                return;
            }

            if (!this.isValidEmail(email)) {
                this.showFeedback('Por favor, insira um e-mail válido.', 'danger');
                return;
            }

            // Salva email se "Lembrar-me" estiver marcado
            if (this.cache.rememberMe.is(':checked')) {
                localStorage.setItem('remembered_email', email);
            } else {
                localStorage.removeItem('remembered_email');
            }

            // Mostra loading
            this.setLoading(true);

            // ============================================
            // 📤 ENVIO DOS DADOS VIA AJAX
            // ============================================
            const loginData = {
                email: email,
                senha: password
            };

            console.log('📤 Enviando dados para o backend:', loginData);

            $.ajax({
                url: this.apiConfig.baseUrl + this.apiConfig.endpoints.login,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(loginData),
                dataType: 'json',
                timeout: 10000, // 10 segundos
                
                // ✅ Sucesso
                success: (response) => {
                    console.log('✅ Resposta do backend:', response);
                    
                    if (response.success) {
                        // Salva o token JWT no localStorage
                        if (response.data && response.data.token) {
                            localStorage.setItem('jwt_token', response.data.token);
                            localStorage.setItem('user_data', JSON.stringify(response.data.user));
                        }
                        
                        this.showFeedback(response.message || 'Login realizado com sucesso!', 'success');
                        
                        // Redireciona para o dashboard
                        setTimeout(() => {
                            window.location.href = 'index.php?route=dashboard';
                        }, 1500);
                    } else {
                        this.showFeedback(response.message || 'Erro ao fazer login', 'danger');
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
                    } else if (xhr.status === 401) {
                        errorMessage = 'Credenciais inválidas. Verifique seu e-mail e senha.';
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
            const btn = this.cache.loginBtn;
            if (isLoading) {
                btn.text('Entrando...').prop('disabled', true);
                // Adiciona spinner
                btn.prepend('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>');
            } else {
                btn.text('Entrar').prop('disabled', false);
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

            // Auto-fecha após 5 segundos
            setTimeout(() => {
                this.cache.feedback.removeClass('show').empty();
            }, 5000);
        }
    }

    // Inicializa
    let instance = null;
    $(document).ready(() => {
        if (!instance) {
            instance = new LoginBehavior();
        }
    });

})(jQuery, window, document);