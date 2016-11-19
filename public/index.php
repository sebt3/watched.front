<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
spl_autoload_register(function ($classname) {
	require ("../classes/" . $classname . ".php");
});
$config = json_decode(file_get_contents("../front.config.json"), true);
$app = new \Slim\App([ "settings" => $config ]);

// containers
$container = $app->getContainer();
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('watched');
    $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
    $logger->pushHandler($file_handler);
    return $logger;
};
$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};
$container['view'] = function ($container) use ($app) {
    $view = new \Slim\Views\Twig('../templates/', [
        'cache' => false
        //'cache' => 'path/to/cache'
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));
    $view['menu'] = new MenuObject($app);
    return $view;
};
$container['notFoundHandler'] = function ($c) { 
	return new NotFoundHandler($c->get('view'), function ($request, $response) use ($c) { 
		return $c['response']->withStatus(404)->withHeader('Content-type', 'text/html'); 
	}); 
};
// TODO: ajouter les handler pour 405 et erreurs

function haveTable($db, $table) {
	$stmt = $db->prepare("SELECT count(table_name) as cnt from live_tables where table_name = :tbid");
	$stmt->bindParam(':tbid', $table);
        $stmt->execute();
        $row = $stmt->fetch();
	return $row["cnt"]+0 >0;
}

// adding routes ------------------------------------------------------------------------------------------------------
// TODO: add the login group here
$app->group('/api', function () use ($app) {
	$app->get('/ressources/{name}/{aid:[0-9]+}/{rid:[0-9]+}[/{params:.*}]', '\Api:ressources')->setName('apiRessource');
})->add(function ($request, $response, $next) use ($container) {
	// TODO: add permission checks for api access here
	$response = $next($request, $response);
	if (404 === $response->getStatusCode() && 0 === $response->getBody()->getSize()) {
		$h = $container['notFoundHandler'];
		return $h($request, $response);
	}
	return $response ;
});

$app->get('/', '\Dashboard:dashboard')->setName('home');

$app->group('/hosts', function () use ($app) {
	$app->get('', '\Host:hosts')->setName('hosts');
	$app->get('/{id:[0-9]+}', '\Host:host')->setName('host');
	$app->get('/{id:[0-9]+}/services', '\HostService:hostServices')->setName('services');
	$app->get('/{hid:[0-9]+}/services/{sid:[0-9]+}', '\HostService:hostService')->setName('service');
	$app->get('/{id:[0-9]+}/ressource[s]', '\Host:ressources')->setName('ressources');
	$app->get('/{aid:[0-9]+}/ressources/{rid:[0-9]+}', '\HostRessource:ressource')->setName('ressource');
})->add(function ($request, $response, $next) use ($container) {
	// TODO: add permission checks for Host access here
	$response = $next($request, $response);
	if (404 === $response->getStatusCode() && 0 === $response->getBody()->getSize()) {
		$h = $container['notFoundHandler'];
		return $h($request, $response);
	}
	return $response ;
});

$app->group('/events', function () use ($app) {
	$app->get('', '\Event:events')->setName('events');
	$app->get('/{id:[0-9]+}', '\Event:event')->setName('event');
})->add(function ($request, $response, $next) use ($container) {
	// TODO: add permission checks for event access here
	$response = $next($request, $response);
	if (404 === $response->getStatusCode() && 0 === $response->getBody()->getSize()) {
		$h = $container['notFoundHandler'];
		return $h($request, $response);
	}
	return $response ;
});


$app->run();
?>
