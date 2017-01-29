<?php
/////////////////////////////////////////////////////////////////////////////////////////////
// dependencies
session_start();
require '../vendor/autoload.php';
spl_autoload_register(function ($classname) {
	if (file_exists('../classes/Containers/' . $classname . '.php'))
		require ('../classes/Containers/' . $classname . '.php');
	else if (file_exists('../classes/Admin/' . $classname . '.php'))
		require ('../classes/Admin/' . $classname . '.php');
	else if ($classname!='Throwable')
		require ('../classes/' . $classname . '.php');
	
});
$app = new \Slim\App([ 'settings' => json_decode(file_get_contents('../front.config.json'), true) ]);

/////////////////////////////////////////////////////////////////////////////////////////////
// containers
$container = $app->getContainer();
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('watched');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler('../logs/app.log'));
    return $logger;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],  $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

/*$container['csrf'] = function($container){
    return new \Slim\Csrf\Guard;
};*/
$container['flash'] = function () {	return new \Slim\Flash\Messages; };
$container['auth'] = function ($c) {	return new AuthContainer($c); };
$container['menu']  = function ($c) {	return new MenuObject($c); };

$container['view'] = function ($container) use ($app) {
    $view = new \Slim\Views\Twig('../templates/', [
        'cache' => false
        //'cache' => 'path/to/cache'
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));
    $view->getEnvironment()->addGlobal('menu',  $container->menu);
    $view->getEnvironment()->addGlobal('flash', $container->get('flash'));

    return $view;
};
$container['notFoundHandler'] = function ($c) {		return new NotFoundHandler($c->get('view')); };
$container['notAllowedHandler'] = function ($c) {	return new NotAllowedHandler($c->get('view')); };
$container['phpErrorHandler'] = function ($c) {		return new PhpErrorHandler($c->get('view')); };
$container['errorHandler'] = function ($c) {		return new ErrorHandler($c->get('view')); };

/////////////////////////////////////////////////////////////////////////////////////////////
// middlewares
$app->add(new Finalyse($container));

