<?php
/* @var $config array */
require 'config.php';
$pokemondataurl = 'https://raw.githubusercontent.com/WatWowMap/Masterfile-Generator/master/master-latest.json';

$dsn = "mysql:host=".$config['server'].";port=".$config['port'].";dbname=".$config['database_name'].";charset=".$config['charset'];
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
$events = [
    'yest' => [
       'name' =>'Yesterday',
       'datefrom' => date('Y-m-d',strtotime('-1day')),
       'dateto' => date('Y-m-d',strtotime('-1day')),
        'alwaysshow' => true,
    ],
    'week' => [
        'name' =>'Week',
        'datefrom' => date('Y-m-d',strtotime('monday this week')),
        'dateto' => date('Y-m-d',strtotime('sunday this week')),
        'alwaysshow' => true,
    ],
    'month' => [
        'name' =>'Month',
        'datefrom' => date('Y-m-1'),
        'dateto' => date('Y-m-t'),
        'alwaysshow' => true,
    ],
];
if (!empty($config['events'])) {
    $events += $config['events'];
}
if (!is_file(basename($pokemondataurl)) or filemtime(basename($pokemondataurl)) <  strtotime('-24 hour')) {
    $pokemondata = file_get_contents($pokemondataurl);
    file_put_contents(basename($pokemondataurl),$pokemondata);
} else {
    $pokemondata = file_get_contents(basename($pokemondataurl));
}
$data = json_decode($pokemondata,true);
$today = date('Y-m-d');
$prepare = [];
$query = "SELECT i.pokemon_id,
s.total AS pokemoncount,
s.`count` AS shiny,
(SELECT SUM(h.`count`) FROM pokemon_hundo_stats h WHERE i.pokemon_id=h.pokemon_id AND i.date=h.date and fence='world') AS hundo,
(SELECT SUM(n.`count`) FROM pokemon_nundo_stats n WHERE i.pokemon_id=n.pokemon_id AND i.date=n.date and fence='world') AS nundo
FROM pokemon_stats i
LEFT JOIN pokemon_shiny_stats s ON i.pokemon_id=s.pokemon_id AND i.date=s.date
WHERE 1
";

$activeEvent = null;
if (!empty($_REQUEST['e']) and in_array($_REQUEST['e'],array_keys($events))) {
    $activeEvent = $_REQUEST['e'];
   if (!empty($events[$_REQUEST['e']]['datefrom'])) {
       $query .= "AND i.date >= :from ";
       $prepare['from'] = $events[$_REQUEST['e']]['datefrom'];
   }
    if (!empty($events[$_REQUEST['e']]['dateto'])) {
        $query .= "AND i.date <= :to ";
        $prepare['to'] = $events[$_REQUEST['e']]['dateto'];
    }
    if (!empty($events[$_REQUEST['e']]['ids'])) {
        $query .= "AND i.pokemon_id IN (". implode(',',$events[$_REQUEST['e']]['ids']) .") ";
    }
} else {
    $query .= "AND i.date = :today ";
    $prepare['today'] = $today;
}
$query .= "
GROUP BY i.pokemon_id
ORDER BY pokemoncount DESC
";
$q = $pdo->prepare($query);
$q->execute($prepare);
$all = $q->fetchAll(PDO::FETCH_ASSOC);
$total_mons = array_sum(array_column($all, 'pokemoncount'));
$total_shiny = array_sum(array_column($all, 'shiny'));
$total_hundo = array_sum(array_column($all, 'hundo'));
$total_nundo = array_sum(array_column($all, 'nundo'));

// Generation ranges
$generations = [
    1 => ['name' => 'Kanto',  'min' => 1,   'max' => 151],
    2 => ['name' => 'Johto',  'min' => 152, 'max' => 251],
    3 => ['name' => 'Hoenn',  'min' => 252, 'max' => 386],
    4 => ['name' => 'Sinnoh', 'min' => 387, 'max' => 493],
    5 => ['name' => 'Unova',  'min' => 494, 'max' => 649],
    6 => ['name' => 'Kalos',  'min' => 650, 'max' => 721],
    7 => ['name' => 'Alola',  'min' => 722, 'max' => 809],
    8 => ['name' => 'Galar',  'min' => 810, 'max' => 905],
    9 => ['name' => 'Paldea', 'min' => 906, 'max' => 1025],
];

function getGenName($pokemonId, $generations) {
    foreach ($generations as $gen) {
        if ($pokemonId >= $gen['min'] && $pokemonId <= $gen['max']) {
            return $gen['name'];
        }
    }
    return '';
}

