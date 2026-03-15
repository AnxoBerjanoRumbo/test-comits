/**
 * Validaciones para la recuperación de contraseña
 */
document.addEventListener('DOMContentLoaded', function() {
    const pass = document.getElementById('pass');
    const confirm_pass = document.getElementById('confirm_pass');
    const error_pass = document.getElementById('error_pass');
    const form = document.querySelector('.form-ark');

    if (form && pass && confirm_pass) {
        function checkPass() {
            if (pass.value !== confirm_pass.value && confirm_pass.value !== '') {
                if (error_pass) error_pass.style.display = 'block';
                return false;
            } else {
                if (error_pass) error_pass.style.display = 'none';
                return true;
            }
        }

        pass.addEventListener('input', checkPass);
        confirm_pass.addEventListener('input', checkPass);

        form.addEventListener('submit', function(e) {
            if (!checkPass()) {
                e.preventDefault();
                alert("Las contraseñas no coinciden.");
            }
            if (pass.value.length < 4) {
                alert("La contraseña debe tener al menos 4 caracteres.");
                e.preventDefault();
            }
        });
    }
});
