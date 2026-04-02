<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || ($_SESSION['p_insertar'] ?? 0) == 0) {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

$sql_mapas = "SELECT * FROM mapas ORDER BY nombre_mapa ASC";
$stmt_mapas = $conexion->prepare($sql_mapas);
$stmt_mapas->execute();
$mapas = $stmt_mapas->fetchAll(PDO::FETCH_ASSOC);

$sql_cats = "SELECT * FROM categorias ORDER BY orden ASC";
$stmt_cats = $conexion->prepare($sql_cats);
$stmt_cats->execute();
$categorias = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Añadir Criatura</title>
    <link rel="stylesheet" href="../assets/css/estilos.css?v=1.3">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="../assets/js/stats_reference.js" defer></script>
</head>
<body class="admin-body">
    <?php 
    $is_admin_panel = true;
    $header_titulo = "Panel de Administración";
    $header_volver_link = "../index.php";
    $header_volver_texto = "Volver a la Wiki";
    include '../includes/header.php'; 
    ?>

    <main class="contenedor-formulario">
        <h2>Registrar nueva criatura</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="alerta-error">
                <?php 
                if ($_GET['error'] == 'duplicado') echo "⚠️ El dinosaurio <strong>".htmlspecialchars($_GET['nombre'])."</strong> ya existe en el sistema.";
                elseif ($_GET['error'] == 'formato') echo "⚠️ Formato de imagen no válido o archivo dañado. Usa JPG, PNG o WebP.";
                elseif ($_GET['error'] == 'interno') echo "⚠️ Error interno del servidor al procesar los datos.";
                ?>
            </div>
        <?php endif; ?>

        <form action="../actions/admin/procesar_insertar.php" method="POST" enctype="multipart/form-data" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="campo">
                <label>Nombre de la criatura:</label>
                <input type="text" name="nombre" required placeholder="Ej: Thylacoleo" maxlength="40">
            </div>

            <div class="campo">
                <label>Especie:</label>
                <input type="text" name="especie" required placeholder="Ej: Thylacoleo furtimorsus" maxlength="60">
            </div>

            <div class="campo">
                <label>Dieta principal:</label>
                <select name="dieta" required>
                    <option value="Carnívoro">Carnívoro</option>
                    <option value="Herbívoro">Herbívoro</option>
                    <option value="Omnívoro">Omnívoro</option>
                    <option value="Piscívoro">Piscívoro</option>
                </select>
            </div>

            <div class="campo">
                <label>Descripción:</label>
                <textarea name="descripcion" required placeholder="Aquí va la información importante de la criatura (habilidades, tameo, etc.)..." rows="10" maxlength="10000"></textarea>
            </div>

            <div class="campo">
                <label>Imagen de la criatura:</label>
                <input type="file" name="imagen" accept="image/*" required>
            </div>

            <div class="campo">
                <label>URL de Audio del Dossier: <span style="font-weight:400; color:var(--text-muted); font-size:0.85rem;">(opcional)</span></label>
                <input type="url" name="audio_url" placeholder="https://ark.wiki.gg/images/.../Dossier_NombreDino_VO.ogg">
                <small class="texto-auxiliar">
                    URL del audio del dossier oficial. Consúltalo en 
                    <a href="https://ark.wiki.gg" target="_blank" style="color:var(--accent);">ark.wiki.gg</a> 
                    o busca el nombre de la criatura en el 
                    <a href="../assets/data/ark_creatures.json" target="_blank" style="color:var(--accent);">JSON de referencia</a> 
                    (campo <code style="background:rgba(255,255,255,0.08);padding:1px 4px;border-radius:3px;">"audio"</code>).
                </small>
            </div>

            <div class="campo">
                <label>Mapas de avistamiento:</label>
                <div class="grid-checkboxes">
                    <?php foreach ($mapas as $mapa): ?>
                        <label class="checkbox-tag">
                            <input type="checkbox" name="mapas[]" value="<?php echo $mapa['id']; ?>">
                            <span><?php echo htmlspecialchars($mapa['nombre_mapa']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo">
                <label>Categorías de la criatura:</label>
                <div class="grid-checkboxes">
                    <?php foreach ($categorias as $cat): ?>
                        <label class="checkbox-tag">
                            <input type="checkbox" name="categorias[]" value="<?php echo $cat['id']; ?>">
                            <span><?php echo htmlspecialchars($cat['nombre']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Stats Base ARK -->
            <div class="campo">
                <label style="font-size:1rem; font-weight:bold; color:var(--accent); margin-bottom:15px; display:block;">
                    <span class="material-symbols-outlined" style="vertical-align:middle; font-size:1.2rem;">radar</span>
                    Stats Base (Nivel 1 Salvaje)
                </label>
                <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:20px;">Introduce el valor base y el multiplicador Iw de cada stat (consulta ARK Wiki o ARKBreedingStats). El Iw es el % de incremento por nivel salvaje.</p>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:18px;">
                    <?php
                    $stats = [
                        'stat_health'    => ['Vida (Health)',         'favorite',   '#e74c3c', 'iw_health',    0.2],
                        'stat_stamina'   => ['Energía (Stamina)',      'bolt',       '#f39c12', 'iw_stamina',   0.1],
                        'stat_oxygen'    => ['Oxígeno (Oxygen)',       'water_drop', '#3498db', 'iw_oxygen',    0.1],
                        'stat_food'      => ['Comida (Food)',          'restaurant', '#2ecc71', 'iw_food',      0.15],
                        'stat_weight'    => ['Peso (Weight)',          'weight',     '#9b59b6', 'iw_weight',    0.02],
                        'stat_melee'     => ['Daño Cuerpo a Cuerpo',   'swords',     '#e67e22', 'iw_melee',     0.05],
                        'stat_speed'     => ['Velocidad (%)',          'speed',      '#1abc9c', 'iw_speed',     0.0],
                        'stat_torpidity' => ['Torpor (Inconsciencia)', 'bedtime',    '#95a5a6', 'iw_torpidity', 0.06],
                    ];
                    foreach ($stats as $key => [$label, $icon, $color, $iw_key, $iw_default]): ?>
                    <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; padding:15px;">
                        <label style="display:flex; align-items:center; gap:8px; font-size:0.88rem; font-weight:600; color:<?php echo $color; ?>; margin-bottom:10px;">
                            <span class="material-symbols-outlined" style="font-size:1.1rem;"><?php echo $icon; ?></span>
                            <?php echo $label; ?>
                        </label>
                        <input type="number" name="<?php echo $key; ?>" min="0" max="99999" value="0"
                               style="font-size:1.1rem; font-weight:700; color:<?php echo $color; ?>; border-color:<?php echo $color; ?>33;">
                        <label style="display:block; font-size:0.75rem; color:var(--text-muted); margin-top:8px; margin-bottom:4px;">Iw (multiplicador por nivel wild)</label>
                        <input type="number" name="<?php echo $iw_key; ?>" min="0" max="2" step="0.001" value="<?php echo $iw_default; ?>"
                               style="font-size:0.9rem; color:var(--text-muted); border-color:rgba(255,255,255,0.1);">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Roles y Utilidad -->
            <div class="campo">
                <label style="font-size:1rem; font-weight:bold; color:var(--accent); margin-bottom:15px; display:block;">
                    <span class="material-symbols-outlined" style="vertical-align:middle; font-size:1.2rem;">stars</span>
                    Roles y Utilidad
                </label>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:12px;">
                    <label class="checkbox-tag">
                        <input type="checkbox" name="es_tanque" value="1">
                        <span>🛡️ Tanque</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="es_buff" value="1">
                        <span>📈 Buff/Boost</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="es_recolector" value="1">
                        <span>📦 Recolector</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="es_montura" value="1">
                        <span>🐴 Montura</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="es_volador" value="1">
                        <span>🦅 Volador</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="es_acuatico" value="1">
                        <span>🐳 Acuático</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="es_subterraneo" value="1">
                        <span>🦇 Subterráneo</span>
                    </label>
                </div>
            </div>

            <!-- Buffs y Habilidades Especiales -->
            <div class="campo">
                <label style="font-size:1rem; font-weight:bold; color:var(--accent); margin-bottom:15px; display:block;">
                    <span class="material-symbols-outlined" style="vertical-align:middle; font-size:1.2rem;">bolt</span>
                    Buffs y Habilidades (Yutyrannus, etc.)
                </label>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:18px;">
                    <div>
                        <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">Descripción del buff</label>
                        <textarea name="buff_descripcion" rows="3" placeholder="Ej: Su rugido aumenta el daño aliado un 25%..."></textarea>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
                        <div>
                            <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">% Daño</label>
                            <input type="number" name="buff_damage" min="0" max="100" value="0" placeholder="25">
                        </div>
                        <div>
                            <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">% Armadura</label>
                            <input type="number" name="buff_armor" min="0" max="100" value="0" placeholder="20">
                        </div>
                        <div>
                            <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">% Velocidad</label>
                            <input type="number" name="buff_speed" min="0" max="100" value="0" placeholder="10">
                        </div>
                    </div>
                    <div>
                        <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">Otro bonus</label>
                        <input type="text" name="buff_otro" value="" placeholder="Ej: +50% resistencia al frío">
                    </div>
                </div>
            </div>

            <!-- Formas Especiales (Stego) -->
            <div class="campo">
                <label style="font-size:1rem; font-weight:bold; color:var(--accent); margin-bottom:15px; display:block;">
                    <span class="material-symbols-outlined" style="vertical-align:middle; font-size:1.2rem;">transform</span>
                    Formas Especiales (Stegosaurus, etc.)
                </label>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:15px;">
                    <input type="checkbox" name="tiene_formas" id="tiene_formas" value="1" style="width:20px; height:20px;">
                    <label for="tiene_formas" style="font-size:0.95rem; color:var(--text-main);">¿Tiene diferentes formas/posiciones?</label>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">Descripción de las formas</label>
                    <textarea name="formas_descripcion" rows="4" placeholder="Ej: Placas Pesadas (mitigación), Placas Afiladas (slow), Placas Buff (recolección)..."></textarea>
                </div>
            </div>

            <!-- Recolección -->
            <div class="campo">
                <label style="font-size:1rem; font-weight:bold; color:var(--accent); margin-bottom:15px; display:block;">
                    <span class="material-symbols-outlined" style="vertical-align:middle; font-size:1.2rem;">inventory_2</span>
                    Recursos que puede Recolectar
                </label>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:12px;">
                    <label class="checkbox-tag">
                        <input type="checkbox" name="recolecta_carne" value="1">
                        <span>🥩 Carne</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="recolecta_pescado" value="1">
                        <span>🐟 Pescado</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="recolecta_madera" value="1">
                        <span>🪵 Madera</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="recolecta_piedra" value="1">
                        <span>🪨 Piedra</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="recolecta_metal" value="1">
                        <span>⛏️ Metal</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="recolecta_bayas" value="1">
                        <span>🫐 Bayas</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="recolecta_paja" value="1">
                        <span>🌾 Paja</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="recolecta_fibra" value="1">
                        <span>🌿 Fibra</span>
                    </label>
                    <label class="checkbox-tag">
                        <input type="checkbox" name="recolecta_texugo" value="1">
                        <span>🐾 Texugo</span>
                    </label>
                </div>
            </div>

            <!-- Domesticación y Cría -->
            <div class="campo">
                <label style="font-size:1rem; font-weight:bold; color:var(--accent); margin-bottom:15px; display:block;">
                    <span class="material-symbols-outlined" style="vertical-align:middle; font-size:1.2rem;">pets</span>
                    Domesticación y Cría
                </label>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:18px;">
                    <div>
                        <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">¿Domable?</label>
                        <select name="domable" style="width:100%;">
                            <option value="1" selected>Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">Método de domado</label>
                        <input type="text" name="metodo_domado" value="" placeholder="Emergente, Pasivo, Knockout...">
                    </div>
                    <div>
                        <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">Comida favorita</label>
                        <input type="text" name="comida_favorita" value="" placeholder="Kibble, Carne cruda, Vegetales...">
                    </div>
                    <div>
                        <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">Nivel máximo salvaje</label>
                        <input type="number" name="nivel_max_salvaje" min="1" max="500" value="150">
                    </div>
                    <div>
                        <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">Tiempo incubación (min)</label>
                        <input type="number" name="tiempo_incubacion" min="0" value="0">
                    </div>
                    <div>
                        <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">Tiempo madurez (min)</label>
                        <input type="number" name="tiempo_madurez" min="0" value="0">
                    </div>
                </div>
            </div>

            <!-- Ayuda a Cría (Gigantoraptor) -->
            <div class="campo">
                <label style="font-size:1rem; font-weight:bold; color:var(--accent); margin-bottom:15px; display:block;">
                    <span class="material-symbols-outlined" style="vertical-align:middle; font-size:1.2rem;">egg</span>
                    Ayuda a Cría (Gigantoraptor, etc.)
                </label>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:15px;">
                    <input type="checkbox" name="ayuda_cria" id="ayuda_cria" value="1" style="width:20px; height:20px;">
                    <label for="ayuda_cria" style="font-size:0.95rem; color:var(--text-main);">¿Ayuda en la cría de otros dinos?</label>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:8px;">Descripción de la ayuda</label>
                    <textarea name="ayuda_cria_descripcion" rows="4" placeholder="Ej: El Gigantoraptor aumenta la efectividad de domesticación de crías salvajes..."></textarea>
                </div>
            </div>

            <!-- Regiones de Color -->
            <div class="campo">
                <label style="font-size:1rem; font-weight:bold; color:var(--accent); margin-bottom:15px; display:block;">
                    <span class="material-symbols-outlined" style="vertical-align:middle; font-size:1.2rem;">palette</span>
                    Regiones de Color (ARK tiene 6 regiones por criatura)
                </label>
                <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:20px;">
                    Cada criatura tiene hasta 6 regiones de color (0-5). Indica el nombre de la región y los colores disponibles separados por coma (en formato HEX, ej: <code style="background:rgba(255,255,255,0.08);padding:1px 5px;border-radius:3px;">#FF0000, #00FF00, #0000FF</code>). Deja vacío si la región no existe en esta criatura.
                </p>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:16px;">
                    <?php
                    $region_labels = ['Cuerpo principal','Secundario','Terciario','Acentos','Detalles','Extras'];
                    for ($r = 0; $r < 6; $r++): ?>
                    <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; padding:15px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                            <span style="background:rgba(var(--accent-rgb),0.15); color:var(--accent); font-size:0.75rem; font-weight:800; padding:3px 8px; border-radius:20px;">Región <?php echo $r; ?></span>
                            <span style="font-size:0.8rem; color:var(--text-muted);"><?php echo $region_labels[$r]; ?></span>
                        </div>
                        <label style="font-size:0.78rem; color:var(--text-muted); display:block; margin-bottom:5px;">Nombre de la región</label>
                        <input type="text" name="region_<?php echo $r; ?>_nombre" placeholder="Ej: Cuerpo, Aletas, Cresta..." maxlength="60"
                            style="margin-bottom:8px;">
                        <label style="font-size:0.78rem; color:var(--text-muted); display:block; margin-bottom:5px;">Colores disponibles (HEX separados por coma)</label>
                        <input type="text" name="region_<?php echo $r; ?>_colores" placeholder="#1a1a1a, #ff4444, #ffffff..."
                            class="region-colores-input" data-region="<?php echo $r; ?>">
                        <div id="preview-region-<?php echo $r; ?>" style="display:flex; flex-wrap:wrap; gap:4px; margin-top:8px; min-height:20px;"></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <button type="submit" class="boton-insertar">Añadir a la base de datos</button>

            <script>
            document.querySelectorAll('.region-colores-input').forEach(input => {
                input.addEventListener('input', function() {
                    const r = this.dataset.region;
                    const preview = document.getElementById('preview-region-' + r);
                    preview.innerHTML = '';
                    this.value.split(',').forEach(c => {
                        const hex = c.trim();
                        if (/^#[0-9A-Fa-f]{3,6}$/.test(hex)) {
                            const dot = document.createElement('span');
                            dot.style.cssText = `display:inline-block;width:20px;height:20px;border-radius:50%;background:${hex};border:2px solid rgba(255,255,255,0.2);title="${hex}"`;
                            dot.title = hex;
                            preview.appendChild(dot);
                        }
                    });
                });
            });
            </script>
        </form>
    <?php include '../includes/footer.php'; ?>