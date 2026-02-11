// Regisztrációs űrlap validálása
document.addEventListener("DOMContentLoaded", function() {
    const registerForm = document.querySelector('.register-form');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            let isValid = true;

            // Teljes név validálás (csak betűk és szóköz)
            const fullname = document.getElementById('fullname');
            const fullnameError = document.querySelector('#fullname + .error-message');
            if (!/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű\s]+$/.test(fullname.value)) {  // Engedélyezett ékezetek
                fullname.classList.add('input-error');
                if (fullnameError) fullnameError.textContent = 'A Teljes név csak betűt és szóközt tartalmazhat.';
                isValid = false;
            } else {
                fullname.classList.remove('input-error');
                if (fullnameError) fullnameError.textContent = '';
            }

            // Email validálás
            const email = document.getElementById('email');
            const emailError = document.querySelector('#email + .error-message');
            // Egyszerű email validálás
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                email.classList.add('input-error');
                if (emailError) emailError.textContent = 'Helytelen email cím.';
                isValid = false;
            } else {
                email.classList.remove('input-error');
                if (emailError) emailError.textContent = '';
            }

            // Jelszó validálás
            const password = document.getElementById('password');
            const passwordError = document.querySelector('#password + .error-message');
            const confirmPassword = document.getElementById('confirm_password');
            const confirmPasswordError = document.querySelector('#confirm_password + .error-message');
            
            if (password.value !== confirmPassword.value || password.value.length < 6 || password.value.length > 13 || !/[a-z]/.test(password.value) || !/[A-Z]/.test(password.value) || !/\d/.test(password.value)) {
                password.classList.add('input-error');
                confirmPassword.classList.add('input-error');
                if (passwordError) passwordError.textContent = 'A jelszónak minimum 1 kis és nagy betűből, 1 számból és minimum 6 maximum 13 karakterből kell állnia.';
                if (confirmPasswordError) confirmPasswordError.textContent = 'A jelszónak minimum 1 kis és nagy betűből, 1 számból és minimum 6 maximum 13 karakterből kell állnia.';
                isValid = false;
            } else {
                password.classList.remove('input-error');
                confirmPassword.classList.remove('input-error');
                if (passwordError) passwordError.textContent = '';
                if (confirmPasswordError) confirmPasswordError.textContent = '';
            }

            // Ha bárhol hiba van, ne küldje el az űrlapot
            if (!isValid) {
                event.preventDefault(); 
            }
        });
    }
});
