<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Interop\Container\ContainerInterface as Container;

// TODO: support for login attempt count

//throw new Slim\Exception\MethodNotAllowedException($request, $response, array());
//return $response->withStatus(405);
//throw new Slim\Exception\NotFoundException($request, $response);

class AuthContainer extends core {
	private $user_id;

	public function __construct(Container $ci) {
		parent::__construct($ci);
		$this->user_id	= -1;
		/*
		if (ini_set('session.use_only_cookies', 1) === FALSE) {
			$this->logger->addWarning('Cannot force session.use_only_cookies php parameter. The session wont be secure');
		}
		$cookieParams = session_get_cookie_params();
		session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], SECURE, true);*/
		if (!isset($_SESSION['canary'])) {
			session_regenerate_id(true);
			$_SESSION['canary'] = [
				'birth' => time(),
				'IP' => $_SERVER['REMOTE_ADDR'],
				'user_agent' => $_SERVER['HTTP_USER_AGENT']
			];
			// no canary, not authentified
			unset($_SESSION['auth_id']);
		}
		if ($_SESSION['canary']['IP'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['canary']['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
			// completly wipe the session as the canary is dead
			$this->logger->addWarning('Login failed from '.$_SERVER['REMOTE_ADDR'].' as '.$login);
			$this->disconnect();
		}
		// Regenerate session ID every five minutes:
		if ($_SESSION['canary']['birth'] < time() - 300) {
			session_regenerate_id(true);
			$_SESSION['canary']['birth'] = time();
			
		}
		if ($this->authenticated())
			$this->user_id = $_SESSION['auth_id'];
	}

	public function authenticated() {
		return isset($_SESSION['auth_id']);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	public function setPassword($pass) {
		if (!$this->authenticated()) return false;
		$s = $this->db->prepare('update u$users set passhash = :pass where id = :id');
		$s->bindParam(':id', $this->user_id, PDO::PARAM_INT);
		$s->bindParam(':pass', password_hash($pass, PASSWORD_DEFAULT), PDO::PARAM_STR);
		$s->execute();
	}

	public function authenticate($login, $pass) {
		$s = $this->db->prepare('select passhash, id from u$users where username = :login');
		$s->bindParam(':login', $login, PDO::PARAM_STR);
		$s->execute();
		if( !($r = $s->fetch()) )
			unset($_SESSION['auth_id']);
		else {
			if (password_verify($pass, $r['passhash'])) {
				$_SESSION['auth_id']	= $r['id'];
				$this->user_id		= $r['id'];
			} else {
				$this->logger->addWarning('Login failed from '.$_SERVER['REMOTE_ADDR'].' as '.$login);
			}
		}
		return $this->authenticated();
	}
	public function disconnect() {
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
		session_destroy();
		session_start();
		session_regenerate_id(true);
		$_SESSION = array();
		$_SESSION['canary'] = [
			'birth' => time(),
			'IP' => $_SERVER['REMOTE_ADDR'],
			'user_agent' => $_SERVER['HTTP_USER_AGENT']
		];
		$this->user_id	= -1;
	}

	public function getUserName() {
		if (! $this->authenticated())
			return 'Guest';
		$s = $this->db->prepare('select username, firstname, lastname from u$users where id=:uid');
		$s->bindParam(':uid', $this->user_id,	PDO::PARAM_INT);
		$s->execute();
		if( !($r = $s->fetch()) )
			return false;
		if ($r['firstname']=='' and $r['lastname']=='')
			return $r['username'];
		return $r['firstname'].' '.$r['lastname'];
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Permission management (this could have it's own container)
	public function isAdmin() {
		if (! $this->authenticated())
			return false;
		$s = $this->db->prepare('select count(*) as cnt from p$users_admin where user_id=:uid');
		$s->bindParam(':uid', $this->user_id,	PDO::PARAM_INT);
		$s->execute();
		if( !($r = $s->fetch()) )
			return false;
		return $r['cnt']>0;
	}

	public function haveDomain($domain_id) {
		if (! $this->authenticated())
			return false;
		$s = $this->db->prepare('select count(*) as cnt from p$users_all_domains where user_id=:uid and domain_id=:did');
		$s->bindParam(':uid', $this->user_id,	PDO::PARAM_INT);
		$s->bindParam(':did', $domain_id,	PDO::PARAM_INT);
		$s->execute();
		if( !($r = $s->fetch()) )
			return false;
		return $r['cnt']>0;
	}

	public function haveHost($host_id) {
		if (! $this->authenticated())
			return false;
		$s = $this->db->prepare('select count(*) as cnt from p$users_all_hosts where user_id=:uid and host_id=:hid');
		$s->bindParam(':uid', $this->user_id,	PDO::PARAM_INT);
		$s->bindParam(':hid', $host_id,		PDO::PARAM_INT);
		$s->execute();
		if( !($r = $s->fetch()) )
			return false;
		return $r['cnt']>0;
	}

	public function haveService($service_id) {
		if (! $this->authenticated())
			return false;
		$s = $this->db->prepare('select count(*) as cnt from p$users_all_services where user_id=:uid and serv_id=:sid');
		$s->bindParam(':uid', $this->user_id,	PDO::PARAM_INT);
		$s->bindParam(':sid', $service_id,	PDO::PARAM_INT);
		$s->execute();
		if( !($r = $s->fetch()) )
			return false;
		return $r['cnt']>0;
	}

	public function haveApp($app_id) {
		if (! $this->authenticated())
			return false;
		$s = $this->db->prepare('select count(*) as cnt from p$users_all_domains where user_id=:uid and app_id=:aid');
		$s->bindParam(':uid', $this->user_id,	PDO::PARAM_INT);
		$s->bindParam(':aid', $app_id,		PDO::PARAM_INT);
		$s->execute();
		if( !($r = $s->fetch()) )
			return false;
		return $r['cnt']>0;
	}

	public function assertAdmin($request, $response) {
		if (!$this->isAdmin())
			throw new Slim\Exception\MethodNotAllowedException($request, $response, array());
	}

	public function assertDomain($domain_id, $request, $response) {
		if (!$this->haveDomain($domain_id))
			throw new Slim\Exception\MethodNotAllowedException($request, $response, array());
	}

	public function assertHost($host_id, $request, $response) {
		if (!$this->haveHost($host_id))
			throw new Slim\Exception\MethodNotAllowedException($request, $response, array());
	}

	public function assertService($service_id, $request, $response) {
		if (!$this->haveService($service_id))
			throw new Slim\Exception\MethodNotAllowedException($request, $response, array());
	}

	public function assertApp($app_id, $request, $response) {
		if (!$this->haveApp($app_id))
			throw new Slim\Exception\MethodNotAllowedException($request, $response, array());
	}


/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function loginPage(Request $request, Response $response) {
 		return $this->view->render($response, 'login.twig', []);
	}

	public function loginPost(Request $request, Response $response) {
		if ($this->authenticate($request->getParam('username'), $request->getParam('password'))) {
			$this->flash->addMessage('info', 'Welcome '.$this->getUserName());
			if(isset($_SERVER['HTTP_REFERER']) ) {
				$t = explode('/', $_SERVER['HTTP_REFERER']);
				if ($t[2] == $_SERVER['SERVER_NAME'])
					return $response->withRedirect($_SERVER['HTTP_REFERER']);
			}
			return $response->withRedirect($this->router->pathFor('home'));
		} else {
			$this->flash->addMessage('error', 'Failed to login.');
			return $response->withRedirect($this->router->pathFor('auth.login'));
 		}
	}

	public function signout(Request $request, Response $response) {
		$this->disconnect();
		$this->flash->addMessage('info', 'Succesfully signed out');
 		return $response->withRedirect($this->router->pathFor('home'));
	}
}

?>

