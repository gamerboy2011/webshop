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
});
function changeImage(el) {
        document.getElementById('mainImage').src = el.src;

        document.querySelectorAll('.thumbnail').forEach(img => {
            img.classList.remove('ring-2', 'ring-black');
        });

        el.classList.add('ring-2', 'ring-black');
    }

