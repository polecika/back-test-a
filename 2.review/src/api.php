<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-uri");
include_once "config.php";
include_once "req/db.php";
include_once "req/func.php";
include_once "../core/func.php";

$actions_artworks = explode(",", "addPositionArtworks,loadArtworks,deletePositionArtworks,editPositionArtworks,editZoneSortsArtworks,saveArtworks");
$action_hv = explode(",", "HVCountry,HVFeedback,HVFeedbackType,HVOverallDates,HVProductsList,HVGroupingList");
$mod["HelicopterView"] = $action_hv;

$allowed_actions = get_allowed_actions($mod);
if (!empty($_GET) && isset($_GET["action"]) && in_array($_GET["action"], $allowed_actions["actions"])) {
	$getarr = $_GET;
	$action = $getarr["action"];
	$options["get"] = $getarr;
}
if (!empty($_POST)) {
	$postarr = $_POST;
}
if (!isset($postarr)) {
	$x = file_get_contents("php://input");
	$input_json = json_decode($x, TRUE);
	if ($input_json) {
		$postarr = $input_json;
	}
}
if (isset($postarr)) {
	$options["post"] = $postarr;
}
$user_id = 1; // disabled user checker 
if ($user_id > 0 && isset($action)) {
	$log_arr["post"] = (isset($postarr)) ? $postarr : [];
	$log_arr["get"] = (isset($getarr)) ? $getarr : [];
	set_history_file($action, $log_arr);

	if (method_exists($allowed_actions["mods"][$action], $action)) {
		$class = new $allowed_actions["mods"][$_GET["action"]];
		$class->get = $getarr;
		$class->user_id = $user_id;
		if (isset($postarr)) {
			$class->post = $postarr;
		}
		$return_data = $class->$action();
		$cols_backname = (method_exists($allowed_actions["mods"][$action], "get_format")) ? $class->get_format() : [];
	} else {
		$return_data["success"] = false;
		$return_data["message"] = "Method '$action' not exists!";
	}
}
if (isset($return_data)) {
	$return_data = format_return_data($return_data, $cols_backname);
}
if ($user_id == 0) {
	$return_data["success"] = false;
	$return_data["message"] = "Unauthorized user";
}
header("Content-type: application/json; charset=utf-8");
$flags = (isset($numeric)) ? JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK : JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
echo json_encode($return_data, $flags);
die();
