<?php
use Interop\Container\ContainerInterface;

class CorePage extends core {

	public function getHost($id) {
		$stmt = $this->db->prepare('SELECT a.id, a.name as host from h$hosts a where a.id = :id');
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch();
	}
	public function getRessource($id) {
		$stmt = $this->db->prepare('SELECT id, name, data_type from c$ressources where id = :id');
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch();
	}

	public function formatTimestamp($ts) {
		$date = new DateTime();
		$date->setTimestamp(round($ts/1000));
		return $date->format('Y-m-d H:i:s');

	}
	
	public function getStatusColor($status, $late) {
		if ($late > 60*15 && $status == 'ok') { // not collected since over 15 minuts
			return 'text-yellow';
		}
		switch($status) {
		case 'missing':
			return 'text-yellow';
		case 'failed':
			return 'text-red';
		default:
			return 'text-green';
		}
	}
	public function getPctColor($pct) {
		if ($pct>90)	  return 'red';
		else if ($pct>70) return 'yellow';
		else if ($pct<20) return 'blue';
		return 'green';
	}

	public function getDomainTextColor($name) {
		switch($name) {
		case 'Production':
			return 'text-red';
		case 'Qualification':
			return 'text-orange';
		case 'Testing':
			return 'text-yellow';
		case 'Developpement':
			return 'text-blue';
		case 'unset':
			return 'text-green';
		default:
			return 'text-light-blue';
		}
	}
	public function getDomainColor($name) {
		switch($name) {
		case 'Production':
			return '#dd4b39';
		case 'Qualification':
			return '#ff851b';
		case 'Testing':
			return '#f39c12';
		case 'Developpement':
			return '#0073b7';
		case 'unset':
			return '#00a65a';
		default:
			return "#3c8dbc";
		}
	}
	public function getEventColor($name) {
		switch($name) {
		case 'ok':
		case 'Ok':
			return '#00a65a';
		case 'failed':
		case 'Failed':
		case 'Critical':
			return '#dd4b39';
		case 'missing':
		case 'Missing':
		case 'Error':
			return '#ff851b';
		case 'Warning':
			return '#f39c12';
		case 'Notice':
			return '#0073b7';
		default:
			return '#3c8dbc';
		}
	}
	public function getEventTextColor($name) {
		switch($name) {
		case 'Ok':
			return 'text-green';
		case 'Failed':
		case 'failed':
		case 'Critical':
			return 'text-red';
		case 'Missing':
		case 'Error':
			return 'text-orange';
		case 'Warning':
			return 'text-yellow';
		case 'Notice':
			return 'text-blue';
		default:
			return 'text-light-blue';
		}
	}
}

?>
