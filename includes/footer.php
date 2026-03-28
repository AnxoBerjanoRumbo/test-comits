    </main>
    <footer class="footer-principal">
        <p class="footer-copyright">
            &copy; <?php echo date('Y'); ?> <strong>ARK Survival Hub</strong> - Wiki de Supervivencia.
        </p>
        <p class="footer-texto">
            Desarrollado para la comunidad de supervivientes. Todos los derechos reservados.
        </p>
    </footer>

    <!-- Panel de Accesibilidad (Izquierda) -->
    <div class="accessibility-panel">
        <div id="accMenu" class="accessibility-menu">
            <p style="font-size: 0.7rem; color: var(--accent); margin-bottom: 8px; text-align: center; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;">Temas Atmosféricos</p>
            
            <div class="theme-opt" onclick="setTheme('ragnarok')">
                <span class="dot" style="background: #00ffcc;"></span> Ragnarok
            </div>
            <div class="theme-opt" onclick="setTheme('aberration')">
                <span class="dot" style="background: #b700ff;"></span> Aberration
            </div>
            <div class="theme-opt" onclick="setTheme('extinction')">
                <span class="dot" style="background: #ffcc00;"></span> Extinction
            </div>
            <div class="theme-opt" onclick="setTheme('scorched')">
                <span class="dot" style="background: #ff6600;"></span> Scorched
            </div>

            <div class="theme-opt" onclick="setTheme('daltonico')" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 5px; padding-top: 10px;">
                <span class="material-symbols-outlined" style="font-size: 1.2rem;">visibility</span> Accesibilidad
            </div>
        </div>
        <button id="btnAcc" class="btn-accessibility" title="Temas y Accesibilidad">
            <span class="material-symbols-outlined">palette</span>
        </button>
    </div>

    <!-- Botón Scroll Arriba (Derecha) -->
    <div id="scrollContainer" class="scroll-container">
        <button id="btnArriba" class="btn-scroll-main" title="Volver arriba">
            <span class="material-symbols-outlined">expand_less</span>
        </button>
    </div>

    <!-- Scripts Generales -->
    <script src="<?php echo ($path_prefix ?? ''); ?>assets/js/main.js"></script>
</body>
</html>
