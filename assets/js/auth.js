/**
 * MyTeamWork - Auth Manager
 * Gerencia autenticação e tokens JWT
 */

;(function($, window, document) {
    'use strict';

    class AuthManager {
        constructor() {
            this.tokenKey = 'jwt_token';
            this.userKey = 'user_data';
        }

        /**
         * Verifica se usuário está autenticado
         */
        isAuthenticated() {
            const token = this.getToken();
            return token !== null && token !== undefined;
        }

        /**
         * Obtém token JWT
         */
        getToken() {
            return localStorage.getItem(this.tokenKey);
        }

        /**
         * Obtém dados do usuário
         */
        getUser() {
            const userData = localStorage.getItem(this.userKey);
            return userData ? JSON.parse(userData) : null;
        }

        /**
         * Salva token e dados do usuário
         */
        setAuth(token, user) {
            localStorage.setItem(this.tokenKey, token);
            localStorage.setItem(this.userKey, JSON.stringify(user));
        }

        /**
         * Remove autenticação (logout)
         */
        logout() {
            localStorage.removeItem(this.tokenKey);
            localStorage.removeItem(this.userKey);
            window.location.href = 'index.php?route=login';
        }

        /**
         * Adiciona token ao header das requisições AJAX
         */
        setupAjaxInterceptor() {
            $(document).ajaxSend((event, jqxhr, settings) => {
                const token = this.getToken();
                if (token) {
                    jqxhr.setRequestHeader('Authorization', 'Bearer ' + token);
                }
            });
        }

        /**
         * Redireciona para login se não autenticado
         */
        requireAuth() {
            if (!this.isAuthenticated()) {
                window.location.href = 'index.php?route=login';
                return false;
            }
            return true;
        }
    }

    // Instância global
    window.MyTeamWork = window.MyTeamWork || {};
    window.MyTeamWork.Auth = new AuthManager();

    // Configura interceptor para todas as requisições AJAX
    $(document).ready(() => {
        window.MyTeamWork.Auth.setupAjaxInterceptor();
    });

})(jQuery, window, document);