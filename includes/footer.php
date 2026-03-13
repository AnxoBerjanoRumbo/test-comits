    </main>
    <footer style="margin-top: 50px; padding: 30px 5%; background: var(--bg-header); border-top: 1px solid var(--border-color); text-align: center;">
        <div style="margin-bottom: 15px;">
            <img src="<?php echo ($path_prefix ?? ''); ?>assets/img/logo.png" alt="ARK Hub Logo" style="width: 50px; opacity: 0.7;">
        </div>
        <p style="color: #888; font-size: 0.9rem; margin: 0;">
            &copy; <?php echo date('Y'); ?> <strong>ARK Survival Hub</strong> - Wiki de Supervivencia.
        </p>
        <p style="color: #555; font-size: 0.8rem; margin-top: 5px;">
            Desarrollado para la comunidad de supervivientes. Todos los derechos reservados.
        </p>
    </footer>

    <button id="btnArriba" class="btn-arriba" title="Volver arriba">
        ⬆
    </button>

    <!-- Scripts Generales -->
    <script src="<?php echo ($path_prefix ?? ''); ?>assets/js/main.js"></script>
</body>
</html>
