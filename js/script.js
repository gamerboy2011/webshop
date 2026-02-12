document.addEventListener("DOMContentLoaded", function() {
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
});
