/**
 * ARK Survival Hub — Music Player
 * HTML5 Audio Player · BSO ARK: Survival Evolved
 *
 * Utiliza ficheros .mp3 alojados de forma local (assets/music/)
 * para máxima seguridad y rendimiento. No depende de Youtube.
 */

let TRACKS = [];
let audioPlayer   = new Audio();
let currentTrack  = 0;
let isPlaying     = false;
let isMuted       = false;
let rawVol        = parseInt(localStorage.getItem('ark_music_vol'));
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
            
            // Precargar primera canción (sin reproducir)
            audioPlayer.src = TRACKS[0].url;
            audioPlayer.volume = currentVolume / 100;
            updateVolumeSliderFill(currentVolume);
        } else {
            console.error('Error cargando la música:', result.message);
        }
    } catch (error) {
        console.error('Error fetching music:', error);
    }
}

// ── Eventos del Reproductor de Audio Nativo ──────────────────────────────────
function setupAudioEvents() {
    audioPlayer.addEventListener('play', () => {
        isPlaying = true;
        document.getElementById('musicPlayIcon').textContent = 'pause';
        document.getElementById('musicNoteIcon').classList.add('music-note-playing');
    });

    audioPlayer.addEventListener('pause', () => {
        isPlaying = false;
        document.getElementById('musicPlayIcon').textContent = 'play_arrow';
        document.getElementById('musicNoteIcon').classList.remove('music-note-playing');
    });

    audioPlayer.addEventListener('ended', () => {
        musicNext();
    });

    audioPlayer.addEventListener('error', (e) => {
        console.error("Error reproduciendo archivo local, saltando...", e);
        // Descomentar para desarrollo: alert("Falta el archivo: " + TRACKS[currentTrack].url);
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
        item.className = 'music-playlist-item' + (i === 0 ? ' active' : '');
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
    
    if (isPlaying) {
        audioPlayer.pause();
    } else {
        const playPromise = audioPlayer.play();
        if (playPromise !== undefined) {
            playPromise.catch(e => console.error("Autoplay prevent flag triggered.", e));
        }
    }
}

function musicPlayTrack(index) {
    if (TRACKS.length === 0) return;
    
    currentTrack = index;
    updateTrackUI(index);
    
    audioPlayer.src = TRACKS[index].url;
    audioPlayer.volume = currentVolume / 100;
    audioPlayer.play().catch(e => console.log("Play interrupted / missing file", e));
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
    if(tName) tName.textContent = TRACKS[index].title;
    
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
    
    const trackNameContainer = document.querySelector('.music-track-info');
    if (trackNameContainer) {
        trackNameContainer.style.cursor = 'pointer';
        trackNameContainer.title = 'Ver lista de canciones';
    }
    updateVolumeSliderFill(currentVolume);

    // Cerrar playlist al clicar fuera
    document.addEventListener('click', function (e) {
        const panel = document.getElementById('musicPanel');
        const pl    = document.getElementById('musicPlaylist');
        if (pl && pl.classList.contains('open') && !panel?.contains(e.target)) {
            pl.classList.remove('open');
        }
    });
});
