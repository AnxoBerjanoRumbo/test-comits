    </main>
    <footer class="footer-principal">
        <p class="footer-copyright">
            &copy; <?php echo date('Y'); ?> <strong>ARK Survival Hub</strong> - Wiki de Supervivencia.
        </p>
        <p class="footer-texto">
            Desarrollado para la comunidad de supervivientes. Todos los derechos reservados.
        </p>
    </footer>

    <!-- Panel de Funciones Flotantes (Izquierda) -->
    <div class="accessibility-panel">
        
        <!-- Botón y Menú de Música -->
        <div style="position: relative; display: flex; align-items: center;">
            
            <button id="btnMusicToggle" class="btn-accessibility" title="Reproductor de Música ARK" onclick="document.getElementById('musicPanel').classList.toggle('active')">
                <span class="material-symbols-outlined">queue_music</span>
            </button>
            
            <div id="musicPanel" class="accessibility-menu" style="position: absolute; left: 70px; bottom: 0; width: 240px; margin: 0; cursor: default;">
                <div class="music-track-info" onclick="document.getElementById('musicPlaylist').classList.toggle('open')" style="cursor: pointer; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                        <span class="material-symbols-outlined music-note-icon" id="musicNoteIcon">music_note</span>
                        <span id="musicTrackName" title="Ver canciones" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Waking Up (Main Theme)</span>
                    </div>
                    <span class="material-symbols-outlined" style="font-size: 1.1rem; opacity: 0.7;">expand_more</span>
                </div>
                <div class="music-controls">
                    <button class="music-btn" id="musicPrevBtn" onclick="musicPrev()" title="Anterior">
                        <span class="material-symbols-outlined">skip_previous</span>
                    </button>
                    <button class="music-btn music-btn-play" id="musicPlayBtn" onclick="musicToggle()" title="Reproducir / Pausar">
                        <span class="material-symbols-outlined" id="musicPlayIcon">play_arrow</span>
                    </button>
                    <button class="music-btn" id="musicNextBtn" onclick="musicNext()" title="Siguiente">
                        <span class="material-symbols-outlined">skip_next</span>
                    </button>
                    <button class="music-btn" id="musicMuteBtn" onclick="musicToggleMute()" title="Silenciar">
                        <span class="material-symbols-outlined" id="musicMuteIcon">volume_up</span>
                    </button>
                    <input type="range" id="musicVolume" class="music-volume-slider" min="0" max="100" value="40"
                        oninput="musicSetVolume(this.value)" title="Volumen">
                </div>
                <div class="music-playlist" id="musicPlaylist">
                    <!-- generado por JS -->
                </div>
            </div>
        </div>

        <!-- Botón y Menú de Accesibilidad -->
        <div style="position: relative; display: flex; align-items: center;">
            <button id="btnAcc" class="btn-accessibility" title="Temas y Accesibilidad">
                <span class="material-symbols-outlined">palette</span>
            </button>
            <div id="accMenu" class="accessibility-menu" style="position: absolute; left: 70px; bottom: 0;">
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
        </div>

    </div>

    <!-- Botón Scroll Arriba (Derecha) -->
    <div id="scrollContainer" class="scroll-container">
        <button id="btnArriba" class="btn-scroll-main" title="Volver arriba">
            <span class="material-symbols-outlined">expand_less</span>
        </button>
    </div>

    <!-- Scripts Generales -->
    <script src="<?php echo ($path_prefix ?? ''); ?>assets/js/main.js"></script>
    <script src="<?php echo ($path_prefix ?? ''); ?>assets/js/music_player.js"></script>
</body>
</html>
