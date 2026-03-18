/* ===================================
   VINTAGE VIBE RECORDS - MAIN SCRIPT
   White Theme - Favoriler & Sepet Sistemi
=================================== */

const FAVORITES_KEY = 'vvr_favorites';
const CART_KEY = 'vvr_cart';

document.addEventListener('DOMContentLoaded', function () {
    loadFavoritesFromStorage();
    loadCartFromStorage();
    updateCartBadge();
    initializeFavorites();
    initializeCart();
    initializeSearch();
    initializeCategoryFilter();
    initializeProductCards();
    initializeNewsletterForm();
    initializeHeaderScroll();
});

/* ===================================
   FAVORİLER SİSTEMİ
=================================== */

function initializeFavorites() {
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    const detayFavButtons = document.querySelectorAll('.favorite-btn-detay');

    // Index & product cards favoriler
    favoriteButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const productId = this.getAttribute('data-product-id');
            toggleFavorite(productId, this);
        });
    });

    // Detay sayfası favoriler
    detayFavButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            toggleFavorite(productId, this);
        });
    });
}

function toggleFavorite(productId, btn) {
    let favorites = JSON.parse(localStorage.getItem(FAVORITES_KEY)) || [];

    if (favorites.includes(productId)) {
        favorites = favorites.filter(id => id !== productId);
        btn.classList.remove('active');
        showNotification('Favorilerden çıkarıldı', 'info');
    } else {
        favorites.push(productId);
        btn.classList.add('active');
        showNotification('Favorilere eklendi! ♥', 'success');
    }

    localStorage.setItem(FAVORITES_KEY, JSON.stringify(favorites));
}

function loadFavoritesFromStorage() {
    const favorites = JSON.parse(localStorage.getItem(FAVORITES_KEY)) || [];
    const favoriteButtons = document.querySelectorAll('.favorite-btn');

    favoriteButtons.forEach(btn => {
        const productId = btn.getAttribute('data-product-id');
        if (favorites.includes(productId)) {
            btn.classList.add('active');
        }
    });
}

/* ===================================
   SEPET SİSTEMİ - DİNAMİK SAYAÇ
=================================== */

function initializeCart() {
    // Detay sayfası: ürün zaten sepette mi kontrol et
    checkProductInCart();

    // Sepete ekle butonlarını dinle
    const addToCartBtns = document.querySelectorAll('[data-product-id].sepete-ekle-btn, .sepete-ekle-btn[data-product-id]');
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            if (productId) addToCart(productId);
        });
    });

    // Detay sayfası form submit'ini intercept et
    const cartForm = document.getElementById('cart-form');
    if (cartForm) {
        cartForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const id = this.querySelector('input[name="id"]').value;
            const button = this.querySelector('button[type="submit"]');

            // AJAX ile sepete ekle
            fetch('sepete_ekle.php?id=' + id, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('✓ Ürün sepetinize eklenmiştir', 'success');
                        // LocalStorage cart'a da ekle (UI uyumu için)
                        let cart = JSON.parse(localStorage.getItem('vvr_cart')) || [];
                        const existingItem = cart.find(item => item.id === id);
                        if (existingItem) {
                            existingItem.quantity += 1;
                        } else {
                            cart.push({ id: id, quantity: 1 });
                        }
                        localStorage.setItem('vvr_cart', JSON.stringify(cart));
                        updateCartBadge();
                        
                        // Button'u güncelleştir - tekrar tıklanamayacak
                        button.textContent = '✓ Ürün Sepetinizde';
                        button.disabled = true;
                        button.style.opacity = '0.7';
                        button.style.cursor = 'not-allowed';
                    }
                })
                .catch(error => {
                    showNotification('Hata: Sepete eklenemedi', 'error');
                    console.error('Sepete ekleme hatası:', error);
                });
        });
    }
}

// Detay sayfası: ürün zaten sepette mi kontrol et
function checkProductInCart() {
    const cartForm = document.getElementById('cart-form');
    if (!cartForm) return;

    const productId = cartForm.getAttribute('data-product-id');
    const button = cartForm.querySelector('button[type="submit"]');
    
    if (!productId || !button) return;

    // localStorage'dan kontrol et
    let cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
    const inCart = cart.find(item => item.id === productId);

    // Eğer sepette varsa button'u disable et
    if (inCart) {
        button.textContent = '✓ Ürün Sepetinizde';
        button.disabled = true;
        button.style.opacity = '0.7';
        button.style.cursor = 'not-allowed';
    } else if (parseInt(cartForm.getAttribute('data-stock')) === 1) {
        // Stok 1 kalıştı ise
        button.textContent = '📦 Son Ürün - Sepete Ekle';
    }
}

function addToCart(productId) {
    let cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];

    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({ id: productId, quantity: 1 });
    }

    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartBadge();
    showNotification('Sepete eklendi!', 'success');
}

function loadCartFromStorage() {
    return JSON.parse(localStorage.getItem(CART_KEY)) || [];
}

function updateCartBadge() {
    const cart = loadCartFromStorage();
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

    const badge = document.querySelector('.icon-badge');
    if (badge) {
        badge.textContent = totalItems;
    }
}

function initializeSearch() {
    const searchForm = document.querySelector('.search-form');
    const searchInput = document.querySelector('.search-input');

    if (!searchForm) return;

    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const query = searchInput.value.trim();

        if (query.length > 0) {
            window.location.href = `index.php?arama=${encodeURIComponent(query)}`;
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            filterProductsBySearch(query);
        });
    }
}

/* ===================================
   CATEGORY FILTER
=================================== */

