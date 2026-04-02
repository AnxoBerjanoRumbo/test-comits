/**
 * Tablón de referencia de stats ARK - carga datos desde assets/data/ark_creatures.json
 */

let ARK_DATA = [];

function buildStatsReferencePanel() {
    if (document.getElementById('stats-ref-panel')) return;

    // Botón flotante
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'btn-stats-ref';
    btn.innerHTML = '<span class="material-symbols-outlined">table_chart</span> Referencia Stats';
    document.body.appendChild(btn);

    // Panel
    const panel = document.createElement('div');
    panel.id = 'stats-ref-panel';
    panel.innerHTML = `
        <div class="stats-ref-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="material-symbols-outlined" style="color:var(--accent);">table_chart</span>
                <h3 style="margin:0;font-size:1rem;color:var(--accent);text-transform:uppercase;letter-spacing:1px;">Referencia Criaturas ARK</h3>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <input type="text" id="stats-ref-search" placeholder="Buscar criatura..."
                    style="padding:6px 12px;border-radius:6px;border:1px solid var(--border-color);background:var(--input-bg);color:var(--input-text);font-family:inherit;font-size:0.85rem;outline:none;width:180px;">
                <button type="button" id="btn-close-ref" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.4rem;line-height:1;padding:0 4px;">&times;</button>
            </div>
        </div>
        <p style="font-size:0.75rem;color:var(--text-muted);padding:8px 16px 4px;margin:0;">
            Clic en una fila para autorellenar <strong style="color:var(--accent);">todos</strong> los campos del formulario. Los campos de texto libre (descripción, etc.) no se tocan.
        </p>
        <div class="stats-ref-table-wrap">
            <table class="stats-ref-table" id="stats-ref-table">
                <thead>
                    <tr>
                        <th>Criatura</th><th>Dieta</th>
                        <th>❤️ Vida</th><th>⚡ Stam</th><th>💧 Ox</th><th>🍖 Food</th>
                        <th>⚖️ Peso</th><th>⚔️ Melée</th><th>💨 Vel</th><th>😴 Torpor</th>
                        <th>Iw H</th><th>Iw M</th>
                        <th>Roles</th><th>Método</th><th>Comida</th>
                    </tr>
                </thead>
                <tbody id="stats-ref-tbody">
                    <tr><td colspan="15" style="text-align:center;padding:20px;color:var(--text-muted);">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>
    `;
    document.body.appendChild(panel);

    // Cargar JSON
    const base = document.querySelector('base')?.href || window.location.origin;
    const isAdmin = window.location.pathname.includes('/admin/');
    const jsonPath = (isAdmin ? '../' : '') + 'assets/data/ark_creatures.json';

    fetch(jsonPath)
        .then(r => r.json())
        .then(data => {
            ARK_DATA = data.criaturas || [];
            renderTable(ARK_DATA);
        })
        .catch(() => {
            document.getElementById('stats-ref-tbody').innerHTML =
                '<tr><td colspan="15" style="text-align:center;padding:20px;color:#ff4444;">Error al cargar ark_creatures.json</td></tr>';
        });

    // Buscador
    document.getElementById('stats-ref-search').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        renderTable(ARK_DATA.filter(c => c.nombre.toLowerCase().includes(q)));
    });

    // Cerrar
    document.getElementById('btn-close-ref').addEventListener('click', () => panel.classList.remove('open'));

    // Toggle
    btn.addEventListener('click', () => {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) document.getElementById('stats-ref-search').focus();
    });
}

function renderTable(data) {
    const tbody = document.getElementById('stats-ref-tbody');
    if (!tbody) return;
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="15" style="text-align:center;padding:20px;color:var(--text-muted);">Sin resultados</td></tr>';
        return;
    }
    tbody.innerHTML = '';
    data.forEach(c => {
        const s = c.stats;
        const r = c.roles;
        const roles = [
            r.es_tanque    ? '🛡️' : '',
            r.es_buff      ? '📈' : '',
            r.es_recolector? '📦' : '',
            r.es_montura   ? '🐴' : '',
            r.es_volador   ? '🦅' : '',
            r.es_acuatico  ? '🐳' : '',
            r.es_subterraneo? '🦇' : '',
        ].filter(Boolean).join(' ');

        const tr = document.createElement('tr');
        tr.title = 'Clic para autorellenar el formulario con los datos de ' + c.nombre;
        tr.innerHTML = `
            <td style="font-weight:700;color:var(--accent);white-space:nowrap;">${c.nombre}</td>
            <td style="white-space:nowrap;">${c.dieta}</td>
            <td>${s.stat_health}</td><td>${s.stat_stamina}</td><td>${s.stat_oxygen}</td><td>${s.stat_food}</td>
            <td>${s.stat_weight}</td><td>${s.stat_melee}%</td><td>${s.stat_speed}%</td><td>${s.stat_torpidity}</td>
            <td style="color:#aaa;">${s.iw_health}</td><td style="color:#aaa;">${s.iw_melee}</td>
            <td style="font-size:1rem;">${roles || '—'}</td>
            <td style="white-space:nowrap;">${c.domesticacion.metodo_domado}</td>
            <td style="white-space:nowrap;">${c.domesticacion.comida_favorita}</td>
        `;
        tr.addEventListener('click', () => autofillForm(c));
        tbody.appendChild(tr);
    });
}

