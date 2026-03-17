document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Funcionalidad del botón de volver arriba
    const btnArriba = document.getElementById('btnArriba');
    if (btnArriba) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                btnArriba.style.display = 'block';
                btnArriba.style.opacity = '1';
                btnArriba.style.transform = 'translateY(0)';
            } else {
                btnArriba.style.opacity = '0';
                btnArriba.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    if (window.scrollY <= 300) btnArriba.style.display = 'none';
                }, 300);
            }
        });

        // Estilos iniciales para la animación
        btnArriba.style.transition = 'all 0.3s ease';
        btnArriba.style.opacity = '0';
        btnArriba.style.transform = 'translateY(20px)';

        btnArriba.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // 2. Contador de caracteres para los campos textarea (Comentarios y descripciones)
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        const span = document.createElement('div');
        span.className = 'contador-caracteres';
        
        // Estimar unas 10.000 palabras = 60.000 caracteres
        const maxLength = 60000;
        
        span.textContent = `0 / ${maxLength} caracteres`;
        textarea.parentNode.insertBefore(span, textarea.nextSibling);

        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            span.textContent = `${currentLength} / ${maxLength} caracteres`;

            if (currentLength > maxLength * 0.9) {
                span.classList.add('limite-cerca');
                span.classList.remove('limite-excedido');
            } else {
                span.classList.remove('limite-cerca');
            }

            if (currentLength >= maxLength) {
                span.classList.add('limite-excedido');
                this.value = this.value.substring(0, maxLength); // Trim text
                span.textContent = `${maxLength} / ${maxLength} caracteres`;
            } else {
                span.classList.remove('limite-excedido');
            }
        });
    });

    // 3. Previsualizar la imagen antes de subirla
    const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Ver si ya existe una preview
                    let preview = input.parentNode.querySelector('.img-preview-container img');
                    let container = input.parentNode.querySelector('.img-preview-container');
                    
                    if (!container) {
                        container = document.createElement('div');
                        container.className = 'img-preview-container';
                        input.parentNode.appendChild(container);
                        
                        preview = document.createElement('img');
                        container.appendChild(preview);
                    }
                    
                    // Decidir qué clase aplicar según el input
                    const isProfile = input.name.includes('perfil') || input.id.includes('perfil');
                    preview.className = isProfile ? 'foto-perfil-preview' : 'img-preview';
                    
                    if (isProfile) {
                        preview.style.width = '150px';
                        preview.style.height = '150px';
                        preview.style.borderRadius = '50%';
                        preview.style.objectFit = 'cover';
                    } else {
                        preview.style.maxWidth = '300px';
                        preview.style.maxHeight = '300px';
                        preview.style.borderRadius = '8px';
                    }
                    
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    preview.style.margin = '15px auto';
                    preview.style.border = '2px solid var(--accent)';
                }
                reader.readAsDataURL(file);
            }
        });
    });

    // 4. Confirmaciones genéricas mediante data-confirm
    document.addEventListener('click', function(e) {
        const target = e.target.closest('[data-confirm]');
        if (target) {
            const mensaje = target.getAttribute('data-confirm') || '¿Estás seguro de realizar esta acción?';
            if (!confirm(mensaje)) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        }
    });

    // Compatibilidad adicional para formularios de borrado que no usen data-confirm todavía
    const legacyConfirmButtons = document.querySelectorAll('form[action="actions/borrar_comentario.php"] button, .boton-eliminar');
    legacyConfirmButtons.forEach(btn => {
        if (!btn.hasAttribute('data-confirm')) {
            btn.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro de que deseas realizar esta acción irreversible?')) {
                    e.preventDefault();
                }
            });
        }
    });

    // 5. Manejo global de errores de imágenes (Fallback)
    // Usamos delegación en la fase de captura porque el evento 'error' no burbujea
    document.addEventListener('error', function(e) {
        if (e.target.tagName === 'IMG') {
            // Intentar detectar si es perfil o dino según clase
            const cl = e.target.classList;
            const isProfile = cl.contains('perfil-foto-main') || 
                              cl.contains('avatar-header') || 
                              cl.contains('avatar-comentario') ||
                              cl.contains('moderacion-avatar') ||
                              cl.contains('perfil-avatar-nav');
            
            const root = typeof path_prefix !== 'undefined' ? path_prefix : '';
            const fallback = isProfile ? root + 'assets/img/perfil/default.png' : root + 'assets/img/dinos/default_dino.jpg';
            
            if (e.target.src !== window.location.origin + window.location.pathname && !e.target.src.includes(fallback)) {
                e.target.src = fallback;
            }
        }
    }, true);
});
