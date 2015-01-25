<?php
$config = file_get_contents('config.json');
$config = json_decode($config);

$uri = 'https://bitbucket.org/api/1.0/user/repositories/';

$ch = curl_init($uri);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic '.$config->['auth'], // base64_encode("foo:bar")
        'X-Target-URI: https://bitbucket.org']
]);

$response = curl_exec($ch);
$list = json_decode($response);

$stats = [];

echo '<h1>Bitbucket statistics</h1>';
printf('<h2>Repo count: %d</h2>', count($list));

echo '<table>';
echo '<tr><th>Name</th><th>Language</th><th>Created</th></tr>';
foreach($list as $repo) {
    $lang = $repo->language;

    if (empty($stats[$lang])) {
        $stats[$repo->language] = [];
    }

    $stats[$lang][] = $repo->name;

    $date = date('d/m/Y', strtotime($repo->utc_created_on));
    printf('<tr><td>%s</td><td>%s</td><td>%s</td>', $repo->name, $repo->language, $date);
}
echo '</table>';

$init = [];
foreach($stats as $lang => $value) {
    $init[$lang] = 0;
}

$points = [];
foreach(range(0, count($list)-1) as $i) {
    $repo = $list[$i];
    $date = date('d/m/Y', strtotime($repo->utc_created_on));

    if ($i > 0) {
        $prev = $points[$i - 1]['repos'];
    } else {
        $prev = $init;
    }

    $points[$i] = [
        'date' => $date,
        'repos' => $prev
    ];

    if ($i > 0 && !empty($prev[$repo->language])) {
        $count = $prev[$repo->language] + 1;
    } else {
        $count = 1;
    }

    $points[$i]['repos'][$repo->language] = $count;
}

echo '<h2>Programming language breakdown</h2>';
foreach($stats as $lang => $list) {

    printf('<h4>%s (%d)</h4>', $lang, count($list));
    echo '<ul>';

    foreach($list as $repo) {
        printf('<li>%s</li>', $repo);

    }
    echo '</ul>';
}

echo '<h2>Programming language total</h2>';
echo '<table>';
foreach($stats as $lang => $list) {
    printf('<tr><td>%s</td><td>%d</td></tr></h4>', $lang, count($list));
}
echo '</table>';

echo '<h2>Growth</h2>';

echo '<table>';
echo PHP_EOL;
echo '<tr><th>Date</th>';

foreach($points[0]['repos'] as $lang => $value) {
    printf('<th>%s</th>', $lang);
}

echo '</tr>';
echo PHP_EOL;
foreach($points as $point) {
    echo '<tr>';
    printf('<td>%s</td>', $point['date']);

    echo PHP_EOL;
        foreach($point['repos'] as $count) {
            printf('<td>%d</td>', $count);
            echo PHP_EOL;
        }
    echo '</tr>';
    echo PHP_EOL;
}
echo '</table>';
echo PHP_EOL;

