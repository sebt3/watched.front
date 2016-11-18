<?php
use Interop\Container\ContainerInterface;

class CorePage {

	protected $ci;

	public function __construct(ContainerInterface $ci) { 
		$this->ci = $ci;
	}

	public function getHost($id) {
		$stmt = $this->ci->db->prepare("SELECT a.id, a.name as host from hosts a where a.id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch();
	}
	public function getRessource($id) {
		$stmt = $this->ci->db->prepare("SELECT id, name, type from ressources where id = :id");
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
		if ($late > 60*15 && $status == "ok") { // not collected since over 15 minuts
			return "text-yellow";
		}
		switch($status) {
		case "failed":
			return "text-red";
		default:
			return "text-green";
		}
	}

	public function getEventColor($name) {
		switch($name) {
		case "Ok":
			return "#00a65a";
		case "Failed":
		case "Critical":
			return "#dd4b39";
		case "Error":
			return "#ff851b";
		case "Warning":
			return "#f39c12";
		case "Notice":
			return "#0073b7";
		default:
			return "#3c8dbc";
		}
	}
	public function getEventTextColor($name) {
		switch($name) {
		case "Ok":
			return "text-green";
		case "Failed":
		case "Critical":
			return "text-red";
		case "Error":
			return "text-orange";
		case "Warning":
			return "text-yellow";
		case "Notice":
			return "text-blue";
		default:
			return "text-light-blue";
		}
	}
}

?>
