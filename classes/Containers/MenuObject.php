<?php
use Interop\Container\ContainerInterface as Container;

class MenuObject extends core {
	//menu view interfaces
	public $domains;
	public $apps;
	public $breadcrumb;
	public $adminlinks;
	public $isAdmin;
	public $isAuth;
	public $username;

	public function activateHost($hostname) {
		foreach($this->domains as $did => $domain) {
			foreach($domain['hosts'] as $hid => $host) {
				if ($host['name'] == $hostname) {
					$this->domains[$did]['hosts'][$hid]['active'] = true;
					$this->domains[$did]['active'] = true;
				}
			}
		}
	}

	public function activateApps($hostname) {
		foreach($this->apps as $did => $domain) {
			foreach($domain['apps'] as $hid => $host) {
				if ($host['name'] == $hostname) {
					$this->apps[$did]['app'][$hid]['active'] = true;
					$this->apps[$did]['active'] = true;
				}
			}
		}
	}

	public function activateAdmin($hostname) {
		foreach($this->adminlinks as $did => $domain) {
			foreach($domain['pages'] as $hid => $host) {
				if ($host['name'] == $hostname) {
					$this->adminlinks[$did]['pages'][$hid]['active'] = true;
					$this->adminlinks[$did]['active'] = true;
				}
			}
		}
	}

	public function __construct(Container $ci) {
		parent::__construct($ci);
		$this->apps		= array();
		$this->domains		= array();
		$this->adminlinks	= array();
		$this->isAuth		= $ci->auth->authenticated();
		$this->isAdmin		= $ci->auth->isAdmin();
		$this->username		= $ci->auth->getUserName();

		// populate the admin page list
		if ($this->isAdmin) {
			array_push($this->adminlinks, array('name' => 'Users management', 'icon' => 'fa fa-users', 'pages' => array(
				array('name' => 'Users', 'icon' => 'icon ion-person', 'page' => 'admin.users.list'),
				array('name' => 'Teams', 'icon' => 'icon ion-person-stalker', 'page' => 'admin.teams.list'),
				array('name' => 'Roles', 'icon' => 'icon ion-ios-body-outline', 'page' => 'admin.roles.list')
				
			)));
			array_push($this->adminlinks, array('name' => 'Parc management', 'icon' => 'fa fa-globe', 'pages' => array(
				array('name' => 'Agents', 'icon' => 'fa fa-id-badge', 'page' => 'admin.agents.list'),
				array('name' => 'Domains', 'icon' => 'fa fa-object-group', 'page' => 'admin.domains.list'),
				array('name' => 'Apps', 'icon' => 'fa fa-rocket', 'page' => 'admin.apps.list'),
				array('name' => 'Groups', 'icon' => 'fa fa-briefcase', 'page' => 'admin.groups.list'),
				array('name' => 'Clean', 'icon' => 'fa fa-eraser', 'page' => 'admin.clean')
				
			)));
		}

		// populate the domain/hosts array
		$stmt = $this->db->query('SELECT id, name from c$domains');
		while($row = $stmt->fetch()) {
			$res = array();
			$stm2 = $this->db->prepare('SELECT name, id from h$hosts where domain_id = :id');
			$stm2->bindParam(':id', $row['id'], PDO::PARAM_INT);
			$stm2->execute();
			while($l = $stm2->fetch()) {
				array_push($res, $l);
			}
			if(count($res)>0)
				array_push($this->domains, array('name' => $row['name'], 'hosts' => $res));
		}
		$lst=array();
		$stm3 = $this->db->query('SELECT name, id from h$hosts where domain_id is null');
		while($r = $stm3->fetch()) {
			array_push($lst, $r);
		}
		if(count($lst)>0)
			array_unshift($this->domains, array('name' => 'unset', 'hosts' => $lst));
	}
}


?>
