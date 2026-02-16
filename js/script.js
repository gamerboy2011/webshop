document.addEventListener("DOMContentLoaded", function() {
    // REGISZTRÁCIÓ – jelszó validáció
    const registerForm = document.getElementById('register-form');

    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('password_confirm');

            const pwd = password.value;

            const hasLower = /[a-z]/.test(pwd);
            const hasUpper = /[A-Z]/.test(pwd);
            const hasDigit = /[0-9]/.test(pwd);
            const correctLength = pwd.length >= 6 && pwd.length <= 13;

            if (
                !correctLength ||
                !hasLower ||
                !hasUpper ||
                !hasDigit ||
                pwd !== confirmPassword.value
            ) {
                event.preventDefault();
                alert("A jelszónak 6–13 karakter hosszúnak kell lennie, tartalmaznia kell kis- és nagybetűt, valamint számot, és a két jelszónak egyeznie kell.");
            }
        });
    }

    // ==============
    // KEDVENCEK – guest + logged
    // ==============

    function getGuestFavorites() {
        try {
            return JSON.parse(localStorage.getItem("favorites") || "[]");
        } catch (e) {
            return [];
        }
    }

    function saveGuestFavorites(list) {
        localStorage.setItem("favorites", JSON.stringify(list));
    }

    function toggleGuestFavorite(productId, btn) {
        let favs = getGuestFavorites();

        if (favs.includes(productId)) {
            favs = favs.filter(id => id !== productId);
            btn.classList.remove("text-red-500", "border-red-400");
            btn.classList.add("text-gray-400");
        } else {
            favs.push(productId);
            btn.classList.add("text-red-500", "border-red-400");
            btn.classList.remove("text-gray-400");
        }

        saveGuestFavorites(favs);
    }

    const favButtons = document.querySelectorAll('.favorite-btn');

    favButtons.forEach(btn => {
        const productId = parseInt(btn.dataset.product, 10);
        const isLogged = btn.dataset.logged === '1';

        if (!productId) return;

        // guest esetén állapot visszatöltése
        if (!isLogged) {
            const favs = getGuestFavorites();
            if (favs.includes(productId)) {
                btn.classList.add("text-red-500", "border-red-400");
                btn.classList.remove("text-gray-400");
            } else {
                btn.classList.add("text-gray-400");
            }
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();

            if (isLogged) {
                // szerveres toggle – a routeredhez igazítva
                fetch('/favorite-toggle', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'product_id=' + encodeURIComponent(productId)
                })
                .then(r => r.json())
                .then(() => {
                    btn.classList.toggle("text-red-500");
                    btn.classList.toggle("border-red-400");
                    btn.classList.toggle("text-gray-400");
                })
                .catch(() => {});
            } else {
                // guest – localStorage
                toggleGuestFavorite(productId, btn);
            }
        });
    });
});
