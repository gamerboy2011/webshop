/**
 * YoursyWear RESTful API Client
 * Egységes API kommunikáció a frontenddel
 */

const API = {
    baseUrl: '/webshop/api/v1',
    
    /**
     * Alap fetch wrapper
     */
    async request(endpoint, options = {}) {
        const url = this.baseUrl + endpoint;
        
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };
        
        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            // Ha nem sikeres, dobjunk hibát
            if (!response.ok) {
                const error = new Error(data.message || 'API hiba');
                error.status = response.status;
                error.data = data;
                throw error;
            }
            
            return data;
        } catch (error) {
            if (error.status) throw error;
            throw new Error('Hálózati hiba');
        }
    },
    
    // ============ PRODUCTS ============
    products: {
        /**
         * Termékek listázása
         * @param {Object} filters - category, vendor, min_price, max_price, search, sort, limit, offset
         */
        async list(filters = {}) {
            const params = new URLSearchParams(filters).toString();
            return API.request('/products' + (params ? '?' + params : ''));
        },
        
        /**
         * Termék részletei
         */
        async get(id) {
            return API.request('/products/' + id);
        }
    },
    
    // ============ CART ============
    cart: {
        /**
         * Kosár lekérése
         */
        async get() {
            return API.request('/cart');
        },
        
        /**
         * Termék hozzáadása
         */
        async add(productId, sizeId, quantity = 1) {
            return API.request('/cart', {
                method: 'POST',
                body: { product_id: productId, size_id: sizeId, quantity }
            });
        },
        
        /**
         * Mennyiség módosítása
         */
        async update(cartItemId, quantity) {
            return API.request('/cart/' + cartItemId, {
                method: 'PUT',
                body: { quantity }
            });
        },
        
        /**
         * Elem törlése
         */
        async remove(cartItemId) {
            return API.request('/cart/' + cartItemId, {
                method: 'DELETE'
            });
        },
        
        /**
         * Kosár ürítése
         */
        async clear() {
            return API.request('/cart', {
                method: 'DELETE'
            });
        }
    },
    
    // ============ FAVORITES ============
    favorites: {
        /**
         * Kívánságlista lekérése
         */
        async list() {
            return API.request('/favorites');
        },
        
        /**
         * Kívánságlistához adás
         */
        async add(productId) {
            return API.request('/favorites', {
                method: 'POST',
                body: { product_id: productId }
            });
        },
        
        /**
         * Kívánságlistáról törlés
         */
        async remove(productId) {
            return API.request('/favorites/' + productId, {
                method: 'DELETE'
            });
        },
        
        /**
         * Toggle (hozzáad/eltávolít)
         */
        async toggle(productId) {
            try {
                await this.add(productId);
                return { added: true };
            } catch (e) {
                if (e.status === 400) {
                    await this.remove(productId);
                    return { added: false };
                }
                throw e;
            }
        }
    },
    
    // ============ COUPONS ============
    coupons: {
        /**
         * Kupon ellenőrzése
         */
        async validate(code, cartTotal = 0) {
            return API.request('/coupons/' + encodeURIComponent(code) + '/validate?cart_total=' + cartTotal);
        }
    },
    
    // ============ ORDERS ============
    orders: {
        /**
         * Rendelések listázása
         */
        async list() {
            return API.request('/orders');
        },
        
        /**
         * Rendelés részletei
         */
        async get(id) {
            return API.request('/orders/' + id);
        },
        
        /**
         * Rendelés leadása
         */
        async create(orderData) {
            return API.request('/orders', {
                method: 'POST',
                body: orderData
            });
        }
    },
    
    // ============ RETURNS ============
    returns: {
        /**
         * Visszáru kérelmek listázása
         */
        async list() {
            return API.request('/returns');
        },
        
        /**
         * Visszáru kérelem
         */
        async create(orderId, reason, description = null) {
            return API.request('/returns', {
                method: 'POST',
                body: { order_id: orderId, reason, description }
            });
        }
    },
    
    // ============ CITIES ============
    cities: {
        /**
         * Város keresése irányítószám alapján
         */
        async getByPostcode(postcode) {
            return API.request('/cities?postcode=' + encodeURIComponent(postcode));
        }
    },
    
    // ============ AUTH ============
    auth: {
        /**
         * Bejelentkezés
         */
        async login(email, password) {
            return API.request('/auth/login', {
                method: 'POST',
                body: { email, password }
            });
        },
        
        /**
         * Regisztráció
         */
        async register(userData) {
            return API.request('/auth/register', {
                method: 'POST',
                body: userData
            });
        },
        
        /**
         * Kijelentkezés
         */
        async logout() {
            return API.request('/auth/logout', {
                method: 'POST'
            });
        },
        
        /**
         * Aktuális felhasználó
         */
        async me() {
            return API.request('/auth/me');
        }
    }
};

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = API;
}
