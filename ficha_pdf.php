<?php
session_start();
include 'config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: index.php"); exit(); }

// Ficha pública: no requiere login, pero sí que el dino exista
$stmt = $conexion->prepare("SELECT * FROM dinosaurios WHERE id = :id");
$stmt->execute([':id' => $id]);
$dino = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$dino) { header("Location: index.php"); exit(); }

$stmt_mapas = $conexion->prepare("SELECT m.nombre_mapa FROM mapas m INNER JOIN dino_mapas dm ON m.id = dm.mapa_id WHERE dm.dino_id = :id");
$stmt_mapas->execute([':id' => $id]);
$mapas = $stmt_mapas->fetchAll(PDO::FETCH_COLUMN);

$stmt_cats = $conexion->prepare("SELECT c.nombre FROM categorias c INNER JOIN dino_categorias dc ON c.id = dc.categoria_id WHERE dc.dino_id = :id ORDER BY c.orden ASC");
$stmt_cats->execute([':id' => $id]);
$cats = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

$stats_data = [
    'Vida'      => ['#e74c3c', (int)($dino['stat_health']    ?? 0)],
    'Energia'   => ['#f39c12', (int)($dino['stat_stamina']   ?? 0)],
    'Oxigeno'   => ['#3498db', (int)($dino['stat_oxygen']    ?? 0)],
    'Comida'    => ['#2ecc71', (int)($dino['stat_food']      ?? 0)],
    'Peso'      => ['#9b59b6', (int)($dino['stat_weight']    ?? 0)],
    'Melee'     => ['#e67e22', (int)($dino['stat_melee']     ?? 0)],
    'Velocidad' => ['#1abc9c', (int)($dino['stat_speed']     ?? 0)],
    'Torpor'    => ['#95a5a6', (int)($dino['stat_torpidity'] ?? 0)],
];
$tiene_stats = array_sum(array_column($stats_data, 1)) > 0;
$stat_max_val = $tiene_stats ? max(array_column($stats_data, 1)) : 1;

$roles = [];
if ($dino['es_tanque'])      $roles[] = ['Tanque',      '#3498db'];
if ($dino['es_buff'])        $roles[] = ['Soporte',     '#e74c3c'];
if ($dino['es_recolector'])  $roles[] = ['Recolector',  '#2ecc71'];
if ($dino['es_montura'])     $roles[] = ['Montura',     '#f39c12'];
if ($dino['es_volador'])     $roles[] = ['Volador',     '#00bcd4'];
if ($dino['es_acuatico'])    $roles[] = ['Acuatico',    '#2196f3'];
if ($dino['es_subterraneo']) $roles[] = ['Cueva',       '#795548'];

$recursos = [];
if ($dino['recolecta_carne'])  $recursos[] = 'Carne';
if ($dino['recolecta_pescado'])$recursos[] = 'Pescado';
if ($dino['recolecta_madera']) $recursos[] = 'Madera';
if ($dino['recolecta_piedra']) $recursos[] = 'Piedra';
if ($dino['recolecta_metal'])  $recursos[] = 'Metal';
if ($dino['recolecta_bayas'])  $recursos[] = 'Bayas';
if ($dino['recolecta_paja'])   $recursos[] = 'Paja';
if ($dino['recolecta_fibra'])  $recursos[] = 'Fibra';
if ($dino['recolecta_texugo']) $recursos[] = 'Texugo';

$regiones = [];
for ($r = 0; $r < 6; $r++) {
    $nombre  = trim($dino["region_{$r}_nombre"]  ?? '');
    $colores = trim($dino["region_{$r}_colores"] ?? '');
    if ($nombre !== '' || $colores !== '') {
        $lista = array_filter(array_map('trim', explode(',', $colores)));
        $regiones[$r] = ['nombre' => $nombre ?: "Region $r", 'colores' => $lista];
    }
}

