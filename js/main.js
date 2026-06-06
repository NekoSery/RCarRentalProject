// RCar Rental - Main JavaScript File

// Mobile Navigation Toggle
function toggleMobileMenu() {
    const navLinks = document.getElementById('navLinks');
    navLinks.classList.toggle('active');
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Toast Notification System
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Remove after delay
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Modal Functions
function openAddCarModal() {
    const modal = document.getElementById('carModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeCarModal() {
    const modal = document.getElementById('carModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Filter Functions for Catalog
function applyFilters() {
    const brand = document.getElementById('filterBrand')?.value;
    const price = document.getElementById('filterPrice')?.value;
    
    let url = 'index.php?page=catalog';
    if (brand) url += '&brand=' + encodeURIComponent(brand);
    if (price) url += '&price=' + encodeURIComponent(price);
    
    window.location.href = url;
}

// Search Function
function searchCars() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput && searchInput.value.trim()) {
        window.location.href = 'index.php?page=catalog&search=' + encodeURIComponent(searchInput.value.trim());
    }
}

// Smooth Scroll
function smoothScroll(target) {
    const element = document.querySelector(target);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

// Navbar Scroll Effect
window.addEventListener('scroll', function() {
    const navbar = document.getElementById('navbar');
    if (navbar) {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }
});

// Form Validation Helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    return isValid;
}

// Date Validation for Booking
function validateDates() {
    const pickup = document.getElementById('bookingPickup')?.value;
    const returnDate = document.getElementById('bookingReturn')?.value;
    
    if (pickup && returnDate) {
        const pickupDate = new Date(pickup);
        const returnDateObj = new Date(returnDate);
        
        if (returnDateObj <= pickupDate) {
            showToast('Return date must be after pickup date', 'error');
            return false;
        }
    }
    
    return true;
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });
    
    // Add enter key support for search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCars();
            }
        });
    }
});

// Export functions for use in other scripts
window.RCar = {
    showToast,
    openModal,
    closeModal,
    openAddCarModal,
    closeCarModal,
    applyFilters,
    searchCars,
    validateForm
};