function getPokemonTypes($pokemonId, $data) {
    if (!empty($data['pokemon'][$pokemonId]['types'])) {
        $types = $data['pokemon'][$pokemonId]['types'];
        return implode(',', array_map(function($t) {
            $typeNames = [
                1 => 'normal', 2 => 'fighting', 3 => 'flying', 4 => 'poison',
                5 => 'ground', 6 => 'rock', 7 => 'bug', 8 => 'ghost',
                9 => 'steel', 10 => 'fire', 11 => 'water', 12 => 'grass',
                13 => 'electric', 14 => 'psychic', 15 => 'ice', 16 => 'dragon',
                17 => 'dark', 18 => 'fairy'
            ];
            if (is_array($t)) {
                $typeId = $t['typeId'] ?? ($t['type_id'] ?? 0);
            } else {
                $typeId = (int)$t;
            }
            return $typeNames[$typeId] ?? '';
        }, $types));
    }
    return '';
}

function getPokemonRarity($pokemonId, $data) {
    $pokemon = $data['pokemon'][$pokemonId] ?? null;
    if (!$pokemon) return '';
    if (!empty($pokemon['legendary'])) return 'legendary';
    if (!empty($pokemon['mythical'])) return 'mythical';
    if (!empty($pokemon['ultraBeast'])) return 'ultra';
    return '';
}

// Determine summary label
if ($activeEvent && !empty($events[$activeEvent]['name'])) {
    $summaryLabel = $events[$activeEvent]['name'];
    $dateRange = '(' . date(($config['date_format']??'d-m-Y'),strtotime($events[$activeEvent]['datefrom'])) . ' &ndash; ' . date(($config['date_format']??'d-m-Y'),strtotime($events[$activeEvent]['dateto'])) . ')';
} else {
    $summaryLabel = "Today's stats";
    $dateRange = '';
}

$isDark = !empty($_COOKIE["theme"]);

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <meta http-equiv="refresh" content="600">
    <link rel="stylesheet" href="./styles.css?v=11" />
    <title>Live shiny stats for Pokémon Go</title>
</head>
<body class="dark-changer ' . ($isDark ? "dark" : "") . '">
    <div id="topbar">
        <label class="switch" title="Toggle dark mode">
            <input type="checkbox" id="toggleTheme" ' . ($isDark ? "checked" : "") . '>
            <span class="slider round"></span>
        </label>
        <span class="topbar-time">' . date('H:i:s') . '</span>
    </div>

    <div class="headandstats">
        <div id="header">
            Live Stats for Pokémon Go
        </div>

        <div id="period-control">';

$html .= '<a href="?" class="period-pill ' . (empty($activeEvent) ? 'active' : '') . '">Today</a>';
foreach ($events as $key => $event) {
    if (empty($event['datefrom']) || empty($event['dateto'])) continue;
    $now = new DateTime();
    $start = new DateTime($event['datefrom']);
    $end = new DateTime($event['dateto']);
    $end->modify('+'. ($config['keep_events_days'] ?? 1).' day');
    if ($now >= $start && $now < $end || !empty($event['alwaysshow'])) {
        $isActive = ($activeEvent === $key) ? 'active' : '';
        $html .= '<a href="?e='. $key .'" class="period-pill '. $isActive .'">'. $event['name'] .'</a>';
    }
}

$html .= '
        </div>

        <div id="summary-bar">
            <span class="summary-label">' . htmlspecialchars($summaryLabel) . '</span>
            ' . (!empty($dateRange) ? '<span class="summary-date-range">' . $dateRange . '</span>' : '') . '
            <div class="summary-items">
                <div class="summary-pill">
                    <div class="summary-pill-label">Total</div>
                    <div class="summary-pill-value">' . number_format($total_mons) . '</div>
                </div>
                <div class="summary-pill">
                    <div class="summary-pill-label">Shinies</div>
                    <div class="summary-pill-value">' . number_format($total_shiny) . '</div>
                    ' . ($total_shiny > 0 ? '<div class="summary-pill-rate">1/' . number_format(round($total_mons/$total_shiny)) . '</div>' : '') . '
                </div>
                <div class="summary-pill">
                    <div class="summary-pill-label">Hundos</div>
                    <div class="summary-pill-value">' . number_format($total_hundo) . '</div>
                    ' . ($total_hundo > 0 ? '<div class="summary-pill-rate">1/' . number_format(round($total_mons/$total_hundo)) . '</div>' : '') . '
                </div>
                <div class="summary-pill">
                    <div class="summary-pill-label">Nundos</div>
                    <div class="summary-pill-value">' . number_format($total_nundo) . '</div>
                    ' . ($total_nundo > 0 ? '<div class="summary-pill-rate">1/' . number_format(round($total_mons/$total_nundo)) . '</div>' : '') . '
                </div>
            </div>
            <span class="summary-time">as of ' . date('H:i') . '</span>
        </div>

        <div id="search-field">
            <div id="search-wrapper">
                <svg id="search-icon" class="search-icon" viewBox="0 0 24 24">
                    <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                    <path d="M0 0h24v24H0z" fill="none" />
                </svg>
                <input type="text" id="searchpokemon" placeholder="Search for names..">
                <button type="button" id="search-clear" aria-label="Clear search">&#10005;</button>
            </div>
            <div id="search-count">Showing ' . count($all) . ' of ' . count($all) . ' Pokémon</div>
        </div>

        <button id="filter-toggle">Filters &#9662;</button>
        <div id="filter-container">
            <div class="filter-row" data-filter="gen">
                <div class="filter-label">Generations</div>
                <div class="filter-chips">';
