    <!-- ══════════════════════════════════════════════════
         File: includes/footer.php
         Reusable footer — included on every authenticated page
    ══════════════════════════════════════════════════ -->
    <footer class="ft-footer mt-auto py-4">
        <div class="container-fluid px-4">

            <div class="row align-items-center gy-3">

                <!-- Left: App name + tagline -->
                <div class="col-12 col-md-4 text-center text-md-start">
                    <div class="fw-bold mb-1" style="color:var(--ft-brand); font-size:0.95rem;">
                        <i class="bi bi-wallet2 me-1"></i><?= e(APP_NAME) ?>
                    </div>
                    <small style="color:var(--ft-text-muted); font-style:italic;">
                        Track smarter, save better.
                    </small>
                </div>

                <!-- Center: Developer badge — contact info shown on click -->
                <div class="col-12 col-md-4 text-center" style="position:relative;">

                    <!-- Toggle button -->
                    <button onclick="toggleDevCard()" style="
                        display: inline-flex;
                        align-items: center;
                        gap: 0.45rem;
                        background: linear-gradient(135deg, #0057ff 0%, #00b4d8 100%);
                        color: #fff;
                        font-family: 'Segoe UI', system-ui, sans-serif;
                        font-size: 0.72rem;
                        font-weight: 700;
                        letter-spacing: 0.06em;
                        padding: 0.42rem 1rem 0.42rem 0.65rem;
                        border-radius: 50px;
                        border: none;
                        box-shadow: 0 3px 14px rgba(0,87,255,0.3);
                        cursor: pointer;
                        transition: box-shadow 0.25s ease, transform 0.25s ease;
                    "
                    onmouseover="this.style.boxShadow='0 6px 22px rgba(0,87,255,0.5)';this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.boxShadow='0 3px 14px rgba(0,87,255,0.3)';this.style.transform='translateY(0)'">
                        <span style="
                            width: 22px; height: 22px;
                            background: rgba(255,255,255,0.2);
                            border-radius: 50%;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 0.8rem;
                            flex-shrink: 0;
                        ">👨‍💻</span>
                        Developer &nbsp;<strong>Amariya T</strong>
                        <i class="bi bi-chevron-up" id="devChevron" style="font-size:0.65rem; margin-left:0.2rem; transition:transform 0.25s;"></i>
                    </button>

                    <!-- Contact card — hidden by default, shown on click -->
                    <div id="devContactCard" style="
                        display: none;
                        position: absolute;
                        bottom: calc(100% + 10px);
                        left: 50%;
                        transform: translateX(-50%);
                        background: #ffffff;
                        border: 1.5px solid #dce6f5;
                        border-radius: 14px;
                        box-shadow: 0 8px 28px rgba(21,101,192,0.18);
                        padding: 1rem 1.25rem;
                        min-width: 230px;
                        text-align: left;
                        z-index: 999;
                        animation: devCardPop 0.2s cubic-bezier(0.34,1.56,0.64,1) both;
                    ">
                        <p style="font-size:0.78rem; font-weight:700; color:#0d1b4b; margin:0 0 0.6rem;">
                            👨‍💻 Amariya T
                        </p>
                        <a href="mailto:amariyatesfaw@gmail.com" style="
                            display:flex; align-items:center; gap:0.4rem;
                            font-size:0.75rem; color:#1565c0; text-decoration:none;
                            margin-bottom:0.4rem;
                        ">
                            <i class="bi bi-envelope-fill"></i> amariyatesfaw@gmail.com
                        </a>
                        <a href="tel:+251927618147" style="
                            display:flex; align-items:center; gap:0.4rem;
                            font-size:0.75rem; color:#1565c0; text-decoration:none;
                        ">
                            <i class="bi bi-telephone-fill"></i> +251 927 618 147
                        </a>
                    </div>

                </div>

                <!-- Right: Copyright -->
                <div class="col-12 col-md-4 text-center text-md-end">
                    <small style="color:var(--ft-text-muted);">
                        &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &mdash; v<?= APP_VERSION ?>
                    </small>
                </div>

            </div><!-- /.row -->

            <!-- Bottom rule -->
            <hr style="border-color: #dce6f5; margin: 1rem 0 0;">
            <div class="text-center">
                <small style="color:var(--ft-text-muted); font-size:0.7rem;">
                    Built with <i class="bi bi-heart-fill" style="color:var(--ft-danger); font-size:0.65rem;"></i>
                    using PHP &bull; MySQL &bull; Bootstrap 5 &bull; Chart.js
                </small>
            </div>

        </div>
    </footer>

    <style>
        @keyframes devCardPop {
            from { opacity: 0; transform: translateX(-50%) translateY(8px) scale(0.95); }
            to   { opacity: 1; transform: translateX(-50%) translateY(0)   scale(1);    }
        }
    </style>

    <script>
        function toggleDevCard() {
            const card    = document.getElementById('devContactCard');
            const chevron = document.getElementById('devChevron');
            const visible = card.style.display === 'block';
            card.style.display = visible ? 'none' : 'block';
            chevron.style.transform = visible ? 'rotate(0deg)' : 'rotate(180deg)';
            if (!visible) {
                // Close when clicking anywhere outside
                setTimeout(() => {
                    document.addEventListener('click', function handler(e) {
                        if (!document.getElementById('devContactCard').contains(e.target)
                            && !e.target.closest('button[onclick="toggleDevCard()"]')) {
                            document.getElementById('devContactCard').style.display = 'none';
                            document.getElementById('devChevron').style.transform = 'rotate(0deg)';
                            document.removeEventListener('click', handler);
                        }
                    });
                }, 10);
            }
        }
    </script>

    <!-- Bootstrap 5 JS Bundle — CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFXFMrWCU3FA0e6bMknOZZSoiw"
            crossorigin="anonymous"></script>

    <!-- Chart.js — CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <!-- Local vendor fallbacks -->
    <script>
        if (typeof bootstrap === 'undefined') {
            document.write('<script src="<?= APP_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"><\/script>');
        }
        if (typeof Chart === 'undefined') {
            document.write('<script src="<?= APP_URL ?>/assets/vendor/chart.js/chart.umd.min.js"><\/script>');
        }
    </script>

    <!-- Application JS -->
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    <?php if (isset($loadCharts) && $loadCharts): ?>
    <script src="<?= APP_URL ?>/assets/js/charts.js"></script>
    <?php endif; ?>

    <?php if (isset($extraScripts)) echo $extraScripts; ?>

</body>
</html>