function initializeCategoryFilter() {
    const categoryItems = document.querySelectorAll('.category-item');

    categoryItems.forEach(item => {
        item.addEventListener('click', function (e) {
            // LİNK ÇALIŞMASINı SAĞ LA (sunucu tarafında filtreleme)
            // e.preventDefault() KAKALDIRILDI - kategori URL'si çalışacak

            // Smooth scroll to products after page load
            setTimeout(() => smoothScrollToProducts(), 100);
        });
    });

    /* ===================================
       PRODUCT SEARCH FILTER
    =================================== */

    function filterProductsBySearch(query) {
        const productCards = document.querySelectorAll('.product-card');
        let visibleCount = 0;

        productCards.forEach(card => {
            const title = card.querySelector('.product-title')?.textContent.toLowerCase() || '';
            const artist = card.querySelector('.product-artist')?.textContent.toLowerCase() || '';
            const category = card.querySelector('.product-category')?.textContent.toLowerCase() || '';

            if (title.includes(query) || artist.includes(query) || category.includes(query)) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.5s ease-out';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Show "no products" message if needed
        if (visibleCount === 0 && query.length > 0) {
            console.log('No products found for:', query);
        }
    }

    /* ===================================
       PRODUCT CARDS INTERACTION
    =================================== */

    function initializeProductCards() {
        const productCards = document.querySelectorAll('.product-card');

        productCards.forEach(card => {
            const viewBtn = card.querySelector('.product-view-btn');
            const productLink = card.querySelector('.product-link-overlay');

            if (viewBtn) {
                viewBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const href = this.getAttribute('data-href') || productLink?.href;
                    if (href) {
                        window.location.href = href;
                    }
                });
            }

            if (productLink) {
                productLink.addEventListener('click', function () {
                    const href = this.getAttribute('href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            }
        });
    }

    /* ===================================
       NEWSLETTER FORM
    =================================== */

    function initializeNewsletterForm() {
        const newsletterForm = document.querySelector('.newsletter-form');

        if (!newsletterForm) return;

        newsletterForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const emailInput = this.querySelector('input[type="email"]');
            const email = emailInput.value.trim();

            // Basic email validation
            if (!isValidEmail(email)) {
                showNotification('Lütfen geçerli bir email adresi girin', 'error');
                return;
            }

            // Send newsletter subscription
            subscribeNewsletter(email);
        });
    }

    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function subscribeNewsletter(email) {
        showNotification('E-postanız başarıyla kaydedildi!', 'success');
    }

    /* ===================================
       SORT FUNCTIONALITY
    =================================== */

    function initializeSortForm() {
        const sortSelect = document.querySelector('.sort-select');

        if (!sortSelect) return;

        sortSelect.addEventListener('change', function () {
            const sortValue = this.value;
            console.log('Sort by:', sortValue);

            // Sort products based on selection
            sortProducts(sortValue);
        });
    }

    function sortProducts(sortBy) {
        const productCards = Array.from(document.querySelectorAll('.product-card'));
        const productsGrid = document.querySelector('.products-grid');

        productCards.sort((a, b) => {
            switch (sortBy) {
                case 'price-low':
                    return getPriceValue(a) - getPriceValue(b);
                case 'price-high':
                    return getPriceValue(b) - getPriceValue(a);
                case 'newest':
                    return getYearValue(b) - getYearValue(a);
                case 'oldest':
                    return getYearValue(a) - getYearValue(b);
                case 'name-a-z':
                    return getTitleText(a).localeCompare(getTitleText(b));
                case 'name-z-a':
                    return getTitleText(b).localeCompare(getTitleText(a));
                default:
                    return 0;
            }
        });

        // Re-append sorted cards
        productCards.forEach(card => {
            productsGrid.appendChild(card);
        });
    }

    function getPriceValue(card) {
        const priceText = card.querySelector('.product-price')?.textContent || '0';
        return parseFloat(priceText.replace(/[^\d.]/g, ''));
    }

    function getYearValue(card) {
        const yearText = card.querySelector('.product-year')?.textContent || '0';
        return parseInt(yearText.match(/\d+/)?.[0] || 0);
    }

    function getTitleText(card) {
        return card.querySelector('.product-title')?.textContent || '';
    }

    /* ===================================
       HEADER SCROLL BEHAVIOR
    =================================== */

    function initializeHeaderScroll() {
        let lastScrollTop = 0;
        const header = document.querySelector('.vvr-header');

        if (!header) return;

        window.addEventListener('scroll', function () {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop > 100) {
                header.style.boxShadow = '0 2px 10px rgba(212, 175, 55, 0.1)';
            } else {
                header.style.boxShadow = 'none';
            }

            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        });
    }

    /* ===================================
       UTILITY FUNCTIONS
    =================================== */

    function smoothScrollToProducts() {
        const productsSection = document.querySelector('.products-section');
        if (productsSection) {
            productsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        const bgColor = type === 'success' ? '#ad3107' : type === 'error' ? '#ef4444' : '#3b82f6';

        notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${bgColor};
        color: white;
        border-radius: 6px;
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        font-weight: 500;
    `;

        document.body.appendChild(notification);

        const style = document.createElement('style');
        style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
        if (!document.head.querySelector('style[data-notifications]')) {
            style.setAttribute('data-notifications', 'true');
            document.head.appendChild(style);
        }

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /* ===================================
       EXPORT FUNCTIONS
    =================================== */

    window.VintageVibeRecords = {
        filterByCategory: filterProductsByCategory,
        filterBySearch: filterProductsBySearch,
        sortProducts: sortProducts,
        subscribeNewsletter: subscribeNewsletter,
        showNotification: showNotification
    };
    
