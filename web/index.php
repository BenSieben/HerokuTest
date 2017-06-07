<?php

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

//Register database code
$dbopts = parse_url(getenv('DATABASE_URL'));
$app->register(new Csanquer\Silex\PdoServiceProvider\Provider\PDOServiceProvider('pdo'),
    array(
        'pdo.server' => array(
            'driver'   => 'pgsql',
            'user' => $dbopts["user"],
            'password' => $dbopts["pass"],
            'host' => $dbopts["host"],
            'port' => $dbopts["port"],
            'dbname' => ltrim($dbopts["path"],'/')
        )
    )
);

// Our web handlers

$app->get('/', function() use($app) {
    $app['monolog']->addDebug('logging output.');
    $page = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Heroku Test B</title>
    <link rel="icon" href="./favicon.ico" />
</head>
<body>
    <h1>Hello Heroku</h1>';
    //Test a query
    $st = $app['pdo']->prepare('SELECT name FROM test_table');
    $st->execute();

    $names = array();
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $app['monolog']->addDebug('Row ' . $row['name']);
        $names[] = $row;
    }

    if(count($names) == 0) {
        //No results of names
        $page .= "\n    <p>No results found from query</p>";
    }
    else {
        $page .= "<h3>Query results:</h3>";
        foreach($names as $n) {
            $page .= "\n    <p>$n</p>";
        }
    }

    $page .= "\n</body>\n</html>";
    echo "<!-- Does echo show up? -->";
    return $page;
    //return $app['twig']->render($str);  //Original return statement
});

$app->run();

?>