// Convertir imagen a base64 para evitar problemas CORS en html2canvas
$img_base64 = '';
if (!empty($dino['imagen'])) {
    $src_img = (strpos($dino['imagen'], 'http') === 0) ? $dino['imagen'] : null;
    if ($src_img && strpos($src_img, 'res.cloudinary.com') !== false) {
        // Pedir versión pequeña para el hero
        $src_img = str_replace('/upload/', '/upload/w_900,h_280,c_fill,g_auto,f_jpg,q_80/', $src_img);
    }
    if ($src_img) {
        // Fetch con contexto para evitar timeout
        $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
        $raw = @file_get_contents($src_img, false, $ctx);
        if ($raw) {
            $img_base64 = 'data:image/jpeg;base64,' . base64_encode($raw);
        }
    } else {
        // Imagen local
        $local = __DIR__ . '/assets/img/dinos/' . $dino['imagen'];
        if (file_exists($local)) {
            $raw = file_get_contents($local);
            $mime = mime_content_type($local) ?: 'image/jpeg';
            $img_base64 = "data:$mime;base64," . base64_encode($raw);
        }
    }
}

$nombre_archivo = 'ARK_Hub_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $dino['nombre']) . '.pdf';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ficha — <?php echo htmlspecialchars($dino['nombre']); ?></title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
  background:#0d0d0d;
  font-family:'Segoe UI', system-ui, sans-serif;
  color:#e0e0e0;
  display:flex;
  flex-direction:column;
  align-items:center;
  padding:20px;
  gap:16px;
}

