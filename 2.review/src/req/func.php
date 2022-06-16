<?php
function get_allowed_actions($arr)
{
	$av = [];
	$am = [];
	foreach ($arr as $k => $v) {
		$av += array_merge($av, array_values($v));
		foreach ($v as $item) {
			$am[$item] = $k;
		}
	}
	$ar["actions"] = $av;
	$ar["mods"] = $am;
	return ($ar);
}

function drawpage($tpl = 'users')
{
	readfile("templates/$tpl.html");
}

function pr($arr)
{
	echo "<pre>";
	print_r($arr);
	echo "</pre>";
}

/*Sheets parse section*/

function clear_fields_str($str)
{
	$str = ($str == "#") ? "num" : $str;
	$str = mb_strtolower($str, "UTF-8");
	$str = preg_replace('/[^a-z0-9]+/iu', ' ', $str);
	$str = trim($str);
	$str = preg_replace('/\s/', '_', $str);
	return $str;
}

function prepare_str_to_alter_table($action, $prefix, $arr, $spreadsheetId, $list_id, $cols_frontname)
{
	$last_row = $prefix . "_row";
	$k = 0;
	$e = 1;
	$db = new connecti();
	$cols = [];
	$fields = [];
	foreach ($arr as $v) {
		$fieldname = $db->esc($v["orig"]);
		$field = $v["clean"];
		if (!$field || $field == '') {
			$field = "empty_" . $e;
			$e++;
		}
		$colname = get_xls_colname($k + 1);
//		$field_name = strtolower($prefix . "_" . $field . "_" . $colname);
		$field_name = strtolower($prefix . "_" . $field);
		$field_type = get_field_type($field_name);
		if ($field_type == "date") {
			$fields[] = "ADD COLUMN `$field_name` INT(11) UNSIGNED NULL DEFAULT NULL COMMENT '$fieldname' AFTER `$last_row`";
		} else {
			$fields[] = "ADD COLUMN `$field_name` VARCHAR(255) NULL DEFAULT '' COMMENT '$fieldname' AFTER `$last_row`";
		}
		if ($action == "fill_books_cols" && !isset($cols_frontname[$field_name])) {
			$cols[] = array("xbctitle" => $fieldname, "xbccolname" => $colname, "xbcfieldname" => $field_name, "xbcfieldtype" => $field_type, "xlbid" => $spreadsheetId, "xbcsheetname" => $list_id);
		}
		$last_row = $field_name;
		$k++;
	}
	$data["cols"] = $cols;
	$data["fields"] = $fields;
	return $data;
}

function get_field_type($field)
{
	$field = mb_strtolower($field);
	$field_type = "text";
	$str_vals = explode("_", $field);
	if ($str_vals) {
		foreach ($str_vals as $val) {
			if ($val == "date" || $val == "time") {
				$field_type = "date";
				return $field_type;
			}
		}
	}
	return $field_type;
}

/*Sheets parse section end*/

function get_sys_object_content($type = '')
{
	$db = new connecti();
	$type = ($type) ? "AND otype = '" . $type . ";" : "";
	$list = $db->get_res("SELECT oid as id,ocontent as name,otype as `type` FROM sys_objects WHERE oactive = 1 $type ORDER BY IF(osorting,osorting,oid)");
	foreach ($list as $v) {
		$k = $v["type"];
		unset($v["type"]);
		$data[$k][] = $v;
	}
	return $data;
}

function get_obj_val($val, $mode, $arr)
{
	foreach ($arr as $v) {
		$field = ($mode == "id") ? "id" : "name";
		if ($v[$field] == $val) {
			$val = ($mode == "id") ? $v["name"] : (int)$v["id"];
			return $val;
		}
	}
}

function get_xls_colname($num)
{
	$num = abs(intval($num));
	if ($num < 1) {
		$num = 1;
	}
	$num1 = ($num - 1) % 26;
	$letter = chr(65 + $num1);
	$num2 = intval(($num - 1) / 26);
	if ($num2 > 0) {
		return get_xls_colname($num2) . $letter;
	} else {
		return $letter;
	}
}

