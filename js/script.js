document.addEventListener("DOMContentLoaded", () => {
    console.log("SCRIPT FUT");

    /* HERO BELÉPŐ */
    document.querySelectorAll(".hero-animate").forEach(el => {
        requestAnimationFrame(() => {
            el.classList.remove("opacity-0", "translate-y-8");
        });
    });

    /* TERMÉKKÁRTYÁK SCROLLRA */
    const productCards = document.querySelectorAll(".product-card");

    const observer = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.remove("opacity-0", "translate-y-8");
                    observer.unobserve(entry.target); // csak egyszer fusson le
                }
            });
        },
        {
            threshold: 0.15
        }
    );

    productCards.forEach(card => observer.observe(card));

function changeImage(el) {
        document.getElementById('mainImage').src = el.src;

        document.querySelectorAll('.thumbnail').forEach(img => {
            img.classList.remove('ring-2', 'ring-black');
        });

        el.classList.add('ring-2', 'ring-black');
    }

document.addEventListener("DOMContentLoaded", () => {

    const foxpostRadio = document.getElementById('foxpostRadio');
    const foxpostBox   = document.getElementById('foxpostBox');

    if (foxpostRadio && foxpostBox) {
        document.querySelectorAll('input[name="delivery_method_id"]').forEach(radio => {
            radio.addEventListener('change', () => {
                foxpostBox.classList.toggle('hidden', !foxpostRadio.checked);
            });
        });
    }

});
//postcode.php script-je
    function toggleAddress(show) {
        document.getElementById('shippingAddress').style.display = show ? 'block' : 'none';
    }
// város kiegészítés script
console.log('JS FUT');
function fetchCity(postcode, cityInput, cityIdInput) {
    cityInput.value = '';
    cityIdInput.value = '';

    if (postcode.length !== 4) return;

    fetch(`app/api/city_by_postcode.php?postcode=${postcode}`)
        .then(res => res.json())
        .then(cities => {

            if (cities.length === 0) {
                cityInput.value = 'Nincs ilyen irányítószám';
                return;
            }

            if (cities.length === 1) {
                cityInput.value = cities[0].city_name;
                cityIdInput.value = cities[0].city_id;
                return;
            }

            // több város esetén (pl. Budapest)
            cityInput.value = cities.map(c => c.city_name).join(', ');
            cityIdInput.value = cities[0].city_id; // backendnek elég 1
        })
        .catch(() => {
            cityInput.value = 'Hiba a város betöltésekor';
        });
}


document.getElementById('billing_postcode').addEventListener('input', e => {
    fetchCity(
        e.target.value,
        document.getElementById('billing_city_name'),
        document.getElementById('billing_city_id')
    );
});
//checkbox ha a számlázási, szállítási cím megegyezik
document.getElementById('shipping_postcode').addEventListener('input', e => {
    fetchCity(
        e.target.value,
        document.getElementById('shipping_city_name'),
        document.getElementById('shipping_city_id')
    );
});

function toggleShippingAddress() {
    const checkbox = document.getElementById('sameAddress');
    const shipping = document.getElementById('shippingAddress');

    if (checkbox.checked) {
        shipping.style.display = 'none';

        // másolás
        document.getElementById('shipping_city_id').value =
            document.getElementById('billing_city_id').value;

        document.querySelector('[name="shipping_street"]').value =
            document.querySelector('[name="billing_street"]').value;
    } else {
        shipping.style.display = 'block';
    }
}
});

document.getElementById('sameAddress').addEventListener('change', e => {
  document.getElementById('shippingAddress')
    .classList.toggle('hidden', e.target.checked);
});

document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("userMenuBtn");
    const dropdown = document.getElementById("userDropdown");

    btn.addEventListener("click", function (e) {
        e.stopPropagation();
        dropdown.classList.toggle("hidden");
    });

    document.addEventListener("click", function () {
        dropdown.classList.add("hidden");
    });
});