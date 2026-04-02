/**
 * ARK Survival Hub — Music Player
 * HTML5 Audio Player · BSO ARK: Survival Evolved
 *
 * Utiliza ficheros .mp3 alojados de forma local (assets/music/)
 * para máxima seguridad y rendimiento. No depende de Youtube.
 */

let TRACKS = [];
let audioPlayer = new Audio();
let currentTrack = parseInt(localStorage.getItem('ark_music_track_index')) || 0;
let isPlaying = localStorage.getItem('ark_music_playing') === 'true';
let isMuted = localStorage.getItem('ark_music_muted') === 'true';
let rawVol = parseInt(localStorage.getItem('ark_music_vol'));
let currentVolume = isNaN(rawVol) ? 40 : rawVol;

// ── Cargar tracks desde la base de datos y construir playlist ────────────────
async function initMusicPlayer() {
    try {
        const basePath = window.location.pathname.includes('/ark-survival-hub-main/') ? '/ark-survival-hub-main' : '';
        const response = await fetch(basePath + '/actions/get_music.php');
        const result = await response.json();

        if (result.status === 'success' && result.data.length > 0) {
            TRACKS = result.data.map(track => ({
                title: track.title,
                url: basePath + '/assets/music/' + track.file
            }));

            buildPlaylist();
            setupAudioEvents();

            // Cargar track guardado o el primero
            if (currentTrack >= TRACKS.length) currentTrack = 0;

            audioPlayer.src = TRACKS[currentTrack].url;
            audioPlayer.volume = currentVolume / 100;
            audioPlayer.muted = isMuted;

            updateTrackUI(currentTrack);
            updateVolumeSliderFill(currentVolume);

            // Reanudar tiempo
            const savedTime = parseFloat(localStorage.getItem('ark_music_timestamp'));
            if (!isNaN(savedTime)) {
                audioPlayer.currentTime = savedTime;
            }

            // Intentar reanudar reproducción si estaba activo
            if (isPlaying) {
                attemptAutoPlay();
            }
        }
    } catch (error) {
        console.error('Error fetching music:', error);
    }
}

// ── Lógica de Auto-reanudación (AutoPlay Bypass) ──────────────────────────────
function attemptAutoPlay() {
    audioPlayer.play().then(() => {
        console.log("Musica reanudada correctamente.");
    }).catch(e => {
        console.log("Autoplay bloqueado. Esperando primera interacción...");
        // Si falla, esperamos a que el usuario haga click en cualquier parte
        const resumeOnInteract = () => {
            audioPlayer.play();
            document.removeEventListener('click', resumeOnInteract);
            document.removeEventListener('keydown', resumeOnInteract);
        };
        document.addEventListener('click', resumeOnInteract);
        document.addEventListener('keydown', resumeOnInteract);
    });
}

// ── Eventos del Reproductor de Audio Nativo ──────────────────────────────────
function setupAudioEvents() {
    audioPlayer.addEventListener('play', () => {
        isPlaying = true;
        localStorage.setItem('ark_music_playing', 'true');
        document.getElementById('musicPlayIcon').textContent = 'pause';
        document.getElementById('musicNoteIcon').classList.add('music-note-playing');
    });

    audioPlayer.addEventListener('pause', () => {
        isPlaying = false;
        localStorage.setItem('ark_music_playing', 'false');
        document.getElementById('musicPlayIcon').textContent = 'play_arrow';
        document.getElementById('musicNoteIcon').classList.remove('music-note-playing');
    });

    audioPlayer.addEventListener('ended', () => {
        musicNext();
    });

    // Guardar progreso cada segundo (throttle)
    audioPlayer.addEventListener('timeupdate', () => {
        if (Math.floor(audioPlayer.currentTime) % 2 === 0) { // Cada 2 segundos para no saturear
            localStorage.setItem('ark_music_timestamp', audioPlayer.currentTime);
        }
    });

    audioPlayer.addEventListener('error', (e) => {
        console.error("Error reproduciendo archivo local", e);
        musicNext();
    });
}

// ── Construir playlist en el DOM ──────────────────────────────────────────────
function buildPlaylist() {
    const list = document.getElementById('musicPlaylist');
    if (!list) return;
    list.innerHTML = '';
    TRACKS.forEach((t, i) => {
        const item = document.createElement('div');
        item.className = 'music-playlist-item' + (i === currentTrack ? ' active' : '');
        item.dataset.index = i;
        item.textContent = t.title;
        item.addEventListener('click', () => musicPlayTrack(i));
        list.appendChild(item);
    });
    const slider = document.getElementById('musicVolume');
    if (slider) slider.value = currentVolume;
}

// ── Controles ─────────────────────────────────────────────────────────────────
function musicToggle() {
    if (TRACKS.length === 0) return;
    if (audioPlayer.paused) {
        audioPlayer.play();
    } else {
        audioPlayer.pause();
    }
}

function musicPlayTrack(index) {
    if (TRACKS.length === 0) return;

    currentTrack = index;
    localStorage.setItem('ark_music_track_index', currentTrack);
    localStorage.setItem('ark_music_timestamp', 0); // Reset tiempo si es track nuevo

    updateTrackUI(index);

    audioPlayer.src = TRACKS[index].url;
    audioPlayer.currentTime = 0;
    audioPlayer.play().catch(e => console.log("Play blocked", e));
}

function musicNext() {
    if (TRACKS.length === 0) return;
    musicPlayTrack((currentTrack + 1) % TRACKS.length);
}

function musicPrev() {
    if (TRACKS.length === 0) return;
    musicPlayTrack((currentTrack - 1 + TRACKS.length) % TRACKS.length);
}

function musicToggleMute() {
    isMuted = !isMuted;
    audioPlayer.muted = isMuted;
    localStorage.setItem('ark_music_muted', isMuted);
    document.getElementById('musicMuteIcon').textContent = isMuted ? 'volume_off' : 'volume_up';
}

function musicSetVolume(val) {
    currentVolume = parseInt(val);
    localStorage.setItem('ark_music_vol', currentVolume);

    if (audioPlayer) {
        audioPlayer.volume = currentVolume / 100;
        if (isMuted && currentVolume > 0) {
            isMuted = false;
            audioPlayer.muted = false;
            document.getElementById('musicMuteIcon').textContent = 'volume_up';
        }
    }
    updateVolumeSliderFill(currentVolume);
}

// ── UI helpers ────────────────────────────────────────────────────────────────
function updateTrackUI(index) {
    const tName = document.getElementById('musicTrackName');
    if (tName) tName.textContent = TRACKS[index] ? TRACKS[index].title : 'Cargando...';

    document.querySelectorAll('.music-playlist-item').forEach((el, i) => {
        el.classList.toggle('active', i === index);
    });
}

function updateVolumeSliderFill(val) {
    const slider = document.getElementById('musicVolume');
    if (!slider) return;
    slider.style.setProperty('--vol-val', val + '%');
    slider.value = val;
}

// Eventos globales DOM
document.addEventListener('DOMContentLoaded', function () {
    initMusicPlayer();

    // UI Tooltip
    const mi = document.getElementById('musicNoteIcon');
    if (mi) mi.title = "Escuchando BSO ARK";

    // Cerrar playlist al clicar fuera
    document.addEventListener('click', function (e) {
        const panel = document.getElementById('musicPanel');
        const pl = document.getElementById('musicPlaylist');
        if (pl && pl.classList.contains('open') && !panel?.contains(e.target)) {
            pl.classList.remove('open');
        }
    });
});