function autofillForm(c) {
    const s = c.stats;
    const r = c.roles;
    const b = c.buffs;
    const rec = c.recoleccion;
    const dom = c.domesticacion;
    const cria = c.cria;

    // Campos de texto/número simples
    const simple = {
        nombre: c.nombre, especie: c.especie,
        audio_url: c.audio || '',
        ...s,
        buff_damage: b.buff_damage, buff_armor: b.buff_armor,
        buff_speed: b.buff_speed, buff_otro: b.buff_otro,
        metodo_domado: dom.metodo_domado, comida_favorita: dom.comida_favorita,
        nivel_max_salvaje: dom.nivel_max_salvaje,
        tiempo_incubacion: cria.tiempo_incubacion, tiempo_madurez: cria.tiempo_madurez,
    };
    Object.entries(simple).forEach(([key, val]) => {
        const el = document.querySelector(`[name="${key}"]`);
        if (el && el.tagName !== 'SELECT') el.value = val;
    });

    // Select dieta
    const selectDieta = document.querySelector('select[name="dieta"]');
    if (selectDieta) selectDieta.value = c.dieta;

    // Select domable
    const selectDomable = document.querySelector('select[name="domable"]');
    if (selectDomable) selectDomable.value = dom.domable;

    // Checkboxes de roles
    const checkboxes = {
        es_tanque: r.es_tanque, es_buff: r.es_buff, es_recolector: r.es_recolector,
        es_montura: r.es_montura, es_volador: r.es_volador, es_acuatico: r.es_acuatico,
        es_subterraneo: r.es_subterraneo,
        tiene_formas: c.formas.tiene_formas, ayuda_cria: cria.ayuda_cria,
        ...rec,
    };
    Object.entries(checkboxes).forEach(([key, val]) => {
        const el = document.querySelector(`input[type="checkbox"][name="${key}"]`);
        if (el) el.checked = !!val;
    });

    // Mapas: marcar los que coincidan por nombre
    document.querySelectorAll('input[type="checkbox"][name="mapas[]"]').forEach(cb => {
        const label = cb.closest('label')?.querySelector('span')?.textContent?.trim() || '';
        cb.checked = c.mapas.some(m => label.toLowerCase().includes(m.toLowerCase()) || m.toLowerCase().includes(label.toLowerCase()));
    });

    // Flash confirmación
    const btn = document.getElementById('btn-stats-ref');
    const orig = btn.innerHTML;
    btn.style.background = 'var(--success-color, #00e676)';
    btn.style.color = '#000';
    btn.innerHTML = '<span class="material-symbols-outlined">check</span> ¡Rellenado!';
    setTimeout(() => {
        btn.style.background = '';
        btn.style.color = '';
        btn.innerHTML = orig;
    }, 2000);

    document.getElementById('stats-ref-panel').classList.remove('open');
    const firstField = document.querySelector('input[name="nombre"]');
    if (firstField) firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Estilos
const style = document.createElement('style');
style.textContent = `
#btn-stats-ref {
    position: fixed; bottom: 30px; right: 100px; z-index: 1100;
    background: var(--accent); color: var(--accent-text, #000);
    border: none; border-radius: 10px; padding: 12px 18px;
    font-family: inherit; font-size: 0.85rem; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 20px rgba(var(--accent-rgb), 0.4);
    transition: all 0.3s; text-transform: uppercase; letter-spacing: 0.5px;
}
#btn-stats-ref:hover { filter: brightness(1.15); transform: translateY(-2px); }
#stats-ref-panel {
    position: fixed; bottom: 90px; right: 20px;
    width: min(96vw, 1100px); max-height: 72vh;
    background: var(--bg-card, #1e1e1e); border: 1px solid var(--accent);
    border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,0.7);
    z-index: 1099; display: flex; flex-direction: column; overflow: hidden;
    transform: translateY(20px) scale(0.97); opacity: 0; pointer-events: none;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
#stats-ref-panel.open { transform: translateY(0) scale(1); opacity: 1; pointer-events: all; }
.stats-ref-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 16px; border-bottom: 1px solid var(--border-color, #333);
    background: rgba(var(--accent-rgb), 0.06); flex-shrink: 0;
}
.stats-ref-table-wrap { overflow: auto; flex: 1; }
.stats-ref-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; white-space: nowrap; }
.stats-ref-table thead th {
    position: sticky; top: 0; background: var(--bg-header, #181818);
    color: var(--text-muted, #aaa); padding: 8px 10px; text-align: right;
    font-weight: 700; border-bottom: 1px solid var(--border-color, #333); font-size: 0.75rem;
}
.stats-ref-table thead th:first-child, .stats-ref-table thead th:nth-child(2) { text-align: left; }
.stats-ref-table tbody tr { cursor: pointer; transition: background 0.15s; }
.stats-ref-table tbody tr:hover { background: rgba(var(--accent-rgb), 0.1); }
.stats-ref-table tbody td {
    padding: 7px 10px; border-bottom: 1px solid rgba(255,255,255,0.04);
    text-align: right; color: var(--text-main, #e0e0e0);
}
.stats-ref-table tbody td:first-child,
.stats-ref-table tbody td:nth-child(2) { text-align: left; }
@media (max-width: 600px) {
    #btn-stats-ref { right: 20px; bottom: 90px; }
    #stats-ref-panel { bottom: 150px; right: 10px; left: 10px; width: auto; }
}
`;
document.head.appendChild(style);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', buildStatsReferencePanel);
} else {
    buildStatsReferencePanel();
}
