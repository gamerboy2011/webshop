document.addEventListener("DOMContentLoaded", () => {
    const heroElements = document.querySelectorAll(".hero-animate");

    heroElements.forEach((el) => {
        // biztosítjuk, hogy a transition lefusson
        requestAnimationFrame(() => {
            el.classList.remove("opacity-0", "translate-y-8");
        });
    });
});