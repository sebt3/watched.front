<?php
/////////////////////////////////////////////////////////////////////////////////////////////
// dependencies
session_start();
require '../vendor/autoload.php';
spl_autoload_register(function ($classname) {
	$class = implode(DIRECTORY_SEPARATOR, explode('\\', $classname));
	require '..'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$class.'.php';
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
$container['trans'] = function ($c) {	return new \Containers\Translate($c); };
$container['auth'] = function ($c) {	return new \Containers\AuthContainer($c); };
$container['menu']  = function ($c) {	return new \Containers\MenuObject($c); };

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
    $view->getEnvironment()->addGlobal('flash', $container->flash);
    $view->getEnvironment()->addFunction(new Twig_SimpleFunction('_', $container->trans));
    $view->getEnvironment()->addFunction(new Twig_SimpleFunction('json', 'json_encode', array('is_safe' => array('html'))));

    return $view;
};
$container['notFoundHandler']	= function ($c) {	return new \Containers\NotFoundHandler($c->get('view')); };
$container['notAllowedHandler'] = function ($c) {	return new \Containers\NotAllowedHandler($c->get('view')); };
$container['phpErrorHandler']	= function ($c) {	return new \Containers\PhpErrorHandler($c->get('view')); };
$container['errorHandler']	= function ($c) {	return new \Containers\ErrorHandler($c->get('view')); };

/////////////////////////////////////////////////////////////////////////////////////////////
// middlewares
//$app->add(new \Containers\Finalyse($container));

/////////////////////////////////////////////////////////////////////////////////////////////
// Routes 
$app->group('/admin', function () use ($app) {
	$app->get('', '\Admin\Admin:admin')->setName('admin');
	$app->group('/users', function () use ($app) {
		$app->get('', '\Admin\Users:listAll')->setName('admin.users.list');
		$app->get('/new', '\Admin\Users:add')->setName('admin.users.add');
		$app->post('/new', '\Admin\Users:addPost');
		$app->get('/{id:[0-9]+}', '\Admin\Users:user')->setName('admin.users.change');
		$app->post('/{id:[0-9]+}', '\Admin\Users:change');
		$app->post('/{id:[0-9]+}/delete', '\Admin\Users:del')->setName('admin.users.delete');
		// user <-> team
		$app->get('/{id:[0-9]+}/addTeam', '\Admin\Users:addTeam')->setName('admin.users.addTeam');
		$app->post('/{id:[0-9]+}/addTeam', '\Admin\Users:postTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/delete', '\Admin\Users:deleteTeam')->setName('admin.users.deleteTeam');
		// user <-> properties
		$app->get('/{id:[0-9]+}/addProperty', '\Admin\Users:addProp')->setName('admin.users.addProp');
		$app->post('/{id:[0-9]+}/addProperty', '\Admin\Users:postProp');
		$app->get('/{id:[0-9]+}/properties/{pid:[0-9]+}', '\Admin\Users:changeProp')->setName('admin.users.changeProp');
		$app->post('/{id:[0-9]+}/properties/{pid:[0-9]+}', '\Admin\Users:updateProp');
		$app->post('/{id:[0-9]+}/properties/{pid:[0-9]+}/delete', '\Admin\Users:deleteProp')->setName('admin.users.deleteProp');
	});
	$app->group('/teams', function () use ($app) {
		$app->get('', '\Admin\Teams:listAll')->setName('admin.teams.list');
		$app->get('/new', '\Admin\Teams:add')->setName('admin.teams.add');
		$app->post('/new', '\Admin\Teams:addPost');
		$app->get('/{id:[0-9]+}', '\Admin\Teams:team')->setName('admin.teams.change');
		$app->post('/{id:[0-9]+}', '\Admin\Teams:change');
		$app->post('/{id:[0-9]+}/delete', '\Admin\Teams:del')->setName('admin.teams.delete');
	});
	$app->group('/roles', function () use ($app) {
		$app->get('', '\Admin\Roles:listAll')->setName('admin.roles.list');
		$app->get('/new', '\Admin\Roles:add')->setName('admin.roles.add');
		$app->post('/new', '\Admin\Roles:addPost');
		$app->get('/{id:[0-9]+}', '\Admin\Roles:role')->setName('admin.roles.change');
		$app->post('/{id:[0-9]+}', '\Admin\Roles:change');
		$app->post('/{id:[0-9]+}/delete', '\Admin\Roles:del')->setName('admin.roles.delete');
	});
	$app->group('/agents', function () use ($app) {
		$app->get('', '\Admin\Agents:listAll')->setName('admin.agents.list');
		$app->get('/new', '\Admin\Agents:add')->setName('admin.agents.add');
		$app->post('/new', '\Admin\Agents:addPost');
		$app->get('/{id:[0-9]+}', '\Admin\Agents:agent')->setName('admin.agents.change');
		$app->post('/{id:[0-9]+}', '\Admin\Agents:change');
		$app->post('/{id:[0-9]+}/delete', '\Admin\Agents:del')->setName('admin.agents.delete');
	});
	$app->group('/apps', function () use ($app) {
		$app->get('', '\Admin\Apps:listAll')->setName('admin.apps.list');
		$app->get('/new', '\Admin\Apps:add')->setName('admin.apps.add');
		$app->post('/new', '\Admin\Apps:addPost');
		$app->get('/{id:[0-9]+}', '\Admin\Apps:app')->setName('admin.apps.change');
		$app->post('/{id:[0-9]+}', '\Admin\Apps:change');
		$app->post('/{id:[0-9]+}/delete', '\Admin\Apps:del')->setName('admin.apps.delete');
		// apps <-> team
		$app->get('/{id:[0-9]+}/addTeam', '\Admin\Apps:addTeam')->setName('admin.apps.addTeam');
		$app->post('/{id:[0-9]+}/addTeam', '\Admin\Apps:postTeam');
		$app->get('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}', '\Admin\Apps:team')->setName('admin.apps.changeTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}', '\Admin\Apps:changeTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}/delete', '\Admin\Apps:deleteTeam')->setName('admin.apps.deleteTeam');
		// apps <-> service
		$app->get('/{id:[0-9]+}/addService', '\Admin\Apps:addService')->setName('admin.apps.addService');
		$app->post('/{id:[0-9]+}/addService', '\Admin\Apps:postService');
		$app->post('/{id:[0-9]+}/service/{sid:[0-9]+}/delete', '\Admin\Apps:deleteService')->setName('admin.apps.deleteService');
	});
	$app->group('/groups', function () use ($app) {
		$app->get('', '\Admin\Groups:listAll')->setName('admin.groups.list');
		$app->get('/new', '\Admin\Groups:add')->setName('admin.groups.add');
		$app->post('/new', '\Admin\Groups:addPost');
		$app->get('/{id:[0-9]+}', '\Admin\Groups:group')->setName('admin.groups.change');
		$app->post('/{id:[0-9]+}', '\Admin\Groups:change');
		$app->post('/{id:[0-9]+}/delete', '\Admin\Groups:del')->setName('admin.groups.delete');
		// groups <-> team
		$app->get('/{id:[0-9]+}/addTeam', '\Admin\Groups:addTeam')->setName('admin.groups.addTeam');
		$app->post('/{id:[0-9]+}/addTeam', '\Admin\Groups:postTeam');
		$app->get('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}', '\Admin\Groups:team')->setName('admin.groups.changeTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}', '\Admin\Groups:changeTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}/delete', '\Admin\Groups:deleteTeam')->setName('admin.groups.deleteTeam');
		// groups <-> app
		$app->get('/{id:[0-9]+}/addApp', '\Admin\Groups:addApp')->setName('admin.groups.addApp');
		$app->post('/{id:[0-9]+}/addApp', '\Admin\Groups:postApp');
		$app->post('/{id:[0-9]+}/application/{aid:[0-9]+}/delete', '\Admin\Groups:deleteApp')->setName('admin.groups.deleteApp');
	});
	$app->group('/domains', function () use ($app) {
		$app->get('', '\Admin\Domains:listAll')->setName('admin.domains.list');
		$app->get('/new', '\Admin\Domains:add')->setName('admin.domains.add');
		$app->post('/new', '\Admin\Domains:addPost');
		$app->get('/{id:[0-9]+}', '\Admin\Domains:domain')->setName('admin.domains.change');
		$app->post('/{id:[0-9]+}', '\Admin\Domains:change');
		$app->post('/{id:[0-9]+}/delete', '\Admin\Domains:del')->setName('admin.domains.delete');
		// domain <-> team
		$app->get('/{id:[0-9]+}/addTeam', '\Admin\Domains:addTeam')->setName('admin.domains.addTeam');
		$app->post('/{id:[0-9]+}/addTeam', '\Admin\Domains:postTeam');
		$app->get('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}', '\Admin\Domains:team')->setName('admin.domains.changeTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}', '\Admin\Domains:changeTeam');
		$app->post('/{id:[0-9]+}/team/{tid:[0-9]+}/{rid:[0-9]+}/delete', '\Admin\Domains:deleteTeam')->setName('admin.domains.deleteTeam');
		// domain <-> host
		$app->get('/{id:[0-9]+}/addHost', '\Admin\Domains:addHost')->setName('admin.domains.addHost');
		$app->post('/{id:[0-9]+}/addHost', '\Admin\Domains:postHost');
		$app->post('/{id:[0-9]+}/hosts/{hid:[0-9]+}/delete', '\Admin\Domains:deleteHost')->setName('admin.domains.deleteHost');
	});
	$app->group('/tables', function () use ($app) {
		$app->get('', '\Admin\Tables:listAll')->setName('admin.tables');
		$app->get('/{name}', '\Admin\Tables:viewTable')->setName('admin.tables.edit');
		$app->post('/{name}', '\Admin\Tables:postConfig');
		$app->get('/{name}/del', '\Admin\Tables:removeConfig')->setName('admin.tables.del');
	});
	$app->group('/clean', function () use ($app) {
		$app->get('', '\Admin\Clean:listAll')->setName('admin.clean');
		$app->post('/service/{id:[0-9]+}/delete', '\Admin\Clean:deleteService')->setName('admin.clean.deleteService');
		$app->post('/host/{id:[0-9]+}/delete', '\Admin\Clean:deleteHost')->setName('admin.clean.deleteHost');
	});
})->add(function ($request, $response, $next) {
    $this->auth->assertAdmin($request, $response);
    return $response = $next($request, $response);
});

$app->group('/auth', function () use ($app) {
	$app->get('/login', '\Containers\AuthContainer:loginPage')->setName('auth.login');
	$app->post('/login', '\Containers\AuthContainer:loginPost');
	$app->get('/signout', '\Containers\AuthContainer:signout')->setName('auth.signout');
});

$app->group('/api', function () use ($app) {
	$app->get('/services/{id:[0-9]+}[/{params:.*}]', '\Api:services')->setName('apiService');
});

$app->group('/widgets', function () use ($app) {
	$app->group('/gfx', function () use ($app) {
		$app->group('/host', function () use ($app) {
			$app->get('/{host_id:[0-9]+}/{res_id:[0-9]+}', '\Api:host_res')->setName('widgets.gfx.host');
			$app->get('/{host_id:[0-9]+}/{res_id:[0-9]+}/{min:[0-9\.]+}/{max:[0-9\.]+}', '\Api:host_res_min')->setName('widgets.gfx.host.minut');
			$app->get('/{host_id:[0-9]+}/{res_id:[0-9]+}/{min:[0-9\.]+}/{max:[0-9\.]+}/detail', '\Api:host_res_detail')->setName('widgets.gfx.host.detail');
		});
		$app->group('/serv', function () use ($app) {
			$app->get('/{serv_id:[0-9]+}/{res_id:[0-9]+}', '\Api:serv_res')->setName('widgets.gfx.serv');
			$app->get('/{serv_id:[0-9]+}/{res_id:[0-9]+}/{min:[0-9\.]+}/{max:[0-9\.]+}', '\Api:serv_res_min')->setName('widgets.gfx.serv.minut');
			$app->get('/{serv_id:[0-9]+}/{res_id:[0-9]+}/{min:[0-9\.]+}/{max:[0-9\.]+}/detail', '\Api:serv_res_detail')->setName('widgets.gfx.serv.detail');
		});
	});
	$app->group('/donut', function () use ($app) {
		$app->group('/dash', function () use ($app) {
			$app->get('/status', '\Dashboard:widgetDonutStatus')->setName('widgets.donut.dash.status');
			$app->get('/items', '\Dashboard:widgetDonutItem')->setName('widgets.donut.dash.items');
			$app->get('/domains', '\Dashboard:widgetDonutDomains')->setName('widgets.donut.dash.domains');
		});
		$app->group('/host', function () use ($app) {
			$app->get('/{id:[0-9]+}/status', '\Host:widgetDonutStatus')->setName('widgets.donut.host.status');
			$app->get('/{id:[0-9]+}/items', '\Host:widgetDonutItems')->setName('widgets.donut.host.items');
		});
		$app->group('/ressource', function () use ($app) {
			$app->get('/{host_id:[0-9]+}-{res_id:[0-9]+}/status', '\HostRessource:widgetDonutStatus')->setName('widgets.donut.ress.status');
			$app->get('/{host_id:[0-9]+}-{res_id:[0-9]+}/history', '\HostRessource:widgetDonutItems')->setName('widgets.donut.ress.items');
		});
	});
	$app->group('/table', function () use ($app) {
		$app->group('/dash', function () use ($app) {
			$app->get('/events', '\Dashboard:widgetTableEvent')->setName('widgets.table.dash.events');
			$app->get('/failed', '\Dashboard:widgetTableFailed')->setName('widgets.table.dash.failed');
		});
		$app->group('/host', function () use ($app) {
			$app->get('/{id:[0-9]+}/history', '\Host:widgetTableHistory')->setName('widgets.table.host.history');
		});
		$app->group('/ressource', function () use ($app) {
			$app->get('/{host_id:[0-9]+}-{res_id:[0-9]+}/history', '\HostRessource:widgetTableHistory')->setName('widgets.table.ress.history');
		});
		$app->group('/serv', function () use ($app) {
			$app->get('/{id:[0-9]+}/process', '\HostService:widgetTableProcess')->setName('widgets.table.serv.process');
			$app->get('/{id:[0-9]+}/sockets', '\HostService:widgetTableSockets')->setName('widgets.table.serv.sockets');
		});
	});
	$app->group('/list', function () use ($app) {
		$app->group('/host', function () use ($app) {
			$app->get('/{id:[0-9]+}/services', '\Host:widgetListServices')->setName('widgets.list.host.services');
			$app->get('/{id:[0-9]+}/stats', '\Host:widgetListStats')->setName('widgets.list.host.stats');
			$app->get('/{id:[0-9]+}/ressources', '\Host:widgetListRessources')->setName('widgets.list.host.ressources');
		});
	});
	$app->group('/progess', function () use ($app) {
		$app->group('/host', function () use ($app) {
			$app->get('/{id:[0-9]+}/cpu', '\Host:widgetProgressCpu')->setName('widgets.progress.host.cpu');
			$app->get('/{id:[0-9]+}/storage', '\Host:widgetProgressStorage')->setName('widgets.progress.host.storage');
		});
	});
	$app->group('/property', function () use ($app) {
		$app->group('/event', function () use ($app) {
			$app->get('/{id:[0-9]+}', '\Event:widgetProperty')->setName('widgets.property.event');
		});
	});
	$app->group('/custom', function () use ($app) {
		$app->group('/host', function () use ($app) {
			$app->get('/{id:[0-9]+}/memory', '\Host:widgetMemSwap')->setName('widgets.custom.host.memory');
		});
	});
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
