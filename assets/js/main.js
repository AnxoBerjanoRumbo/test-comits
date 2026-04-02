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

    // Resaltar selects del buscador con valor activo
    document.querySelectorAll('.buscador-select').forEach(sel => {
        if (sel.value) sel.classList.add('has-value');
        sel.addEventListener('change', function () {
            this.classList.toggle('has-value', !!this.value);
        });
    });

    // Sistema de Temas Dinámicos (Global)
    window.setTheme = function (themeName) {
        document.body.classList.remove('theme-ragnarok', 'theme-aberration', 'theme-extinction', 'theme-scorched', 'theme-daltonico');
        document.body.classList.add('theme-' + themeName);
        localStorage.setItem('ark_hub_theme', themeName);

        // Actualizar radares si existen
        // Mapa de colores por tema para no depender de getComputedStyle (puede no actualizarse a tiempo)
        const TEMA_COLORES = {
            'ragnarok': '0,255,204',
            'aberration': '179,0,255',
            'extinction': '255,204,0',
            'scorched': '255,102,0',
            'daltonico': '255,255,0',
        };
        const accentRgb = TEMA_COLORES[themeName] || '0,255,204';
        if (window.radarChart && window.radarChart.data && window.radarChart.data.datasets && window.radarChart.data.datasets[0]) {
            window.radarChart.data.datasets[0].backgroundColor = `rgba(${accentRgb},0.10)`;
            window.radarChart.data.datasets[0].borderColor = `rgba(${accentRgb},0.85)`;
            window.radarChart.update('none');
        }
        if (window.radarComparar && window.radarComparar.data && window.radarComparar.data.datasets && window.radarComparar.data.datasets[0]) {
            window.radarComparar.data.datasets[0].backgroundColor = `rgba(${accentRgb},0.15)`;
            window.radarComparar.data.datasets[0].borderColor = `rgba(${accentRgb},1)`;
            window.radarComparar.data.datasets[0].pointBackgroundColor = `rgba(${accentRgb},1)`;
            window.radarComparar.update('none');
        }
        // Guardar el accentRgb para aplicarlo cuando el comparador se inicialice
        window._pendingAccentRgb = accentRgb;

        // Actualizar sliders de Impronta y Taming con el color del acento
        const accent = getComputedStyle(document.body).getPropertyValue('--accent').trim();
        const imprintSlider = document.getElementById('imprint-slider');
        const tamingSlider = document.getElementById('taming-slider');
        const impVal = document.getElementById('imp-val');
        const tejVal = document.getElementById('tej-val');

        if (imprintSlider && accent) {
            imprintSlider.style.setProperty('--thumb-color', accent);
            const pct = (imprintSlider.value / 100 * 100);
            imprintSlider.style.background = `linear-gradient(to right, ${accent} 0%, ${accent} ${pct}%, rgba(255,255,255,0.1) ${pct}%, rgba(255,255,255,0.1) 100%)`;
        }
        if (tamingSlider && accent) {
            tamingSlider.style.setProperty('--thumb-color', accent);
            const pct = (tamingSlider.value / 100 * 100);
            tamingSlider.style.background = `linear-gradient(to right, ${accent} 0%, ${accent} ${pct}%, rgba(255,255,255,0.1) ${pct}%, rgba(255,255,255,0.1) 100%)`;
        }
        if (impVal) impVal.style.color = accent;
        if (tejVal) tejVal.style.color = accent;
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
                        if (notif.mensaje.startsWith('[Mensaje]')) icon = 'mail';
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
                                <span class="notif-msg-text"></span>
                            </div>
                            <div class="notificacion-fecha">${formatearFecha(notif.fecha)}</div>
                        `;
                        // Usar textContent para el mensaje para evitar XSS
                        item.querySelector('.notif-msg-text').textContent = msgMostrar;

                        item.addEventListener('click', function (e) {
                            if (linkHref !== '#') {
                                e.preventDefault();
                            }
                            // Marcar como leidas si hay alguna cuando hace click en un item
                            fetch(basePath + 'actions/marcar_todas_leidas.php', { method: 'POST' }).then(() => {
                                if (linkHref !== '#') {
                                    window.location.href = linkHref;
                                }
                            });
                        });

                        const btnBorrar = document.createElement('button');
                        btnBorrar.className = 'btn-borrar-notif';
                        btnBorrar.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">close</span>';
                        btnBorrar.title = 'Borrar notificación';
                        btnBorrar.addEventListener('click', function (e) {
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

                                        // Actualizar badge inmediatamente
                                        const badge = btnNotif.querySelector('.badge-notif');
                                        if (badge) {
                                            const currentCount = parseInt(badge.textContent);
                                            if (currentCount > 1) {
                                                badge.textContent = currentCount - 1;
                                            } else {
                                                badge.remove();
                                            }
                                        }

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

                btnClearAll.addEventListener('click', function (e) {
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

        // Polling cada 5s para actualizar el badge (más rápido)
        const _notifPollingId = setInterval(function () {
            if (!dropdownNotif.classList.contains('active')) {
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
            }
        }, 5000);

        // Limpiar polling al salir de la página para evitar memory leaks
        window.addEventListener('beforeunload', function () {
            clearInterval(_notifPollingId);
        });
    }
    // 7. Envío de Comunicados vía AJAX (Panel Superadmin)
    const formComunicado = document.getElementById('formulario-comunicado');
    if (formComunicado) {
        formComunicado.addEventListener('submit', function (e) {
            e.preventDefault();

            const btnSubmit = this.querySelector('button[type="submit"]');
            const originalText = btnSubmit.textContent;
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Enviando...';

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    // Como el PHP redirige, detectamos el final
                    if (response.ok) {
                        // Resetear todo el formulario (asunto, destinatario, mensaje)
                        this.reset();

                        // Actualizar el contador de caracteres si existe
                        const contadorTxt = this.querySelector('.contador-caracteres');
                        if (contadorTxt) {
                            const maxChars = 10000;
                            contadorTxt.textContent = `0 / ${maxChars} caracteres`;
                            contadorTxt.classList.remove('limite-cerca', 'limite-excedido');
                        }

                        // Mostrar feedback visual temporal
                        btnSubmit.textContent = '¡Enviado con éxito!';
                        btnSubmit.style.backgroundColor = 'var(--success-color)';

                        setTimeout(() => {
                            btnSubmit.disabled = false;
                            btnSubmit.textContent = originalText;
                            btnSubmit.style.backgroundColor = '';
                        }, 3000);
                    }
                })
                .catch(err => {
                    console.error('Error al enviar comunicado:', err);
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Error al enviar';
                    btnSubmit.style.backgroundColor = 'var(--error-color)';
                });
        });
    }
});