function get_mimetypes()
{
	$mime["image/jpeg"] = ["jpeg", "jpg"];
	$mime["image/png"] = "png";
	$mime["image/gif"] = "gif";
	$mime["application/pdf"] = ["pdf", "ai"];
	$mime["image/vnd.adobe.photoshop"] = "psd";
	return $mime;
}

function get_mime_from_mimeinfo($ext, $mimeinfo)
{
	$mimes = get_mimetypes();
	foreach ($mimes as $mime_type => $mime_ext) {
		if ($mime_type == $mimeinfo) {
			if (!is_array($mime_ext)) {
				if ($mime_ext == $ext) {
					return $mime_ext;
				}
			} else {
				foreach ($mime_ext as $ext_val) {
					if ($ext_val == $ext) {
						return $ext_val;
					}
				}
			}
			return $mime_ext;
		}
	}
	return false;
}

function format_bytes($size)
{
	$base = log($size, 1024);
	$suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
	return round(pow(1024, $base - floor($base)), 2) . '' . $suffixes[floor($base)];
}

function format_return_data($arr, $cols_backname)
{
	foreach ($arr as $k => $v) {
		if (is_array($v)) {
			foreach ($v as $kk => $vv) {
				if (is_array($vv)) {
					$vv = format_return_data($vv, $cols_backname);
				} else {
					if (isset($cols_backname[$kk])) {
						$fieldtype = $cols_backname[$kk]["type"];
						if ($fieldtype == "json") {
							$vv = explode(";", $vv);
						}
						if ($fieldtype == "bool") {
							$vv = ($vv == 1) ? true : false;
						}
						if ($fieldtype == "int") {
							if ($kk != "late") {
								if ($vv !== null && !is_null($vv) && $vv != '' && $vv > 0) {
									$vv = (int)$vv;
								} else {
									$vv = null;
								}
							}
						}
                        if ($fieldtype == "num") {
                            $vv = (int)$vv;
                        }
						if ($fieldtype == "date") {
							if ($vv > 0) {
								$vv = $vv * 1000;
							} else {
								$vv = null;
							}
						}
						if ($fieldtype == "decimal") {
							$vv = (float)number_format($vv, 2);
						}

					}
					if ($kk == "id" || $kk == "enroute" || $kk == "shopify_stock" || $kk == "count") {
						$vv = (int)$vv;
					}
				}
				$arr[$k][$kk] = $vv;
			}
		} else {
			if (isset($cols_backname[$k])) {
				$fieldtype = $cols_backname[$k]["type"];
				if ($fieldtype == "json") {
					$v = explode(";", $v);
				}
				if ($fieldtype == "bool") {
					$v = ($v == 0) ? false : true;
				}
				if ($fieldtype == "int") {
					if ($k != "late") {
						if ($v !== null && !is_null($v) && $v != '' && $v > 0) {
							$v = (int)$v;
						} else {
							$v = null;
						}
					}
				}
                if ($fieldtype == "num") {
                    $v = (int) $v;
                }
				if ($fieldtype == "date") {
					if ($v > 0) {
						$v = $v * 1000;
					} else {
						$v = null;
					}
				}
				if ($fieldtype == "decimal") {
					$v = (float)number_format($v, 2);
				}
			}
			if ($k == "id" || $k == "enroute" || $k == "shopify_stock" || $k == "count") {
				$v = (int)$v;
			}
			$arr[$k] = $v;
		}
	}
	return $arr;
}


function set_history_file($action, $arr)
{
	$date = date("Ymd");
	$time = date("H:i:s");
	$log_folder = LOG_PATH . $date;
	if (!file_exists($log_folder)) {
		mkdir($log_folder, 754, true);
	}
	$log_file = $log_folder . "/" . $action . ".log";
	$data = (is_array($arr)) ? json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $arr;
	file_put_contents($log_file, $time . "\r\n" . $data . "\r\n", FILE_APPEND);
}

?>