<?php
$gender = $_GET['gender'] ?? null;
$hideHero = $gender !== null;
?>

<section
    id="hero"
    class="w-full transition-all duration-700 ease-in-out
    <?= $hideHero
        ? 'opacity-0 -translate-y-10 max-h-0 overflow-hidden'
        : 'opacity-100 translate-y-0 max-h-[2000px]'
    ?>"
>

    <!-- FEHÉR FELSŐ RÉSZ -->
    <div class="w-full bg-white">
        <div
            class="max-w-7xl mx-auto px-6 py-24 text-center
                   opacity-0 translate-y-8
                   transition-all duration-700 ease-out
                   hero-animate"
        >
            <h1 class="text-6xl font-extrabold mb-6">
                Yoursy Wear
            </h1>

            <p class="text-lg text-gray-600 mb-10 max-w-md mx-auto">
                Your style speaks for you.
            </p>

            <a
                href="#products"
                class="inline-block bg-black text-white px-12 py-4 uppercase tracking-widest text-sm hover:bg-gray-900 transition"
            >
                Fedezd fel a termékeket
            </a>
        </div>
    </div>

    <!-- FEKETE ALSÓ RÉSZ -->
    <div class="w-full bg-black text-white">
        <div
            class="max-w-7xl mx-auto px-6 py-32 text-center
                   opacity-0 translate-y-8
                   transition-all duration-700 ease-out delay-150
                   hero-animate"
        >
            <h2 class="text-4xl font-bold mb-6">
                Yoursy Wear
            </h2>

            <p class="text-base leading-relaxed opacity-90 max-w-lg mx-auto">
                Prémium streetwear és sneaker webshop.<br>
                100% autentikus termékek.
            </p>
        </div>
    </div>

</section>