/////////////////////////////////////////////////////////////////////////////////////////////
// Routes 
$app->group('/admin', function () use ($app) {
	$app->get('', '\Admin:admin')->setName('admin');
	$app->group('/users', function () use ($app) {
		$app->get('', '\Users:listAll')->setName('admin.users.list');
		$app->get('/new', '\Users:add')->setName('admin.users.add');
		$app->post('/new', '\Users:addPost');
		$app->get('/{id:[0-9]+}', '\Users:user')->setName('admin.users.change');
		$app->post('/{id:[0-9]+}', '\Users:change');
		$app->post('/{id:[0-9]+}/delete', '\Users:del')->setName('admin.users.delete');
		// user <-> team
		$app->get('/{id:[0-9]+}/addTeam', '\Users:addTeam')->setName('admin.users.addTeam');
		$app->post('/{id:[0-9]+}/addTeam', '\Users:postTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/delete', '\Users:deleteTeam')->setName('admin.users.deleteTeam');
		// user <-> properties
		$app->get('/{id:[0-9]+}/addProperty', '\Users:addProp')->setName('admin.users.addProp');
		$app->post('/{id:[0-9]+}/addProperty', '\Users:postProp');
		$app->get('/{id:[0-9]+}/properties/{pid:[0-9]+}', '\Users:changeProp')->setName('admin.users.changeProp');
		$app->post('/{id:[0-9]+}/properties/{pid:[0-9]+}', '\Users:updateProp');
		$app->post('/{id:[0-9]+}/properties/{pid:[0-9]+}/delete', '\Users:deleteProp')->setName('admin.users.deleteProp');
	});
	$app->group('/teams', function () use ($app) {
		$app->get('', '\Teams:listAll')->setName('admin.teams.list');
		$app->get('/new', '\Teams:add')->setName('admin.teams.add');
		$app->post('/new', '\Teams:addPost');
		$app->get('/{id:[0-9]+}', '\Teams:team')->setName('admin.teams.change');
		$app->post('/{id:[0-9]+}', '\Teams:change');
		$app->post('/{id:[0-9]+}/delete', '\Teams:del')->setName('admin.teams.delete');
	});
	$app->group('/roles', function () use ($app) {
		$app->get('', '\Roles:listAll')->setName('admin.roles.list');
		$app->get('/new', '\Roles:add')->setName('admin.roles.add');
		$app->post('/new', '\Roles:addPost');
		$app->get('/{id:[0-9]+}', '\Roles:role')->setName('admin.roles.change');
		$app->post('/{id:[0-9]+}', '\Roles:change');
		$app->post('/{id:[0-9]+}/delete', '\Roles:del')->setName('admin.roles.delete');
	});
	$app->group('/agents', function () use ($app) {
		$app->get('', '\Agents:listAll')->setName('admin.agents.list');
		$app->get('/new', '\Agents:add')->setName('admin.agents.add');
		$app->post('/new', '\Agents:addPost');
		$app->get('/{id:[0-9]+}', '\Agents:agent')->setName('admin.agents.change');
		$app->post('/{id:[0-9]+}', '\Agents:change');
		$app->post('/{id:[0-9]+}/delete', '\Agents:del')->setName('admin.agents.delete');
	});
	$app->group('/apps', function () use ($app) {
		$app->get('', '\Apps:listAll')->setName('admin.apps.list');
		$app->get('/new', '\Apps:add')->setName('admin.apps.add');
		$app->post('/new', '\Apps:addPost');
		$app->get('/{id:[0-9]+}', '\Apps:app')->setName('admin.apps.change');
		$app->post('/{id:[0-9]+}', '\Apps:change');
		$app->post('/{id:[0-9]+}/delete', '\Apps:del')->setName('admin.apps.delete');
		// apps <-> team
		$app->get('/{id:[0-9]+}/addTeam', '\Apps:addTeam')->setName('admin.apps.addTeam');
		$app->post('/{id:[0-9]+}/addTeam', '\Apps:postTeam');
		$app->get('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}', '\Apps:team')->setName('admin.apps.changeTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}', '\Apps:changeTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}/delete', '\Apps:deleteTeam')->setName('admin.apps.deleteTeam');
		// apps <-> service
		$app->get('/{id:[0-9]+}/addService', '\Apps:addService')->setName('admin.apps.addService');
		$app->post('/{id:[0-9]+}/addService', '\Apps:postService');
		$app->post('/{id:[0-9]+}/service/{sid:[0-9]+}/delete', '\Apps:deleteService')->setName('admin.apps.deleteService');
	});
	$app->group('/domains', function () use ($app) {
		$app->get('', '\Domains:listAll')->setName('admin.domains.list');
		$app->get('/new', '\Domains:add')->setName('admin.domains.add');
		$app->post('/new', '\Domains:addPost');
		$app->get('/{id:[0-9]+}', '\Domains:domain')->setName('admin.domains.change');
		$app->post('/{id:[0-9]+}', '\Domains:change');
		$app->post('/{id:[0-9]+}/delete', '\Domains:del')->setName('admin.domains.delete');
		// domain <-> team
		$app->get('/{id:[0-9]+}/addTeam', '\Domains:addTeam')->setName('admin.domains.addTeam');
		$app->post('/{id:[0-9]+}/addTeam', '\Domains:postTeam');
		$app->get('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}', '\Domains:team')->setName('admin.domains.changeTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}', '\Domains:changeTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}/delete', '\Domains:deleteTeam')->setName('admin.domains.deleteTeam');
		// domain <-> host
		$app->get('/{id:[0-9]+}/addHost', '\Domains:addHost')->setName('admin.domains.addHost');
		$app->post('/{id:[0-9]+}/addHost', '\Domains:postHost');
		$app->post('/{id:[0-9]+}/hosts/{hid:[0-9]+}/delete', '\Domains:deleteHost')->setName('admin.domains.deleteHost');
	});
	$app->group('/clean', function () use ($app) {
		$app->get('', '\Clean:listAll')->setName('admin.clean');
		$app->post('/service/{id:[0-9]+}/delete', '\Clean:deleteService')->setName('admin.clean.deleteService');
		$app->post('/host/{id:[0-9]+}/delete', '\Clean:deleteHost')->setName('admin.clean.deleteHost');
	});
})->add(function ($request, $response, $next) {
    $this->auth->assertAdmin($request, $response);
    return $response = $next($request, $response);
});

$app->group('/auth', function () use ($app) {
	$app->get('/login', '\AuthContainer:loginPage')->setName('auth.login');
	$app->post('/login', '\AuthContainer:loginPost');
	$app->get('/signout', '\AuthContainer:signout')->setName('auth.signout');
});

$app->group('/api', function () use ($app) {
	$app->get('/ressources/{name}/{aid:[0-9]+}/{rid:[0-9]+}[/{params:.*}]', '\Api:ressources')->setName('apiRessource');
	$app->get('/services/{id:[0-9]+}[/{params:.*}]', '\Api:services')->setName('apiService');
});

$app->get('/', '\Dashboard:dashboard')->setName('home');

$app->group('/hosts', function () use ($app) {
	$app->get('', '\Host:hosts')->setName('hosts');
	$app->get('/{id:[0-9]+}', '\Host:host')->setName('host');
	$app->get('/{id:[0-9]+}/services', '\HostService:hostServices')->setName('services');
	$app->get('/{hid:[0-9]+}/services/{sid:[0-9]+}', '\HostService:hostService')->setName('service');
	$app->get('/{id:[0-9]+}/ressource[s]', '\Host:ressources')->setName('ressources');
	$app->get('/{aid:[0-9]+}/ressources/{rid:[0-9]+}', '\HostRessource:ressource')->setName('ressource');
});

$app->group('/events', function () use ($app) {
	$app->get('', '\Event:events')->setName('events');
	$app->get('/{id:[0-9]+}', '\Event:event')->setName('event');
});

/////////////////////////////////////////////////////////////////////////////////////////////
// running
$app->run();
?>
