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
const APP_URL = "https://heroku-test-b.herokuapp.com";  // URL to the app

$app->get('/', function() use($app) {
    $app['monolog']->addDebug('logging output.');
    echo "<!-- Echo does show up in output of app -->\n";
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Heroku Test B</title>
    <link rel="icon" href="./images/favicon.ico" />
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
        echo "\n    <p>No results found from query</p>";
    }
    else {
        echo "<h3>Query results:</h3>";
        foreach($names as $n) {
            echo "\n    <p>$n</p>";
        }
    }

    //Add some links to the other pages
    echo "<br /><p><a href=\"<?= APP_URL ?>/dbreset\">Reset the test_table</a></p>\n";
    echo "<br /><p><a href=\"<?= APP_URL ?>/dbinsert\">Insert a new name into the test_table</a></p>";

    echo "\n</body>\n</html>";
?>
<!-- Escaping PHP code comment -->

<?php
    return null;
    //return $app['twig']->render($str);  //Original return statement
});

//Web handler to try and reset the test_table
$app->get('/dbreset', function() use($app) {
    $query = "DROP TABLE IF EXISTS test_table
CREATE TABLE test_table(name TEXT)";
    $st = $app['pdo']->prepare($query);
    $st->execute();

?>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Heroku Test B - Reset test_table</title>
    <link rel="icon" href="./images/favicon.ico" />
</head>
<body>
    <h2>test_table has been reset</h2>
    <p><a href="<?= APP_URL ?>">Go back to main page</a></p>
</body>
</html>
<?php
    return null;
});

//Web handler to try and add a name to the test_table
$app->get('/dbinsert', function() use($app) {
    $insert_name = "name" . strval(rand(0, 1000));
    $query = "INSERT INTO test_table VALUES ($insert_name)";
    $st = $app['pdo']->prepare($query);
    $st->execute();
    ?>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Heroku Test B - Insert Into test_table</title>
    <link rel="icon" href="./images/favicon.ico" />
</head>
<body>
    <h2>test_table has added a new name value (<?= $insert_name ?>)</h2>
    <p><a href="<?= APP_URL ?>">Go back to main page</a></p>
</body>
</html>
    <?php
    return null;
});

$app->run();

?>