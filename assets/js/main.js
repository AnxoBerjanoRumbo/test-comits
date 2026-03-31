document.addEventListener('DOMContentLoaded', function () {

    // 1. Funcionalidad de Accesibilidad (Temas y Daltonismo)
    const btnAcc = document.getElementById('btnAcc');
    const accMenu = document.getElementById('accMenu');

    if (btnAcc && accMenu) {
        btnAcc.addEventListener('click', function (e) {
            e.stopPropagation();
            accMenu.classList.toggle('active');
        });

        document.addEventListener('click', function () {
            accMenu.classList.remove('active');
        });

        accMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    // 2. Funcionalidad del botón de volver arriba (Scroll)
    const scrollContainer = document.getElementById('scrollContainer');
    const btnArriba = document.getElementById('btnArriba');

    if (scrollContainer && btnArriba) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 400) {
                scrollContainer.style.opacity = '1';
                scrollContainer.style.visibility = 'visible';
                scrollContainer.style.transform = 'translateY(0)';
            } else {
                scrollContainer.style.opacity = '0';
                scrollContainer.style.visibility = 'hidden';
                scrollContainer.style.transform = 'translateY(20px)';
            }
        });

        btnArriba.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Sistema de Temas Dinámicos (Global)
    window.setTheme = function (themeName) {
        document.body.classList.remove('theme-ragnarok', 'theme-aberration', 'theme-extinction', 'theme-scorched', 'theme-daltonico');
        document.body.classList.add('theme-' + themeName);
        localStorage.setItem('ark_hub_theme', themeName);
    };

    const savedTheme = localStorage.getItem('ark_hub_theme');
    if (savedTheme) {
        setTheme(savedTheme);
    } else {
        setTheme('ragnarok');
    }

    // 3. Contador de caracteres para textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        const span = document.createElement('div');
        span.className = 'contador-caracteres';
        const maxLength = 10000;
        span.textContent = `0 / ${maxLength} caracteres`;
        textarea.parentNode.insertBefore(span, textarea.nextSibling);

        textarea.addEventListener('input', function () {
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
                this.value = this.value.substring(0, maxLength);
                span.textContent = `${maxLength} / ${maxLength} caracteres`;
            } else {
                span.classList.remove('limite-excedido');
            }
        });
    });

    // 4. Previsualizar imagen antes de subirla
    const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    let preview = input.parentNode.querySelector('.img-preview-container img');
                    let container = input.parentNode.querySelector('.img-preview-container');

                    if (!container) {
                        container = document.createElement('div');
                        container.className = 'img-preview-container';
                        input.parentNode.appendChild(container);
                        preview = document.createElement('img');
                        container.appendChild(preview);
                    }

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
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // 5. Confirmaciones antes de borrar
    const confirmButtons = document.querySelectorAll('form[action="actions/borrar_comentario.php"] button, .boton-eliminar');
    confirmButtons.forEach(btn => {
        if (btn.hasAttribute('onclick')) {
            btn.removeAttribute('onclick');
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (confirm('¿Estás seguro de que deseas realizar esta acción irreversible?')) {
                    if (btn.tagName.toLowerCase() === 'button') {
                        btn.closest('form').submit();
                    } else if (btn.tagName.toLowerCase() === 'a') {
                        window.location.href = btn.href;
                    }
                }
            });
        }
    });

    // 6. Sistema de Notificaciones dinámico
    const btnNotif = document.getElementById('btn-notif');
    const dropdownNotif = document.getElementById('dropdown-notif');
    const listaNotif = document.getElementById('lista-notif');

    if (btnNotif && dropdownNotif && listaNotif) {
        const basePath = window.location.pathname.includes('/admin/') ? '../' : './';

        btnNotif.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdownNotif.classList.toggle('active');

            if (dropdownNotif.classList.contains('active')) {
                cargarNotificaciones();
                // Quitar badge inmediatamente al abrir
                const badge = btnNotif.querySelector('.badge-notif');
                if (badge) badge.remove();
            }
        });

        document.addEventListener('click', function () {
            if (dropdownNotif.classList.contains('active')) {
                dropdownNotif.classList.remove('active');
                // Marcar todas como leídas en el servidor al cerrar el modal
                fetch(basePath + 'actions/marcar_todas_leidas.php', { method: 'POST' });
            }
        });

        dropdownNotif.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        function cargarNotificaciones() {
            listaNotif.innerHTML = '<div class="cargando-notif">Cargando...</div>';

            fetch(basePath + 'actions/obtener_notificaciones.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        listaNotif.innerHTML = '<div class="sin-notificaciones">' + data.error + '</div>';
                        return;
                    }

                    if (data.length === 0) {
                        listaNotif.innerHTML = '<div class="sin-notificaciones">No tienes notificaciones.</div>';
                        return;
                    }

                    listaNotif.innerHTML = '';
                    data.forEach(notif => {
                        const itemContainer = document.createElement('div');
                        itemContainer.className = 'notificacion-container' + (notif.leida == 0 ? ' no-leida' : '');
                        itemContainer.style.position = 'relative';

                        const item = document.createElement('a');

                        // Fix relative links: prefix with basePath so they work from /admin/ pages too
                        let linkHref = notif.enlace || '#';
                        if (linkHref !== '#' && !linkHref.startsWith('http') && !linkHref.startsWith('/')) {
                            linkHref = basePath + linkHref;
                        }
                        item.href = linkHref;
                        item.className = 'notificacion-item';
                        item.dataset.id = notif.id;

                        const msgLower = notif.mensaje.toLowerCase();
                        let icon = 'notifications';
                        if (notif.mensaje.startsWith('[Mensaje]'))     icon = 'mail';
                        else if (msgLower.includes('dino') || msgLower.includes('criatura')) icon = 'cruelty_free';
                        else if (msgLower.includes('comentario') || msgLower.includes('respondid')) icon = 'chat';
                        else if (msgLower.includes('restriccion') || msgLower.includes('levantad')) icon = 'lock_open';
                        else if (msgLower.includes('sanci') || msgLower.includes('baned') || msgLower.includes('suspendid')) icon = 'gavel';

                        // Acortar mensaje largo para el dropdown (el completo se ve al clicar)
                        const msgMostrar = notif.mensaje.startsWith('[Mensaje]')
                            ? notif.mensaje.replace('[Mensaje] ', '').split('\n')[0]
                            : notif.mensaje;

                        item.innerHTML = `
                            <div class="notificacion-mensaje">
                                <span class="material-symbols-outlined f-09" style="vertical-align:middle;margin-right:5px;color:var(--accent);">${icon}</span>
                                ${msgMostrar}
                            </div>
                            <div class="notificacion-fecha">${formatearFecha(notif.fecha)}</div>
                        `;

                        item.addEventListener('click', function(e) {
                            // Marcar como leidas si hay alguna cuando hace click en un item
                            fetch(basePath + 'actions/marcar_todas_leidas.php', { method: 'POST' });
                        });

                        const btnBorrar = document.createElement('button');
                        btnBorrar.className = 'btn-borrar-notif';
                        btnBorrar.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">close</span>';
                        btnBorrar.title = 'Borrar notificación';
                        btnBorrar.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const formData = new URLSearchParams();
                            formData.append('id', notif.id);

                            fetch(basePath + 'actions/borrar_notificaciones.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: formData
                            })
                            .then(response => response.json())
                            .then(res => {
                                if (res.status === 'success') {
                                    itemContainer.remove();
                                    if (listaNotif.children.length === 0) {
                                        listaNotif.innerHTML = '<div class="sin-notificaciones">No tienes notificaciones.</div>';
                                    }
                                }
                            });
                        });

                        itemContainer.appendChild(item);
                        itemContainer.appendChild(btnBorrar);
                        listaNotif.appendChild(itemContainer);
                    });
                })
                .catch(() => {
                    listaNotif.innerHTML = '<div class="sin-notificaciones">Error al cargar notificaciones.</div>';
                });

            // Make sure "Clear all" button exists
            let headerDiv = document.querySelector('.header-dropdown');
            if (headerDiv && !headerDiv.querySelector('.btn-clear-all-notif')) {
                const btnClearAll = document.createElement('button');
                btnClearAll.className = 'btn-clear-all-notif';
                btnClearAll.textContent = 'Limpiar todas';
                btnClearAll.style.float = 'right';
                btnClearAll.style.background = 'none';
                btnClearAll.style.border = 'none';
                btnClearAll.style.color = 'var(--text-muted)';
                btnClearAll.style.cursor = 'pointer';
                btnClearAll.style.fontSize = '0.8rem';
                btnClearAll.style.textDecoration = 'underline';
                
                btnClearAll.addEventListener('click', function(e) {
                    e.stopPropagation();
                    fetch(basePath + 'actions/borrar_notificaciones.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=all'
                    })
                    .then(response => response.json())
                    .then(res => {
                        if (res.status === 'success') {
                            listaNotif.innerHTML = '<div class="sin-notificaciones">No tienes notificaciones.</div>';
                            const badge = btnNotif.querySelector('.badge-notif');
                            if (badge) badge.remove();
                        }
                    });
                });
                
                headerDiv.appendChild(btnClearAll);
                
                // Clear floats
                const clearFix = document.createElement('div');
                clearFix.style.clear = 'both';
                headerDiv.appendChild(clearFix);
            }
        }

        function formatearFecha(fechaStr) {
            const fecha = new Date(fechaStr);
            const ahora = new Date();
            const diffSec = Math.floor((ahora - fecha) / 1000);

            if (diffSec < 60) return 'Hace un momento';
            if (diffSec < 3600) return 'Hace ' + Math.floor(diffSec / 60) + ' min';
            if (diffSec < 86400) return 'Hace ' + Math.floor(diffSec / 3600) + ' h';
            return fecha.toLocaleDateString();
        }

        // Polling cada 15s para actualizar el badge
        setInterval(function () {
            fetch(basePath + 'actions/contar_notificaciones_no_leidas.php')
                .then(res => res.json())
                .then(data => {
                    const count = data.count;
                    const oldBadge = btnNotif.querySelector('.badge-notif');

                    if (count > 0) {
                        if (oldBadge) {
                            oldBadge.textContent = count;
                        } else {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'badge-notif';
                            newBadge.textContent = count;
                            btnNotif.appendChild(newBadge);
                        }
                    } else if (oldBadge) {
                        oldBadge.remove();
                    }
                })
                .catch(err => console.error('Polling error:', err));
        }, 15000);
    }
});
