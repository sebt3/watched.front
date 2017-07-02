<?php
namespace Containers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Interop\Container\ContainerInterface as Container;
use \PDO as PDO;

// TODO: support for login attempt count

if (!function_exists('random_bytes')) {
	function random_bytes($bytes) {
		if (function_exists('mcrypt_create_iv') && version_compare(PHP_VERSION, '5.3.7') >= 0) {
			$buf = mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
			if ($buf !== false) {
				return $buf;
			}
		}
		if (function_exists('openssl_random_pseudo_bytes')) {
			$secure = true;
			$buf = openssl_random_pseudo_bytes($bytes, $secure);
			if ($buf !== false && $secure) {
				return $buf;
			}
		}
		if (@is_readable('/dev/urandom')) {
			$fp = fopen('/dev/urandom', 'rb');
			if ($fp !== false) {
				$streamset = stream_set_read_buffer($fp, 0);
				if ($streamset === 0) {
					$remaining = $bytes;
					$buf = false;
					while ($remaining > 0) {
						$read = fread($fp, $remaining); 
						if ($read === false) {
							$buf = false;
							break;
						}
						$remaining -= strlen($read);
						$buf .= $read;
					}
					if ($buf !== false) {
						return $buf;
					}
				}
			}
		}
		// we're out of luck here, this implementation isnt SECURE, use php >= 5.3 for safety
		$remaining = 2*$bytes;
		$hexkey = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
		while ($remaining > 0) {
			$remaining--;
			$buf .= $hexkey[rand(0,15)];
		}
		return $buf;
	}
}

class AuthContainer extends \core {
	private $user_id;
	private $rememberLen;

	public function __construct(Container $ci) {
		parent::__construct($ci);
		$this->user_id	= -1;
		$this->rememberLen = 3600*24; // remember you for a day
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
			$this->testRemember();
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

	public function getUserId() {
		return $this->user_id;
	}


	public function authenticated() {
		return isset($_SESSION['auth_id']);
	}

	private function genToken($length = 20) {
		return bin2hex(random_bytes($length));
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function testRemember() {
		// delete expired authentificator tokens
		$s = $this->db->prepare('delete from u$tokens where created+:len < current_timestamp');
		$s->bindParam(':len', $this->rememberLen, PDO::PARAM_INT);
		$s->execute();

		// if the cookies are set, authenticate them
		if (!isset($_COOKIE['remKey']) || !isset($_COOKIE['remVal']))
			return false;
		$s = $this->db->prepare('select passhash, user_id from u$tokens where keyname = :key');
		$s->bindParam(':key', $_COOKIE['remKey'], PDO::PARAM_STR);
		$s->execute();
		if( $r = $s->fetch() ) {
			// delete the token (new one will be created on authent success)
			$s = $this->db->prepare('delete from u$tokens where keyname = :key');
			$s->bindParam(':key', $_COOKIE['remKey'], PDO::PARAM_STR);
			$s->execute();

			if (password_verify($_COOKIE['remVal'], $r['passhash'])) {
				// authenticate the user
				$_SESSION['auth_id']	= $r['user_id'];
				$this->user_id		= $r['user_id'];
				// generate a new token for next re-authentication need
				$this->rememberMe($r['user_id']);
				return true;
			}
		}

		// rememberMe authetication failed: flush the invalid cookie
		setcookie('remKey', '', time() - 3600, '/');
		setcookie('remVal', '', time() - 3600, '/');
		return false;
	}

	private function rememberMe($id) {
		$key  = $this->genToken();
		$val  = $this->genToken();
		setcookie('remKey', $key, time()+$this->rememberLen, '/');
		setcookie('remVal', $val, time()+$this->rememberLen, '/');
		$hash = password_hash($val, PASSWORD_DEFAULT);
		$s = $this->db->prepare('insert into u$tokens(user_id,keyname, passhash) values(:id,:key,:hash)');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->bindParam(':key', $key, PDO::PARAM_STR);
		$s->bindParam(':hash', $hash, PDO::PARAM_STR);
		$s->execute();
	}

	private function remember($login) {
		$s = $this->db->prepare('select id from u$users where username = :login');
		$s->bindParam(':login', $login, PDO::PARAM_STR);
		$s->execute();
		if( !($r = $s->fetch()) )
			return;
		$this->rememberMe($r['id']);
	}

	public function setPassword($pass) {
		if (!$this->authenticated()) return false;
		$s = $this->db->prepare('update u$users set passhash = :pass where id = :id');
		$s->bindParam(':id', $this->user_id, PDO::PARAM_INT);
		$s->bindParam(':pass', password_hash($pass, PASSWORD_DEFAULT), PDO::PARAM_STR);
		$s->execute();
	}

	public function checkPassword($pass) {
		if (!$this->authenticated())
			return false;
		$s = $this->db->prepare('select passhash from users where id = :id');
		$s->bindParam(':id', $this->user_id, PDO::PARAM_INT);
		$s->execute();
		if( !($r = $s->fetch()) )
			return false;
		return password_verify($pass, $r['passhash']);
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
		setcookie('remKey', '', time() - 3600, '/');
		setcookie('remVal', '', time() - 3600, '/');
		if (isset($_COOKIE['remKey'])) {
			$s = $this->db->prepare('select passhash, user_id from u$tokens where keyname = :key');
			$s->bindParam(':key', $_COOKIE['remKey'], PDO::PARAM_STR);
			$s->execute();
		}
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
		if (! $this->authenticated()) {
			$x = $this->ci->get('trans');
			return $x('Guest');
		}
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
			throw new \Slim\Exception\MethodNotAllowedException($request, $response, array());
	}

	public function assertDomain($domain_id, $request, $response) {
		if (!$this->haveDomain($domain_id))
			throw new \Slim\Exception\MethodNotAllowedException($request, $response, array());
	}

	public function assertHost($host_id, $request, $response) {
		if (!$this->haveHost($host_id))
			throw new \Slim\Exception\MethodNotAllowedException($request, $response, array());
	}

	public function assertService($service_id, $request, $response) {
		if (!$this->haveService($service_id))
			throw new \Slim\Exception\MethodNotAllowedException($request, $response, array());
	}

	public function assertApp($app_id, $request, $response) {
		if (!$this->haveApp($app_id))
			throw new \Slim\Exception\MethodNotAllowedException($request, $response, array());
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function loginPage(Request $request, Response $response) {
 		return $this->view->render($response, 'login.twig', []);
	}

	public function loginPost(Request $request, Response $response) {
		$_ = $this->trans;
		if ($this->authenticate($request->getParam('username'), $request->getParam('password'))) {
			if ($request->getParam('remember')=='on')
				$this->remember($request->getParam('username'));
			$this->flash->addMessage('info', $_('Welcome').' '.$this->getUserName());
			if(isset($_SERVER['HTTP_REFERER']) ) {
				$t = explode('/', $_SERVER['HTTP_REFERER']);
				if ($t[2] == $_SERVER['SERVER_NAME'])
					return $response->withRedirect($_SERVER['HTTP_REFERER']);
			}
			return $response->withRedirect($this->router->pathFor('home'));
		} else {
			$this->flash->addMessage('error', $_('Failed to login.'));
			return $response->withRedirect($this->router->pathFor('auth.login'));
 		}
	}

	public function signout(Request $request, Response $response) {
		$_ = $this->trans;
		$this->disconnect();
		$this->flash->addMessage('info', $_('Succesfully signed out'));
 		return $response->withRedirect($this->router->pathFor('home'));
	}
}
