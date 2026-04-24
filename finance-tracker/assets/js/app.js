/**
 * File: assets/js/app.js
 * Purpose: General UI interactions for Finance Tracker
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ── Mobile sidebar toggle ─────────────────────────────
    const sidebar     = document.querySelector('.ft-sidebar');
    const navToggler  = document.querySelector('.navbar-toggler');

    if (navToggler && sidebar) {
        navToggler.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992 &&
                sidebar.classList.contains('show') &&
                !sidebar.contains(e.target) &&
                !navToggler.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    }

    // ── Auto-dismiss alerts after 4 seconds ──────────────
    document.querySelectorAll('.alert.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 4000);
    });

    // ── Confirm delete via data attributes ────────────────
    // Usage: <button data-confirm="Are you sure?" data-form="formId">
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm(btn.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // ── Number input: prevent negative values ─────────────
    document.querySelectorAll('input[type="number"][min="0"]').forEach(input => {
        input.addEventListener('input', () => {
            if (parseFloat(input.value) < 0) input.value = '';
        });
    });

    // ── Highlight active nav link in sidebar ─────────────
    const currentPath = window.location.pathname;
    document.querySelectorAll('.ft-nav-link').forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('/').pop())) {
            link.classList.add('active');
        }
    });

    // ── Tooltip initialization ────────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // ── Form loading state ────────────────────────────────
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                const original = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';
                // Re-enable after 5s as fallback
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }, 5000);
            }
        });
    });

});

/**
 * Toggle password visibility.
 * @param {string} inputId - The ID of the password input
 */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const eye   = document.getElementById(inputId + '-eye');
    if (!input) return;
    if (input.type === 'password') {
        input.type    = 'text';
        if (eye) eye.className = 'bi bi-eye-slash';
    } else {
        input.type    = 'password';
        if (eye) eye.className = 'bi bi-eye';
    }
}
