// js/main.js
// Modern Client Logic for TPS3R Gang Tani Pringsewu

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle
    const sidebar = document.querySelector('.sidebar');
    const menuToggle = document.getElementById('menu-toggle-btn');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // Close sidebar on click outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });

    // 2. Real-Time Transaction Point & Cash Estimator
    const categorySelect = document.getElementById('category_id');
    const weightInput = document.getElementById('weight');
    const pointsInput = document.getElementById('points_earned');
    const cashInput = document.getElementById('cash_earned');

    if (categorySelect && weightInput) {
        const calculateEstimates = () => {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                if (pointsInput) pointsInput.value = 0;
                if (cashInput) cashInput.value = 'Rp0';
                return;
            }
            
            const pointsPerKg = parseFloat(selectedOption.getAttribute('data-points')) || 0;
            const pricePerKg = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const weight = parseFloat(weightInput.value) || 0;
            
            // Calculate
            const pointsEarned = Math.round(weight * pointsPerKg);
            const cashEarned = Math.round(weight * pricePerKg);
            
            if (pointsInput) {
                pointsInput.value = pointsEarned;
            }
            if (cashInput) {
                // Format rupiah
                cashInput.value = 'Rp' + cashEarned.toLocaleString('id-ID');
            }
        };

        categorySelect.addEventListener('change', calculateEstimates);
        weightInput.addEventListener('input', calculateEstimates);
    }

    // 3. Modal Handlers
    window.openModal = (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    };

    window.closeModal = (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    };

    // Close modal on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal.active');
            if (activeModal) {
                activeModal.classList.remove('active');
            }
        }
    });

    // 4. Toast Notification System
    window.showToast = (message, type = 'success') => {
        // Create container if not exists
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        // Create toast
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let iconHtml = '✨';
        if (type === 'success') iconHtml = '✔️';
        if (type === 'error') iconHtml = '❌';

        toast.innerHTML = `
            <span class="toast-icon">${iconHtml}</span>
            <span class="toast-message">${message}</span>
        `;

        container.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.add('show');
        }, 50);

        // Remove toast
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 400);
        }, 4000);
    };

    // Check URL parameters for redirect alerts
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg')) {
        const msgType = urlParams.get('msg_type') || 'success';
        showToast(decodeURIComponent(urlParams.get('msg')), msgType);
        
        // Clean URL
        const newUrl = window.location.pathname + (urlParams.has('tab') ? `?tab=${urlParams.get('tab')}` : '');
        window.history.replaceState({}, document.title, newUrl);
    }

    // 5. Quick Client Search Filter
    const searchInput = document.getElementById('table-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const filterValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(filterValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
