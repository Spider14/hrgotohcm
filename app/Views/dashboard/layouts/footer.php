</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Session timeout warning — auto-logout after 25 min (300s before 30 min hard limit)
(function() {
    const SESSION_TIMEOUT = 1800; // 30 min (matches PHP)
    const WARNING_BEFORE = 300;    // warn 5 min before
    let warningTimer, logoutTimer;

    function resetTimers() {
        clearTimeout(warningTimer);
        clearTimeout(logoutTimer);
        const idleMs = (SESSION_TIMEOUT - WARNING_BEFORE) * 1000;
        warningTimer = setTimeout(showWarning, idleMs);
        logoutTimer = setTimeout(forceLogout, SESSION_TIMEOUT * 1000);
    }

    function showWarning() {
        const modal = document.createElement('div');
        modal.id = 'session-timeout-modal';
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:99999;display:flex;align-items:center;justify-content:center;';
        modal.innerHTML = '<div style="background:#fff;border-radius:12px;padding:2rem;max-width:400px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);">'
            + '<i class="fas fa-clock fa-3x text-warning mb-3"></i>'
            + '<h5 class="fw-bold">Session Expiring Soon</h5>'
            + '<p class="text-muted small mb-3">Your session will expire in 5 minutes due to inactivity.</p>'
            + '<button id="session-stay-btn" class="btn btn-primary btn-sm px-4">Stay Logged In</button>'
            + '</div>';
        document.body.appendChild(modal);
    }

    function forceLogout() {
        window.location.href = '<?php echo \App\Helpers\Security::escape($_ENV['APP_URL']); ?>/logout';
    }

    document.addEventListener('click', resetTimers);
    document.addEventListener('keydown', resetTimers);
    document.addEventListener('scroll', resetTimers);
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'session-stay-btn') {
            const modal = document.getElementById('session-timeout-modal');
            if (modal) modal.remove();
            resetTimers();
        }
    });
    resetTimers();
})();
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById("sidebar");
    const sidebarBtn = document.getElementById("sidebarCollapseBtn");
    
    if (!sidebar) return;

    // 1. Initialize Layout State Configurations using standard industry classes
    const isMobile = window.innerWidth <= 768;
    const savedSidebarPreference = localStorage.getItem("sidebar_state");

    if (!isMobile) {
        // Desktop Layout: Stay wide open unless explicitly saved as collapsed
        if (savedSidebarPreference === "collapsed") {
            sidebar.classList.add("collapsed");
        } else {
            sidebar.classList.remove("collapsed");
        }
    } else {
        // Mobile Layout: Ensure it is hidden by default to avoid screen crowding
        sidebar.classList.remove("mobile-open");
        sidebar.classList.add("collapsed");
    }

    // 2. Persist sidebar scroll position across page navigations
    const SIDEBAR_SCROLL_KEY = 'hrgoto_sidebar_scroll';
    const savedScroll = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
    if (savedScroll) {
        sidebar.scrollTop = parseInt(savedScroll, 10);
    }
    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(sidebar.scrollTop));
    });

    // 3. Isolated Global Sidebar Toggle Controller
    if (sidebarBtn) {
        sidebarBtn.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation(); // Stop event bubbling up to parent containers

            const currentMobileState = window.innerWidth <= 768;

            if (!currentMobileState) {
                // Desktop Toggle Engine
                sidebar.classList.toggle("collapsed");
                
                if (sidebar.classList.contains("collapsed")) {
                    localStorage.setItem("sidebar_state", "collapsed");
                } else {
                    localStorage.setItem("sidebar_state", "expanded");
                }
            } else {
                // Mobile Toggle Engine
                sidebar.classList.toggle("mobile-open");
            }
        });
    }

    // 4. Clean Dynamic Screen Scaling Rule
    window.addEventListener("resize", function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove("mobile-open");
            if (localStorage.getItem("sidebar_state") === "collapsed") {
                sidebar.classList.add("collapsed");
            } else {
                sidebar.classList.remove("collapsed");
            }
        } else {
            sidebar.classList.add("collapsed");
        }
    });
});
</script>
</body>
</html>