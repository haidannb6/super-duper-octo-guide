<?php
session_start();
//Whitelist IP, change it to your real IP/白名单ip，改为你的真实ip
$allowed_ip = '1.1.1.1'; 

$client_ip = $_SERVER['REMOTE_ADDR']; 
$is_auth = ($client_ip === $allowed_ip);

$db_dir = __DIR__ . '/.db_data';
if (!is_dir($db_dir)) {
    mkdir($db_dir, 0755, true);
    file_put_contents($db_dir . '/.htaccess', 'Deny from all'); 
}
$db_file = $db_dir . '/boot_data.db';

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    if (!$is_auth) {
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }

    $db = new SQLite3($db_file);
    $db->exec("CREATE TABLE IF NOT EXISTS boot_stats (id INTEGER PRIMARY KEY, total_count INTEGER)");

    $row = $db->querySingle("SELECT total_count FROM boot_stats WHERE id = 1");
    $count = ($row === null || $row === false) ? 0 : $row;

    if ($_GET['action'] === 'get') {
        echo json_encode(['count' => $count]);
    } elseif ($_GET['action'] === 'add') {
        if (isset($_SESSION['last_boot']) && (time() - $_SESSION['last_boot'] < 10)) {
            echo json_encode(['count' => $count, 'status' => 'rate_limited']);
        } else {
            $_SESSION['last_boot'] = time();
            if ($row === null || $row === false) {
                $db->exec("INSERT INTO boot_stats (id, total_count) VALUES (1, 1)");
                $count = 1;
            } else {
                $count++;
                $stmt = $db->prepare("UPDATE boot_stats SET total_count = :count WHERE id = 1");
                $stmt->bindValue(':count', $count, SQLITE3_INTEGER);
                $stmt->execute();
            }
            echo json_encode(['count' => $count]);
        }
    }
    $db->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M18.36 6.64a9 9 0 1 1-12.73 0'%3E%3C/path%3E%3Cline x1='12' y1='2' x2='12' y2='12'%3E%3C/line%3E%3C/svg%3E">
    <?php if ($is_auth): ?>
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <?php endif; ?>
    <style>
        :root { --bg-color: #000000; --surface-color: #000000; --text-main: #ffffff; --text-muted: rgba(255, 255, 255, 0.4); }
        * { user-select: none; -webkit-user-select: none; -webkit-user-drag: none; }
        body { font-family: -apple-system, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: var(--bg-color); color: var(--text-main); overflow: hidden; }
        .terminal-card { background: var(--surface-color); padding: 50px 40px; border-radius: 24px; text-align: center; width: 300px; box-sizing: border-box; z-index: 10; }
        .header { margin-bottom: 40px; letter-spacing: 2px; }
        h2, .subtitle, .status-text, .boot-count { white-space: nowrap; }
        h2 { margin: 0 0 6px 0; font-size: 16px; font-weight: 500; letter-spacing: 4px; height: 22px; }
        .subtitle { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 4px; height: 14px; }
        .status-wrapper { display: flex; flex-direction: column; align-items: center; gap: 12px; margin-bottom: 40px; min-height: 60px; }
        .magnetic-dot-wrapper { display: inline-flex; justify-content: center; align-items: center; width: 6px; height: 6px; will-change: transform; pointer-events: none; }
        .status-indicator { width: 6px; height: 6px; border-radius: 50%; background-color: var(--text-muted); transition: background-color 0.8s, box-shadow 0.8s; }
        .status-indicator.online { background-color: #fff; box-shadow: 0 0 10px #fff, 0 0 20px rgba(255,255,255,0.5); animation: pulse 3s infinite alternate; }
        @keyframes pulse { 0% { opacity: 0.6; transform: scale(0.9); } 100% { opacity: 1; transform: scale(1.1); box-shadow: 0 0 15px #fff, 0 0 30px rgba(255,255,255,0.4); } }
        .status-text { font-size: 12px; color: var(--text-muted); letter-spacing: 2px; height: 16px; }
        .boot-count { font-size: 10px; color: rgba(255,255,255,0.25); letter-spacing: 1px; margin-top: -2px; font-variant-numeric: tabular-nums; opacity: 0; transition: opacity 0.5s; height: 14px; }
        .magnetic-char { display: inline-block; will-change: transform; pointer-events: none; white-space: pre; }
        .btn-power { background: transparent; color: var(--text-main); border: 1px solid rgba(255,255,255,0.1); width: 76px; height: 76px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; margin: 0 auto; position: relative; transition: background 0.3s, border-color 0.3s, opacity 0.4s; }
        .btn-power svg { width: 28px; height: 28px; transition: all 0.3s; }
        .btn-power:disabled { opacity: 0.3; cursor: not-allowed; border-color: rgba(255,255,255,0.05); }
        .footer { position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%); z-index: 20; padding: 10px; }
        .footer a { font-size: 11px; color: var(--text-muted); text-decoration: none; letter-spacing: 1px; transition: color 0.3s; pointer-events: auto; }
        .footer a:hover { color: var(--text-main); }
        @media (hover: none) and (pointer: coarse) { .btn-power { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); opacity: 1; visibility: visible; } .btn-power:active:not(:disabled) { transform: scale(0.95); } }
        @media (min-width: 768px) and (hover: hover) and (pointer: fine) { .btn-power { position: fixed; z-index: 999; margin: 0; opacity: 0; visibility: hidden; } .btn-power.visible { opacity: 1; visibility: visible; } .btn-power.visible:disabled { opacity: 0.2; } .btn-power:hover:not(:disabled) { background: var(--text-main); color: var(--bg-color); box-shadow: 0 0 25px rgba(255,255,255,0.15); } }
    </style>
</head>
<body>
    <div class="terminal-card">
        <div class="header">
            <h2 id="textH2"></h2>
            <div id="textSub" class="subtitle"></div>
        </div>
        <div class="status-wrapper">
            <div id="statusDotWrapper" class="magnetic-dot-wrapper" style="display: <?php echo $is_auth ? 'inline-flex' : 'none'; ?>;">
                <div id="statusDot" class="status-indicator"></div>
            </div>
            <span id="statusText" class="status-text"></span>
            <div id="bootCount" class="boot-count"></div>
        </div>
        <button id="wakeBtn" class="btn-power" <?php echo $is_auth ? '' : 'disabled'; ?>></button>
    </div>
    <div id="footerArea" class="footer">
        <a href="https://beian.miit.gov.cn/" target="_blank">鲁ICP备2026006693号</a>
    </div>

    <script>
        const isDesktop = window.matchMedia("(min-width: 768px) and (hover: hover) and (pointer: fine)").matches;
        let repulseTargets = [];

        function updateMagneticText(element, newText) {
            if(!element) return;
            element.innerHTML = ''; 
            newText.split('').forEach(char => {
                const span = document.createElement('span');
                span.innerText = char;
                span.className = 'magnetic-char';
                element.appendChild(span);
            });
            setTimeout(calculateBasePositions, 50);
        }

        function calculateBasePositions() {
            if (!isDesktop) return; 
            const chars = document.querySelectorAll('.magnetic-char, #statusDotWrapper');
            repulseTargets = Array.from(chars).map(el => {
                const oldTransform = el.style.transform;
                el.style.transform = 'translate(0px, 0px)'; 
                const rect = el.getBoundingClientRect();
                el.style.transform = oldTransform;
                if (typeof el._currX === 'undefined') { el._currX = 0; el._currY = 0; }
                return { el: el, baseX: rect.left + rect.width / 2, baseY: rect.top + rect.height / 2 };
            });
        }
        window.addEventListener('resize', calculateBasePositions);

        const textH2 = document.getElementById('textH2');
        const textSub = document.getElementById('textSub');
        const statusText = document.getElementById('statusText');
        const bootCount = document.getElementById('bootCount');
        const wakeBtn = document.getElementById('wakeBtn');
        const footerArea = document.getElementById('footerArea');

        <?php if (!$is_auth): ?>
        updateMagneticText(textH2, 'No permission');
        wakeBtn.innerHTML = '<span style="font-size: 11px; white-space: nowrap; letter-spacing: 1px;">No permission</span>';
        wakeBtn.style.width = '110px';
        wakeBtn.style.height = '110px';
        wakeBtn.style.cursor = 'default';
        if (isDesktop) initPhysics();
        <?php else: ?>
        updateMagneticText(textH2, '终端控制');
        updateMagneticText(textSub, 'ESP32·S3');
        updateMagneticText(statusText, '连接中...');
        
        const powerPath = `<path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line>`;
        const checkPath = `<polyline points="20 6 9 17 4 12"></polyline>`;
        wakeBtn.innerHTML = `<svg id="iconSvg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">${powerPath}</svg>`;
        wakeBtn.disabled = true;

        const client = mqtt.connect('wss://nb6.icu/mqtt');

        fetch('?action=get')
            .then(r => r.json())
            .then(data => {
                if(data.count !== undefined) {
                    updateMagneticText(bootCount, `累计开机: ${data.count} 次`);
                    bootCount.style.opacity = '1';
                }
            });

        client.on('connect', function () {
            updateMagneticText(statusText, '等待设备响应...');
            client.subscribe('esp32/status');
        });

        client.on('message', function (topic, message) {
            if (topic === 'esp32/status') {
                const statusDot = document.getElementById('statusDot');
                if (message.toString() === 'online') {
                    statusDot.classList.add('online');
                    updateMagneticText(statusText, '设备已就绪');
                    statusText.style.color = 'rgba(255, 255, 255, 0.8)';
                    wakeBtn.disabled = false;
                } else {
                    statusDot.classList.remove('online');
                    statusDot.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                    updateMagneticText(statusText, '设备已离线');
                    statusText.style.color = 'var(--text-muted)';
                    wakeBtn.disabled = true;
                }
            }
        });

        wakeBtn.onclick = function() {
            client.publish('esp32/pc_control', 'WAKE');
            document.getElementById('iconSvg').innerHTML = checkPath;
            updateMagneticText(statusText, '指令已发送');
            statusText.style.color = '#fff';
            wakeBtn.style.background = 'rgba(255, 255, 255, 0.08)';
            wakeBtn.disabled = true;

            fetch('?action=add')
                .then(r => r.json())
                .then(data => {
                    if(data.count !== undefined) updateMagneticText(bootCount, `累计开机: ${data.count} 次`);
                });

            setTimeout(() => {
                document.getElementById('iconSvg').innerHTML = powerPath;
                updateMagneticText(statusText, '设备已就绪');
                statusText.style.color = 'rgba(255, 255, 255, 0.8)';
                wakeBtn.style.background = '';
                wakeBtn.disabled = false;
            }, 3000);
        };
        if (isDesktop) initPhysics();
        <?php endif; ?>

        function initPhysics() {
            let mouseX = window.innerWidth / 2, mouseY = window.innerHeight / 2;
            let btnX = mouseX, btnY = mouseY;
            let hoverScale = 1.0;
            let isVisible = false;
            let displayScale = 0; 

            function isNearFooter(x, y) {
                const rect = footerArea.getBoundingClientRect();
                return (x > rect.left - 60 && x < rect.right + 60 && y > rect.top - 60);
            }
            
            document.addEventListener('mouseleave', () => { wakeBtn.classList.remove('visible'); isVisible = false; });
            document.addEventListener('mouseenter', (e) => {
                mouseX = e.clientX; mouseY = e.clientY; btnX = mouseX; btnY = mouseY;
                if (!isNearFooter(mouseX, mouseY)) { wakeBtn.classList.add('visible'); isVisible = true; }
            });
            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX; mouseY = e.clientY;
                if (isNearFooter(mouseX, mouseY)) {
                    if (isVisible) { wakeBtn.classList.remove('visible'); isVisible = false; }
                } else {
                    if (!isVisible) { wakeBtn.classList.add('visible'); isVisible = true; }
                }
            });

            wakeBtn.addEventListener('mouseenter', () => { if(!wakeBtn.disabled) hoverScale = 1.1; });
            wakeBtn.addEventListener('mouseleave', () => hoverScale = 1.0);
            wakeBtn.addEventListener('mousedown', () => { if(!wakeBtn.disabled) hoverScale = 0.95; });
            wakeBtn.addEventListener('mouseup', () => { if(!wakeBtn.disabled) hoverScale = 1.1; });

            function animateButton() {
                btnX += (mouseX - btnX) * 0.12;
                btnY += (mouseY - btnY) * 0.12;

                displayScale += ((isVisible ? 1 : 0) - displayScale) * 0.15;
                let finalScale = 0;

                if (displayScale > 0.001 || isVisible) {
                    const cx = window.innerWidth / 2, cy = window.innerHeight / 2;
                    let dScale = 1.5 - (Math.hypot(btnX - cx, btnY - cy) / Math.hypot(cx, cy)) * 0.9;
                    finalScale = Math.max(0.6, Math.min(1.5, dScale)) * hoverScale * displayScale;
                    wakeBtn.style.left = `${btnX}px`;
                    wakeBtn.style.top = `${btnY}px`;
                    wakeBtn.style.transform = `translate(-50%, -50%) scale(${finalScale})`;
                }

                const repulseRadius = (wakeBtn.offsetWidth / 2) * finalScale + 90; 
                repulseTargets.forEach(target => {
                    const dx = target.baseX - btnX, dy = target.baseY - btnY;
                    const dist = Math.hypot(dx, dy);
                    let pushX = 0, pushY = 0;

                    if (isVisible && displayScale > 0.1 && dist < repulseRadius) {
                        const safeDist = dist === 0 ? 0.001 : dist; 
                        const force = Math.pow((repulseRadius - safeDist) / repulseRadius, 1.6);
                        pushX = (dx / safeDist) * force * 120;
                        pushY = (dy / safeDist) * force * 120;
                    }
                    target.el._currX += (pushX - target.el._currX) * 0.15;
                    target.el._currY += (pushY - target.el._currY) * 0.15;
                    target.el.style.transform = `translate(${target.el._currX}px, ${target.el._currY}px)`;
                });
                requestAnimationFrame(animateButton);
            }
            animateButton();
        }
    </script>
</body>
</html>
