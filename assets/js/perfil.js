/**
 * Funcionalidad para el perfil de usuario
 */
document.addEventListener('DOMContentLoaded', function() {
    // Auto-envío de la foto al seleccionar archivo
    const fotoPerfil = document.getElementById('foto_perfil');
    const formFoto = document.getElementById('form-foto');

    if (fotoPerfil && formFoto) {
        fotoPerfil.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                formFoto.submit();
            }
        });

        const btnSeleccionar = document.getElementById('btn-seleccionar-foto');
        if (btnSeleccionar) {
            btnSeleccionar.addEventListener('click', () => fotoPerfil.click());
        }
    }
});
