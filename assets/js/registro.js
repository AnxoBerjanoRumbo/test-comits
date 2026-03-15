/**
 * Validaciones para el formulario de registro
 */
document.addEventListener('DOMContentLoaded', function() {
    const pass = document.getElementById('pass');
    const confirm_pass = document.getElementById('confirm_pass');
    const error_pass = document.getElementById('error_pass');
    const form = document.querySelector('.form-ark');

    if (pass && confirm_pass && form) {
        function checkPass() {
            if (pass.value !== confirm_pass.value && confirm_pass.value !== '') {
                error_pass.style.display = 'block';
                return false;
            } else {
                error_pass.style.display = 'none';
                return true;
            }
        }

        pass.addEventListener('input', checkPass);
        confirm_pass.addEventListener('input', checkPass);

        form.addEventListener('submit', function(e) {
            if (!checkPass()) {
                e.preventDefault();
                alert('Las contraseñas no coinciden.');
            }
        });
    }
});
