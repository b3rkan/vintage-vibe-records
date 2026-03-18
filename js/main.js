/* ===================================
   VINTAGE VIBE RECORDS - MAIN SCRIPT
   White Theme - Favoriler & Sepet Sistemi
=================================== */

// ===== API PATHS =====
const API_URL = {
    FAVORITES_TOGGLE: './api/favoriler_toggle.php',
    CART_ADD: './api/sepet_ekle.php',
    CART_REMOVE: './api/sepet_sil.php',
    CART_INFO: './api/sepet_bilgi.php'
};

document.addEventListener('DOMContentLoaded', function () {
    // Sayfa yüklenirken sepet bilgisini al (error handling ile)
    fetch(API_URL.CART_INFO)
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                updateCartBadge(data.cart_total);
            }
        })
        .catch(error => console.log('Sepet bilgisi yüklenemedi (opsiyonel):', error));

    // Event listeners'ları başlat
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
    if (!productId) {
        console.error('toggleFavorite: Product ID eksik!', productId);
        showNotification('Ürün ID bulunamadı', 'error');
        return;
    }

    console.log('toggleFavorite çağrıldı - Product ID:', productId);

    // AJAX ile sunucuya gönder (JSON format)
    fetch(API_URL.FAVORITES_TOGGLE, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: productId })
    })
        .then(response => {
            console.log('API Response Status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data);

            if (data.success) {
                if (data.is_favorite) {
                    btn.classList.add('active');
                    btn.textContent = '♥';
                } else {
                    btn.classList.remove('active');
                    btn.textContent = '♡';
                }
                showNotification(data.message, 'success');
            } else {
                showNotification('Hata: ' + (data.message || 'Bilinmeyen hata'), 'error');
            }
        })
        .catch(error => {
            console.error('Favoriler hatası:', error);
            showNotification('Hata: Favoriler güncellenemedi', 'error');
        });
}

function loadFavoritesFromServer() {
    // Sayfada PHP tarafından gelen favori listesi var mı kontrol et
    // şimdilik bu boş - ama daha sonra sunucudan gelecek
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
            if (productId) addToCart(productId, this);
        });
    });

    // Sepet sayfası: ürün silme butonlarını dinle
    const deleteCartBtns = document.querySelectorAll('.btn-sil');
    deleteCartBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            // Linkteki URL'den id çıkar (sepet_sil.php?id=123)
            const url = this.getAttribute('href');
            const productId = new URLSearchParams(url.split('?')[1]).get('id');

            if (productId) {
                removeFromCart(productId);
            }
        });
    });

    // Detay sayfası form submit'ini intercept et
    const cartForm = document.getElementById('cart-form');
    if (cartForm) {
        cartForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const id = this.querySelector('input[name="id"]').value;
            const button = this.querySelector('button[type="submit"]');

            addToCart(id, button);
        });
    }
}

// Detay sayfası: ürün zaten sepette mi kontrol et
function checkProductInCart() {
    const cartForm = document.getElementById('cart-form');
    if (!cartForm) return;

    const productId = String(cartForm.getAttribute('data-product-id')); // String'e dönüştür
    const button = cartForm.querySelector('button[type="submit"]');

    if (!productId || !button) return;

    // AJAX ile sunucudan kontrol et (opsiyonel şimdilik)
    // İleriye dönük: fetch('api/cart_check.php?id=' + productId) çağrısı yapabiliriz

    // Şimdilik: stok bilgisine göre bağlı
    if (parseInt(cartForm.getAttribute('data-stock')) === 1) {
        button.textContent = '📦 Son Ürün - Sepete Ekle';
    }
}

function addToCart(productId, button = null) {
    fetch(API_URL.CART_ADD, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: productId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                updateCartBadge(data.cart_total);

                // Button durumunu güncelle (detay sayfası için)
                if (button && button.type === 'submit') {
                    button.textContent = '✓ Ürün Sepetinizde';
                    button.disabled = true;
                    button.style.opacity = '0.7';
                    button.style.cursor = 'not-allowed';
                }
            } else {
                showNotification('Hata: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Hata: Sepete eklenemedi', 'error');
            console.error('Sepete ekleme hatası:', error);
        });
}

function removeFromCart(productId) {
    fetch(API_URL.CART_REMOVE, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: productId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'info');
                updateCartBadge(data.cart_total);

                // Tablodan satırı kaldır (animasyonla)
                const deleteBtn = document.querySelector(`[onclick*="removeFromCart(${productId})"]`);
                if (deleteBtn) {
                    const row = deleteBtn.closest('tr');
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transition = 'opacity 0.3s ease-out';
                        setTimeout(() => row.remove(), 300);
                    }
                }
            } else {
                showNotification('Hata: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Hata: Sepetten çıkarılamadı', 'error');
            console.error('Sepetten silme hatası:', error);
        });
}

function updateCartBadge(totalItems = null) {
    const badge = document.querySelector('.icon-badge');
    if (badge) {
        if (totalItems !== null) {
            badge.textContent = totalItems;
        } else {
            // Sayfa yüklenirken sunucu session'dan hesapla
            // Bunun için HTML'de veri attribute'u gömeriz
            // Şimdilik: 0 (ileriye dönük geliştirme)
            badge.textContent = '0';
        }
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
}

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
    filterBySearch: filterProductsBySearch,
    sortProducts: sortProducts,
    subscribeNewsletter: subscribeNewsletter,
    showNotification: showNotification,
    toggleFavorite: toggleFavorite,
    addToCart: addToCart,
    removeFromCart: removeFromCart,
    updateCartBadge: updateCartBadge
};

