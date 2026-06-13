// ACADEXA — Main JavaScript

import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    // ─── Flash Message Auto-Dismiss ───────────────────────────────────────────
    document.querySelectorAll('.flash-alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        }, 5000);
    });

    // ─── Search Autocomplete ──────────────────────────────────────────────────
    const searchInput = document.getElementById('searchInput');
    const suggestions = document.getElementById('searchSuggestions');

    if (searchInput && suggestions) {
        let timeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(timeout);
            const q = searchInput.value.trim();
            if (q.length < 2) { suggestions.innerHTML = ''; suggestions.classList.add('d-none'); return; }
            timeout = setTimeout(async () => {
                try {
                    const res = await fetch(`/search/suggestions?q=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    if (data.length) {
                        suggestions.innerHTML = data.map(c =>
                            `<a href="${c.url}" class="dropdown-item">${c.title}</a>`
                        ).join('');
                        suggestions.classList.remove('d-none');
                    } else {
                        suggestions.classList.add('d-none');
                    }
                } catch (e) { suggestions.classList.add('d-none'); }
            }, 250);
        });

        document.addEventListener('click', e => {
            if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
                suggestions.classList.add('d-none');
            }
        });
    }

    // ─── Wishlist Toggle ──────────────────────────────────────────────────────
    document.querySelectorAll('[data-wishlist]').forEach(btn => {
        btn.addEventListener('click', async e => {
            e.preventDefault();
            const courseId = btn.dataset.wishlist;
            try {
                const res  = await fetch(`/wishlist/${courseId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                const data = await res.json();
                const icon = btn.querySelector('.wishlist-icon');
                if (icon) {
                    icon.textContent = data.added ? '❤️' : '🤍';
                }
                showToast(data.message, data.added ? 'success' : 'info');
            } catch (e) { console.error(e); }
        });
    });

    // ─── Notification Bell ────────────────────────────────────────────────────
    const notifBtn = document.getElementById('notifBtn');
    if (notifBtn) {
        notifBtn.addEventListener('click', () => {
            const badge = notifBtn.querySelector('.badge');
            if (badge) badge.remove();
        });
    }

    // ─── Sidebar Toggle (Mobile) ──────────────────────────────────────────────
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.getElementById('mainSidebar');
    const overlay       = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay?.classList.toggle('d-none');
        });
        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.add('d-none');
        });
    }

    // ─── Star Rating Input ────────────────────────────────────────────────────
    document.querySelectorAll('.star-rating-input').forEach(container => {
        const stars  = container.querySelectorAll('.star');
        const input  = container.querySelector('input[type="hidden"]');
        stars.forEach((star, i) => {
            star.addEventListener('click', () => {
                const val = i + 1;
                if (input) input.value = val;
                stars.forEach((s, j) => s.classList.toggle('filled', j <= i));
            });
            star.addEventListener('mouseenter', () => {
                stars.forEach((s, j) => s.classList.toggle('filled', j <= i));
            });
        });
        container.addEventListener('mouseleave', () => {
            const val = parseInt(input?.value || 0);
            stars.forEach((s, j) => s.classList.toggle('filled', j < val));
        });
    });

    // ─── Lesson Player: Mark as Complete ─────────────────────────────────────
    const completeBtn = document.getElementById('markCompleteBtn');
    if (completeBtn) {
        completeBtn.addEventListener('click', async () => {
            const lessonId = completeBtn.dataset.lessonId;
            try {
                const res  = await fetch(`/lessons/${lessonId}/complete`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                const data = await res.json();
                const bar = document.getElementById('progressBar');
                if (bar) bar.style.width = data.progress + '%';
                const pct = document.getElementById('progressPct');
                if (pct) pct.textContent = data.progress + '%';

                completeBtn.textContent = '✓ Completed';
                completeBtn.disabled = true;
                completeBtn.classList.replace('btn-primary', 'btn-success');

                if (data.completed && data.message) {
                    showToast('🎉 ' + data.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showToast('Lesson marked as complete!', 'success');
                }
            } catch (e) { console.error(e); }
        });
    }
});

// ─── Toast Utility ────────────────────────────────────────────────────────────
function showToast(message, type = 'info') {
    const colors = { success: '#10B981', info: '#0A2A5E', warning: '#F59E0B', error: '#EF4444' };
    const toast = document.createElement('div');
    toast.className = 'flash-alert alert alert-dismissible fade show';
    toast.style.cssText = `position:fixed;top:80px;right:1rem;z-index:9999;min-width:300px;box-shadow:0 8px 30px rgba(10,42,94,.18);border-radius:.5rem;border-left:4px solid ${colors[type] || colors.info};`;
    toast.innerHTML = `<span>${message}</span><button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 4000);
}

window.showToast = showToast;