.pdf-toolbar {
  display:flex;
  gap:12px;
  align-items:center;
  background:#1a1a1a;
  border:1px solid #333;
  border-radius:12px;
  padding:12px 20px;
  width:100%;
  max-width:820px;
}
.pdf-toolbar span { color:#888; font-size:0.85rem; flex:1; }
.btn-dl {
  background:#00ffcc; color:#000; border:none; border-radius:8px;
  padding:10px 22px; font-weight:800; font-size:0.9rem; cursor:pointer;
  display:flex; align-items:center; gap:8px; transition:filter 0.2s;
}
.btn-dl:hover { filter:brightness(1.15); }
.btn-back {
  background:rgba(255,255,255,0.06); color:#aaa; border:1px solid #333;
  border-radius:8px; padding:10px 18px; font-size:0.85rem; cursor:pointer;
  text-decoration:none; display:flex; align-items:center; gap:6px; transition:background 0.2s;
}
.btn-back:hover { background:rgba(255,255,255,0.1); color:#fff; }

/* ── FICHA ── */
#ficha {
  width:820px;
  background:#111;
  border-radius:14px;
  overflow:hidden;
  box-shadow:0 20px 60px rgba(0,0,0,0.8);
}

/* HERO — layout horizontal para ahorrar espacio vertical */
.pdf-hero {
  display:flex;
  height:180px;
  overflow:hidden;
  background:#0a0a0a;
  position:relative;
}
.pdf-hero-img-col {
  width:260px;
  flex-shrink:0;
  overflow:hidden;
  position:relative;
}
.pdf-hero-img-col img {
  width:100%; height:100%;
  object-fit:cover; object-position:center center;
  filter:brightness(0.75) saturate(1.2);
  display:block;
}
.pdf-hero-img-col::after {
  content:'';
  position:absolute; inset:0;
  background:linear-gradient(to right, transparent 60%, #111 100%);
}
.pdf-hero-info {
  flex:1;
  padding:22px 28px;
  display:flex;
  flex-direction:column;
  justify-content:center;
  gap:8px;
  background:linear-gradient(135deg, #161616 0%, #111 100%);
  border-left:3px solid #00ffcc;
}
.pdf-hero-nombre {
  font-size:2rem; font-weight:900; color:#fff; line-height:1;
}
.pdf-hero-especie {
  font-size:0.85rem; color:#00ffcc; font-style:italic;
}
.pdf-hero-tags {
  display:flex; flex-wrap:wrap; gap:6px; margin-top:4px;
}
.pdf-tag {
  padding:3px 10px; border-radius:20px; font-size:0.7rem; font-weight:700; border:1px solid;
}
.pdf-tag-dieta  { background:rgba(0,255,204,0.1); border-color:rgba(0,255,204,0.3); color:#00ffcc; }
.pdf-tag-mapa   { background:rgba(255,255,255,0.07); border-color:rgba(255,255,255,0.15); color:#ccc; }
.pdf-tag-cat    { background:rgba(255,152,0,0.1); border-color:rgba(255,152,0,0.3); color:#ff9800; }
.pdf-watermark {
  position:absolute; bottom:10px; right:14px;
  font-size:0.6rem; font-weight:800; color:rgba(0,255,204,0.35);
  letter-spacing:2px; text-transform:uppercase;
}

/* CUERPO */
.pdf-body { padding:20px 28px; display:flex; flex-direction:column; gap:18px; }

.pdf-section { display:flex; flex-direction:column; gap:10px; }
.pdf-section-title {
  font-size:0.65rem; font-weight:800; color:#00ffcc;
  text-transform:uppercase; letter-spacing:2px;
  padding-bottom:6px; border-bottom:1px solid rgba(0,255,204,0.2);
  display:flex; align-items:center; gap:8px;
}
.pdf-section-title::before {
  content:''; display:inline-block; width:3px; height:12px;
  background:#00ffcc; border-radius:2px;
}

/* Descripcion — compacta */
.pdf-desc {
  font-size:0.78rem; line-height:1.6; color:rgba(255,255,255,0.7);
  text-align:justify;
  background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05);
  border-radius:8px; padding:12px 14px;
  max-height:120px; overflow:hidden;
}

/* Stats — fila horizontal */
.pdf-stats-row {
  display:flex; gap:8px;
}
.pdf-stat-item {
  flex:1;
  background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07);
  border-radius:8px; padding:8px 10px;
  display:flex; flex-direction:column; gap:4px;
}
.pdf-stat-label { font-size:0.6rem; color:#777; font-weight:700; text-transform:uppercase; }
.pdf-stat-value { font-size:1.1rem; font-weight:900; }
.pdf-stat-bar-wrap { height:3px; background:rgba(255,255,255,0.07); border-radius:3px; overflow:hidden; }
.pdf-stat-bar-fill { height:100%; border-radius:3px; }

/* Fila inferior: roles + recursos + domado en columnas */
.pdf-bottom-grid {
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:14px;
}

/* Roles */
.pdf-roles { display:flex; flex-wrap:wrap; gap:6px; }
.pdf-rol-badge {
  padding:4px 10px; border-radius:20px; font-size:0.72rem; font-weight:700; border:1px solid;
}

/* Recursos */
.pdf-recursos { display:flex; flex-wrap:wrap; gap:6px; }
.pdf-recurso {
  padding:4px 10px; background:rgba(46,204,113,0.08);
  border:1px solid rgba(46,204,113,0.2); border-radius:20px;
  font-size:0.72rem; color:#2ecc71; font-weight:600;
}

/* Domado */
.pdf-info-list { display:flex; flex-direction:column; gap:6px; }
.pdf-info-row { display:flex; gap:8px; align-items:baseline; }
.pdf-info-lbl { font-size:0.65rem; color:#666; font-weight:700; text-transform:uppercase; min-width:90px; }
.pdf-info-val { font-size:0.8rem; font-weight:700; color:#e0e0e0; }

/* Regiones */
.pdf-regiones { display:flex; flex-direction:column; gap:8px; }
.pdf-region-row { display:flex; align-items:center; gap:10px; }
.pdf-region-label { font-size:0.68rem; color:#888; font-weight:700; min-width:120px; }
.pdf-color-swatches { display:flex; gap:5px; flex-wrap:wrap; }
.pdf-swatch {
  width:20px; height:20px; border-radius:5px;
  border:1px solid rgba(255,255,255,0.15); display:inline-block;
}

/* Footer */
.pdf-footer {
  background:rgba(0,255,204,0.04); border-top:1px solid rgba(0,255,204,0.1);
  padding:10px 28px; display:flex; justify-content:space-between; align-items:center;
}
.pdf-footer-brand { font-size:0.68rem; font-weight:800; color:#00ffcc; letter-spacing:1px; }
.pdf-footer-date  { font-size:0.65rem; color:#444; }
</style>
</head>
<body>

<div class="pdf-toolbar" id="toolbar">
  <span>Ficha de <strong style="color:#fff;"><?php echo htmlspecialchars($dino['nombre']); ?></strong></span>
  <a href="detalle.php?id=<?php echo $id; ?>" class="btn-back">← Volver</a>
  <button class="btn-dl" id="btn-dl" onclick="descargarPDF()">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 20h14v-2H5v2zm7-18v10.17l-3.59-3.58L7 10l5 5 5-5-1.41-1.41L13 12.17V2h-1z"/></svg>
    Descargar PDF
  </button>
</div>

<div id="ficha">

  <!-- HERO horizontal -->
  <div class="pdf-hero">
    <?php if ($img_base64): ?>
    <div class="pdf-hero-img-col">
      <img src="<?php echo $img_base64; ?>" alt="<?php echo htmlspecialchars($dino['nombre']); ?>">
    </div>
    <?php endif; ?>
    <div class="pdf-hero-info">
      <div class="pdf-hero-nombre"><?php echo htmlspecialchars($dino['nombre']); ?></div>
      <div class="pdf-hero-especie"><?php echo htmlspecialchars($dino['especie'] ?? ''); ?></div>
      <div class="pdf-hero-tags">
        <span class="pdf-tag pdf-tag-dieta"><?php echo htmlspecialchars($dino['dieta'] ?? ''); ?></span>
        <?php foreach ($mapas as $m): ?>
          <span class="pdf-tag pdf-tag-mapa"><?php echo htmlspecialchars($m); ?></span>
        <?php endforeach; ?>
        <?php foreach ($cats as $c): ?>
          <span class="pdf-tag pdf-tag-cat"><?php echo htmlspecialchars($c); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="pdf-watermark">ARK Survival Hub</div>
  </div>

  <div class="pdf-body">

    <?php if (!empty($dino['descripcion'])): ?>
    <div class="pdf-section">
      <div class="pdf-section-title">Descripcion</div>
      <div class="pdf-desc"><?php echo nl2br(htmlspecialchars($dino['descripcion'])); ?></div>
    </div>
    <?php endif; ?>

    <?php if ($tiene_stats): ?>
    <div class="pdf-section">
      <div class="pdf-section-title">Stats Base (Nivel 1 Salvaje)</div>
      <div class="pdf-stats-row">
        <?php foreach ($stats_data as $label => [$color, $val]):
          if ($val <= 0) continue;
          $pct = round(($val / $stat_max_val) * 100);
        ?>
        <div class="pdf-stat-item">
          <div class="pdf-stat-label"><?php echo $label; ?></div>
          <div class="pdf-stat-value" style="color:<?php echo $color; ?>;"><?php echo number_format($val); ?></div>
          <div class="pdf-stat-bar-wrap">
            <div class="pdf-stat-bar-fill" style="width:<?php echo $pct; ?>%; background:<?php echo $color; ?>;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="pdf-bottom-grid">

      <!-- Columna izquierda: roles + recursos -->
      <div style="display:flex; flex-direction:column; gap:14px;">

        <?php if (!empty($roles)): ?>
        <div class="pdf-section">
          <div class="pdf-section-title">Roles y Utilidad</div>
          <div class="pdf-roles">
            <?php foreach ($roles as [$nombre, $color]): ?>
            <div class="pdf-rol-badge" style="background:<?php echo $color; ?>18; border-color:<?php echo $color; ?>55; color:<?php echo $color; ?>;">
              <?php echo $nombre; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php if (!empty($dino['buff_descripcion'])): ?>
          <div style="font-size:0.72rem; color:rgba(255,255,255,0.6); line-height:1.5; padding:8px 10px; background:rgba(255,255,255,0.02); border-radius:6px; border:1px solid rgba(255,255,255,0.05);">
            <?php echo nl2br(htmlspecialchars(mb_substr($dino['buff_descripcion'], 0, 200))); ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($recursos)): ?>
        <div class="pdf-section">
          <div class="pdf-section-title">Recoleccion</div>
          <div class="pdf-recursos">
            <?php foreach ($recursos as $r): ?>
            <div class="pdf-recurso"><?php echo $r; ?></div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- Columna derecha: domado + regiones -->
      <div style="display:flex; flex-direction:column; gap:14px;">

        <?php
        $hay_domado = !empty($dino['metodo_domado']) || !empty($dino['comida_favorita'])
                   || $dino['nivel_max_salvaje'] > 0 || $dino['tiempo_incubacion'] > 0
                   || $dino['tiempo_madurez'] > 0;
        if ($hay_domado): ?>
        <div class="pdf-section">
          <div class="pdf-section-title">Domado y Cria</div>
          <div class="pdf-info-list">
            <?php if (!empty($dino['metodo_domado'])): ?>
            <div class="pdf-info-row">
              <span class="pdf-info-lbl">Metodo</span>
              <span class="pdf-info-val"><?php echo htmlspecialchars($dino['metodo_domado']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($dino['comida_favorita'])): ?>
            <div class="pdf-info-row">
              <span class="pdf-info-lbl">Comida fav.</span>
              <span class="pdf-info-val"><?php echo htmlspecialchars($dino['comida_favorita']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($dino['nivel_max_salvaje'] > 0): ?>
            <div class="pdf-info-row">
              <span class="pdf-info-lbl">Nivel max.</span>
              <span class="pdf-info-val" style="color:#00ffcc;"><?php echo (int)$dino['nivel_max_salvaje']; ?></span>
            </div>
            <?php endif; ?>
            <?php if ($dino['tiempo_incubacion'] > 0):
              $m = (int)$dino['tiempo_incubacion'];
              $t = $m < 60 ? "$m min" : ($m < 1440 ? round($m/60,1).' h' : round($m/1440,1).' dias');
            ?>
            <div class="pdf-info-row">
              <span class="pdf-info-lbl">Incubacion</span>
              <span class="pdf-info-val"><?php echo $t; ?></span>
            </div>
            <?php endif; ?>
            <?php if ($dino['tiempo_madurez'] > 0):
              $m = (int)$dino['tiempo_madurez'];
              $t = $m < 60 ? "$m min" : ($m < 1440 ? round($m/60,1).' h' : round($m/1440,1).' dias');
            ?>
            <div class="pdf-info-row">
              <span class="pdf-info-lbl">Madurez</span>
              <span class="pdf-info-val"><?php echo $t; ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($regiones)): ?>
        <div class="pdf-section">
          <div class="pdf-section-title">Regiones de Color</div>
          <div class="pdf-regiones">
            <?php foreach ($regiones as $idx => $reg): ?>
            <div class="pdf-region-row">
              <div class="pdf-region-label">R<?php echo $idx; ?> — <?php echo htmlspecialchars($reg['nombre']); ?></div>
              <div class="pdf-color-swatches">
                <?php foreach ($reg['colores'] as $hex):
                  if (!preg_match('/^#[0-9A-Fa-f]{3,6}$/', $hex)) continue;
                ?>
                <span class="pdf-swatch" style="background:<?php echo $hex; ?>;"></span>
                <?php endforeach; ?>
                <?php if (empty($reg['colores'])): ?>
                  <span style="font-size:0.65rem; color:#444; font-style:italic;">Sin colores</span>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div><!-- /pdf-bottom-grid -->

  </div><!-- /pdf-body -->

  <div class="pdf-footer">
    <div class="pdf-footer-brand">ARK SURVIVAL HUB</div>
    <div class="pdf-footer-date">Generado el <?php echo date('d/m/Y'); ?></div>
  </div>

</div><!-- /ficha -->

<script>
const nombreArchivo = <?php echo json_encode($nombre_archivo); ?>;

function descargarPDF() {
  const btn = document.getElementById('btn-dl');
  const toolbar = document.getElementById('toolbar');
  const ficha = document.getElementById('ficha');

  btn.textContent = 'Generando...';
  btn.disabled = true;
  toolbar.style.display = 'none';

  // Medir altura real del contenido para que quepa en una sola página
  const W = ficha.offsetWidth;
  const H = ficha.offsetHeight;

  const opt = {
    margin:      0,
    filename:    nombreArchivo,
    image:       { type: 'jpeg', quality: 0.92 },
    html2canvas: {
      scale: 2,
      useCORS: true,
      allowTaint: false,
      backgroundColor: '#111111',
      logging: false,
      width: W,
      height: H
    },
    jsPDF: {
      unit: 'px',
      format: [W, H],   // formato exacto al contenido → siempre 1 página
      orientation: 'portrait',
      hotfixes: ['px_scaling']
    },
    pagebreak: { mode: [] }  // sin saltos de página
  };

  html2pdf().set(opt).from(ficha).save().then(() => {
    toolbar.style.display = 'flex';
    btn.innerHTML = '&#10003; Descargado';
    btn.style.background = '#2ecc71';
    setTimeout(() => {
      btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 20h14v-2H5v2zm7-18v10.17l-3.59-3.58L7 10l5 5 5-5-1.41-1.41L13 12.17V2h-1z"/></svg> Descargar PDF';
      btn.style.background = '#00ffcc';
      btn.disabled = false;
    }, 2500);
  });
}

<?php if (isset($_GET['auto'])): ?>
window.addEventListener('load', () => setTimeout(descargarPDF, 800));
<?php endif; ?>
</script>
</body>
</html>
