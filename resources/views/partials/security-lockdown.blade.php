<!-- AxeCorp HRMS Enterprise Security Lockdown: Complete Anti-Inspect, F12 & DevTools Neutralization -->
<script>
(function() {
    'use strict';

    // 1. Block Keyboard Developer Shortcuts Immediately (Window & Document Capture Phase)
    function blockKeyboardInspection(e) {
        // F12 key (Key code 123)
        if (e.key === 'F12' || e.keyCode === 123 || e.which === 123) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }

        const isCtrl = e.ctrlKey || e.metaKey;

        // Ctrl / Cmd + Shift + I (DevTools Inspector)
        // Ctrl / Cmd + Shift + J (Console)
        // Ctrl / Cmd + Shift + C (Element Selector)
        // Ctrl / Cmd + Shift + K (Firefox Console)
        // Ctrl / Cmd + Shift + E (Network Inspector)
        // Ctrl / Cmd + Shift + S (Debugger / Profiler)
        if (isCtrl && e.shiftKey) {
            const k = (e.key || '').toUpperCase();
            const code = e.keyCode || e.which;
            if (k === 'I' || code === 73 ||
                k === 'J' || code === 74 ||
                k === 'C' || code === 67 ||
                k === 'K' || code === 75 ||
                k === 'E' || code === 69 ||
                k === 'S' || code === 83) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            }
        }

        // Ctrl / Cmd + U (View Page Source)
        if (isCtrl && ((e.key || '').toUpperCase() === 'U' || e.keyCode === 85 || e.which === 85)) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }

        // Ctrl / Cmd + S (Save Web Page)
        if (isCtrl && ((e.key || '').toUpperCase() === 'S' || e.keyCode === 83 || e.which === 83)) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }
    }

    // Register on window and document capture phases
    window.addEventListener('keydown', blockKeyboardInspection, true);
    document.addEventListener('keydown', blockKeyboardInspection, true);

    // 2. Strict Application-Wide Context Menu (Right-Click) Neutralization
    const suppressContextMenu = function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    };

    window.addEventListener('contextmenu', suppressContextMenu, { capture: true, passive: false });
    document.addEventListener('contextmenu', suppressContextMenu, { capture: true, passive: false });

    // Also suppress auxiliary mouse clicks (e.g. right button mousedown/mouseup)
    function checkMouseButton(e) {
        if (e.button === 2) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }
    window.addEventListener('mousedown', checkMouseButton, true);
    document.addEventListener('mousedown', checkMouseButton, true);

    // 3. Image Drag & Drop Protection
    document.addEventListener('dragstart', function(e) {
        if (e.target && e.target.nodeName === 'IMG') {
            e.preventDefault();
            return false;
        }
    }, false);

    // 4. Active DevTools Trapping via Timing / Debugger Breakpoint
    // When DevTools is closed, debugger statement executes in < 0.05ms (no pause).
    // When DevTools is open, debugger statement pauses execution, freezing DevTools inspection.
    (function initDevToolsTrap() {
        function triggerTrap() {
            try {
                const startTime = performance.now();
                debugger;
                const endTime = performance.now();
                if (endTime - startTime > 100) {
                    // DevTools open or script was stepped into
                    if (document.body) {
                        document.body.style.display = 'none';
                    }
                    window.location.reload();
                }
            } catch (err) {}
        }

        // Recurring trap to freeze DevTools if opened anytime during the session
        setInterval(triggerTrap, 600);
    })();

    // 5. Console Shield: Disable Logging & Obfuscate
    try {
        if (window.console) {
            const noop = function() {};
            window.console.log = noop;
            window.console.debug = noop;
            window.console.info = noop;
            window.console.dir = noop;
            window.console.table = noop;
        }
    } catch (err) {}
})();
</script>