foreach ($generations as $genNum => $gen) {
    $html .= '<button class="chip" data-value="' . $gen['name'] . '">' . $gen['name'] . '</button>';
}
$html .= '<button class="chip-clear" type="button">Clear all</button>
                </div>
            </div>
            <div class="filter-row" data-filter="type">
                <div class="filter-label">Types</div>
                <div class="filter-chips">';
$typeList = ['fire','water','grass','electric','rock','ground','psychic','dark','fairy','dragon','steel','ice','bug','ghost','normal','fighting','poison','flying'];
foreach ($typeList as $type) {
    $html .= '<button class="chip" data-value="' . $type . '">' . ucfirst($type) . '</button>';
}
$html .= '<button class="chip-clear" type="button">Clear all</button>
                </div>
            </div>
            <div class="filter-row" data-filter="rarity">
                <div class="filter-label">Rarity</div>
                <div class="filter-chips">
                    <button class="chip" data-value="legendary">Legendary</button>
                    <button class="chip" data-value="mythical">Mythical</button>
                    <button class="chip" data-value="ultra">Ultra Beast</button>
                    <button class="chip-clear" type="button">Clear all</button>
                </div>
            </div>
        </div>
    </div>

    <div id="shiny_table">
        <table class="dark-changer' . ($isDark ? " dark" : "") . '" id="statstable">
            <thead>
                <tr>
                    <th scope="col" class="col-icon"> </th>
                    <th class="sortable col-name" scope="col">Pokemon</th>
                    <th class="sortable col-stat" scope="col">Shinies</th>
                    <th class="sortable col-stat" scope="col">Hundos</th>
                    <th class="sortable col-stat" scope="col">Nundos</th>
                    <th class="sortable col-total" scope="col">Total</th>
                </tr>
            </thead>
            <tbody id="table_body">';
foreach ($all as $shiny) {
    $pokemonImageUrl = sprintf($config['images'], (int)$shiny['pokemon_id']);
    $genName = getGenName((int)$shiny['pokemon_id'], $generations);
    $types = getPokemonTypes($shiny['pokemon_id'], $data);
    $rarity = getPokemonRarity($shiny['pokemon_id'], $data);
    $pokemonName = $data['pokemon'][$shiny['pokemon_id']]['name'] ?? ('Unknown #' . $shiny['pokemon_id']);

    $html .= '<tr data-gen="' . htmlspecialchars($genName) . '" data-type="' . htmlspecialchars($types) . '" data-rarity="' . htmlspecialchars($rarity) . '">';
    $html .= '<td class="mobile-hide"><img src="' . $pokemonImageUrl . '" class="icon"/></td>';
    $html .= '<td data-label="Pokemon" data-sort="' . $shiny['pokemon_id'] . '">' . $pokemonName . ' <span class="pokemon-id">#' . $shiny['pokemon_id'] . '</span> <img src="' . $pokemonImageUrl . '" class="icon desktop-hide"/></td>';

    // Shinies (merged count + rate)
    $html .= '<td data-label="Shinies" data-sort="'. ($shiny['shiny']>0? $shiny['shiny']:0) .'">';
    if ($shiny['shiny'] > 0) {
        $html .= number_format($shiny['shiny']) . '<span class="rate-sub">1/' . number_format(round($shiny['pokemoncount']/$shiny['shiny'])) . '</span>';
    } else {
        $html .= '<span class="muted">&mdash;</span>';
    }
    $html .= '</td>';

    // Hundos (merged count + rate)
    $html .= '<td data-label="Hundos" data-sort="'. ($shiny['hundo']>0? $shiny['hundo']:0) .'">';
    if ($shiny['hundo'] > 0) {
        $html .= number_format($shiny['hundo']) . '<span class="rate-sub">1/' . number_format(round($shiny['pokemoncount']/$shiny['hundo'])) . '</span>';
    } else {
        $html .= '<span class="muted">&mdash;</span>';
    }
    $html .= '</td>';

    // Nundos (merged count + rate)
    $html .= '<td data-label="Nundos" data-sort="'. ($shiny['nundo']>0? $shiny['nundo']:0) .'">';
    if ($shiny['nundo'] > 0) {
        $html .= number_format($shiny['nundo']) . '<span class="rate-sub">1/' . number_format(round($shiny['pokemoncount']/$shiny['nundo'])) . '</span>';
    } else {
        $html .= '<span class="muted">&mdash;</span>';
    }
    $html .= '</td>';

    $html .= '<td data-label="Total" data-sort="'. ($shiny['pokemoncount']>0? $shiny['pokemoncount']:0) . '">'. number_format($shiny['pokemoncount']) .'</td>';
    $html .= '</tr>';
}
$html .= '</tbody>
        </table>
    </div>
    <div id="mobile-list"></div>
    <div id="footer"></div>
    <script src="./javascript.js?v=11"></script>
</body>
</html>
';
echo $html;
