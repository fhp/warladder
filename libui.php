<?php

function getHtmlID()
{
	static $id = 1;
	return "l" . ($id++);
}

function parseArrayField($values, $keys)
{
	$output = array();
	for($index = 0; ; $index++) {
		$found = false;
		$nonEmpty = false;
		$row = array();
		foreach($keys as $key) {
			$cellKey = "$key-$index";
			if(isset($values[$cellKey]) && $values[$cellKey] !== null) {
				$row[$key] = $values[$cellKey];
				if($row[$key] != "") {
					$nonEmpty = true;
				}
				$found = true;
			} else {
				$row[$key] = null;
			}
		}
		if(!$found) {
			return $output;
		}
		if($nonEmpty) {
			$row[""] = $index;
			$output[] = $row;
		}
	}
}

function acceptFile($name)
{
	$scriptFilename = substr(realpath($_SERVER["SCRIPT_FILENAME"]), strlen(dirname(realpath(__FILE__))));
	
	if(!isset($_SESSION["files"])) {
		$_SESSION["files"] = array();
	}
	if(!isset($_SESSION["files"][$scriptFilename])) {
		$_SESSION["files"][$scriptFilename] = array();
	}
	
	$id = post("$name-id");
	if($id !== null && !isset($_SESSION["files"][$scriptFilename][$id])) {
		$id = null;
	}
	if(isset($_FILES[$name]) && $_FILES[$name]["error"] == UPLOAD_ERR_OK) {
		if($id !== null) {
			unset($_SESSION["files"][$scriptFilename][$id]);
		}
		$id = 1;
		foreach($_SESSION["files"][$scriptFilename] as $key=>$value) {
			$id = $key + 1;
		}
		$data = file_get_contents($_FILES[$name]["tmp_name"]);
		$_SESSION["files"][$scriptFilename][$id] = array(
			"id"=>$id,
			"name"=>$_FILES[$name]["name"],
			"type"=>$_FILES[$name]["type"],
			"size"=>$_FILES[$name]["size"],
			"data"=>$data
		);
		$_POST["$name-id"] = $id;
	}
	
	if(get("download") == $name) {
		$id = get("download-id");
		if(!isset($_SESSION["files"][$scriptFilename][$id])) {
			error404();
		}
		
		$type = $_SESSION["files"][$scriptFilename][$id]["type"];
		$size = $_SESSION["files"][$scriptFilename][$id]["size"];
		$name = addslashes($_SESSION["files"][$scriptFilename][$id]["name"]);
		
		header("Content-Type: $type");
		header("Content-Length: $size");
		header("Content-Disposition: attachment; filename=\"$name\"");
		echo $_SESSION["files"][$scriptFilename][$id]["data"];
		die();
	}
}

function parseFile($values, $name)
{
	$scriptFilename = substr(realpath($_SERVER["SCRIPT_FILENAME"]), strlen(dirname(realpath(__FILE__))));
	
	if(!isset($_SESSION["files"])) {
		$_SESSION["files"] = array();
	}
	if(!isset($_SESSION["files"][$scriptFilename])) {
		$_SESSION["files"][$scriptFilename] = array();
	}
	
	$id = post("$name-id");
	if(isset($_SESSION["files"][$scriptFilename][$id])) {
		return $_SESSION["files"][$scriptFilename][$id];
	}
	return null;
}

function dropdown($list)
{
	$result = array();
	foreach($list as $value=>$label) {
		$result[] = array("value"=>$value, "label"=>$label);
	}
	return $result;
}

function checkPassword($check, $field) {
	if(post("confirm") === null) {
		$check(post("$field-1") == post("$field-2"), "The entered passwords do not match.");
		$check(post("$field-1") != "", "Please enter a password.");
		return null;
	} else {
		$password = decryptPassword(post("encrypted-$field"));
		$check($password !== null, "Internal error: invalid encrypted password. Please enter password again.");
		return $password;
	}
}

function getField($key/*, sources...*/)
{
	$sources = func_get_args();
	array_shift($sources);
	foreach($sources as $source) {
		if(isset($source[$key])) {
			return $source[$key];
		}
	}
	return null;
}

function forwardFields($target /*, sources... */)
{
	$sources = func_get_args(); // $target is included
	foreach(array("fieldclass", "arrayfields", "cellclass", "celltype", "rowclass", "rowid") as $field) {
		foreach($sources as $source) {
			if(isset($source[$field])) {
				$target[$field] = $source[$field];
				break;
			}
		}
	}
	return $target;
}

function renderCell($cell, $values, $readOnly)
{
	$output = array();
	if(isset($cell["cellclass"])) {
		$output["cellclass"] = $cell["cellclass"];
	}
	$output["content"] = "";
	
	if(isset($cell["name"])) {
		if(isset($cell["arrayfields"]) && $cell["arrayfields"] !== null) {
			$baseName = substr($cell["name"], 0, strrpos($cell["name"], "-"));
			$index = substr($cell["name"], strrpos($cell["name"], "-") + 1);
			$arrayData = parseArrayField($values, $cell["arrayfields"]);
			if($index < count($arrayData)) {
				$oldName = "$baseName-{$arrayData[$index][""]}";
			} else {
				$oldName = null;
			}
			$name = $cell["name"];
		} else {
			$oldName = isset($cell["confirm"]) ? "{$cell["name"]}-{$cell["confirm"]}" : $cell["name"];
			$name = $readOnly ? $cell["name"] : $oldName;
		}
		$value = isset($values[$oldName]) ? $values[$oldName] : null;
		$valueHtml = $value === null ? null : htmlentities($value);
	}
	$fieldclass = isset($cell["fieldclass"]) ? $cell["fieldclass"] : "form-control";
	
	if($cell["type"] == "html") {
		$output["content"] = "";
		if(isset($cell["url"]) && $cell["url"] !== null) {
			$urlHtml = htmlentities($cell["url"]);
			$output["content"] .= "<a href=\"$urlHtml\">";
		}
		$output["content"] .= $cell["html"];
		if(isset($cell["url"]) && $cell["url"] !== null) {
			$output["content"] .= "</a>";
		}
	} else if($cell["type"] == "label") {
		$output["content"] .= "<label for=\"{$cell["id"]}\">{$cell["label"]}</label>";
	} else if($cell["type"] == "text" || $cell["type"] == "date") {
		$output["content"] = "<input type=\"text\" name=\"$name\"";
		if($cell["type"] == "date") {
			if($fieldclass !== null) {
				$fieldclass .= " datepicker";
			} else {
				$fieldclass = "datepicker";
			}
		}
		if($fieldclass !== null) {
			$output["content"] .= " class=\"$fieldclass\"";
		}
		if($readOnly) {
			$output["content"] .= " readonly=\"readonly\"";
		}
		if($value !== null) {
			$output["content"] .= " value=\"$valueHtml\"";
		}
		$output["content"] .= " />";
	} else if($cell["type"] == "textarea") {
		$output["content"] = "<textarea name=\"$name\"";
		if($fieldclass !== null) {
			$output["content"] .= " class=\"$fieldclass\"";
		}
		if($readOnly) {
			$output["content"] .= " readonly=\"readonly\"";
		}
		$output["content"] .= ">";
		if($value !== null) {
			$output["content"] .= $valueHtml;
		}
		$output["content"] .= "</textarea>";
	} else if($cell["type"] == "password") {
		if($readOnly) {
			$output["content"] = "<input type=\"password\" readonly=\"readonly\"";
			if($fieldclass !== null) {
				$output["content"] .= " class=\"$fieldclass\"";
			}
			if($value !== null) {
				$masked = str_repeat("*", strlen($value));
				$output["content"] .= " value=\"$masked\"";
			}
			$output["content"] .= " />";
			if($value !== null) {
				$encryptedPassword = encryptPassword($value);
				$output["content"] .= "<input type=\"hidden\" name=\"encrypted-$name\" value=\"$encryptedPassword\" />";
			}
		} else {
			$output["content"] = "<input type=\"password\" name=\"$name\"";
			if($fieldclass !== null) {
				$output["content"] .= " class=\"$fieldclass\"";
			}
			$output["content"] .= " />";
		}
	} else if($cell["type"] == "radioentry") {
		$output["content"] = "<label><input type=\"radio\" value=\"{$cell["value"]}\"";
		if($value == $cell["value"]) {
			$output["content"] .= " checked=\"checked\"";
		}
		if($readOnly) {
			$output["content"] .= " disabled=\"disabled\"";
		} else {
			$output["content"] .= " name=\"$name\"";
		}
		if($fieldclass !== null) {
			$output["content"] .= " class=\"$fieldclass\"";
		}
		$output["content"] .= " /> {$cell["label"]}</label>";
		if($readOnly && ($value == $cell["value"])) {
			$output["content"] .= "<input type=\"hidden\" name=\"$name\" value=\"$valueHtml\" />";
		}
	} else if($cell["type"] == "bareradioentry") {
		$output["content"] = "<input type=\"radio\" value=\"{$cell["value"]}\" id=\"{$cell["id"]}\"";
		if($value == $cell["value"]) {
			$output["content"] .= " checked=\"checked\"";
		}
		if($readOnly) {
			$output["content"] .= " disabled=\"disabled\"";
		} else {
			$output["content"] .= " name=\"$name\"";
		}
		if($fieldclass !== null) {
			$output["content"] .= " class=\"$fieldclass\"";
		}
		$output["content"] .= " />";
		if($readOnly && ($value == $cell["value"])) {
			$output["content"] .= "<input type=\"hidden\" name=\"$name\" value=\"$valueHtml\" />";
		}
	} else if($cell["type"] == "checkbox") {
		$output["content"] = "";
		if(isset($cell["label"]) && $cell["label"] !== null) {
			$output["content"] .= "<label>";
		}
		$output["content"] .= "<input type=\"checkbox\"";
		if(!isset($cell["value"]) || $cell["value"] === null) {
			$valueHtml = "1";
			$checked = ($value !== null);
		} else {
			$valueHtml = htmlentities($cell["value"]);
			$checked = ($value == $cell["value"]);
		}
		$output["content"] .= " value=\"$valueHtml\"";
		if($checked) {
			$output["content"] .= " checked=\"checked\"";
		}
		if($fieldclass !== null) {
			$output["content"] .= " class=\"$fieldclass\"";
		}
		if($readOnly) {
			$output["content"] .= " disabled=\"disabled\"";
		} else {
			$output["content"] .= " name=\"$name\"";
		}
		if(isset($cell["id"]) && $cell["id"] !== null) {
			$output["content"] .= " id=\"{$cell["id"]}\"";
		}
		$output["content"] .= " />";
		if(isset($cell["label"]) && $cell["label"] !== null) {
			$output["content"] .= " {$cell["label"]}</label>";
		}
		if($readOnly && $checked) {
			$output["content"] .= "<input type=\"hidden\" name=\"$name\" value=\"$valueHtml\" />";
		}
	} else if($cell["type"] == "dropdown") {
		$output["content"] = "<select name=\"$name\"";
		if($fieldclass !== null) {
			$output["content"] .= " class=\"$fieldclass\"";
		}
		if($readOnly) {
			$output["content"] .= " readonly=\"readonly\"";
		}
		$output["content"] .= ">\n";
		foreach($cell["options"] as $option) {
			if($readOnly && $option["value"] != $value) {
				continue;
			}
			$valueHtml = htmlentities($option["value"]);
			$output["content"] .= "<option value=\"$valueHtml\"";
			if($option["value"] == $value) {
				$output["content"] .= " selected=\"selected\"";
			}
			if(isset($option["disabled"]) && $option["disabled"]) {
				$output["content"] .= " disabled=\"disabled\"";
			}
			$output["content"] .= ">{$option["label"]}</option>\n";
		}
		$output["content"] .= "</select>";
	} else if($cell["type"] == "file") {
		$file = parseFile($values, $name);
		if($file !== null) {
			$postUrl = $cell["postUrl"];
			if(strpos($postUrl, "?") === false) {
				$postUrl .= "?";
			} else {
				$postUrl .= "&";
			}
			$postUrl .= "download=$name&download-id={$file["id"]}";
			$postUrlHtml = htmlentities($postUrl);
			
			$filenameHtml = htmlentities($file["name"]);
			$readVersion = "<a href=\"$postUrlHtml\">$filenameHtml</a>";
			$readVersion .= "<input type=\"hidden\" name=\"$name-id\" value=\"{$file["id"]}\" />";
		} else {
			$readVersion = "No file selected.";
		}
		
		if($readOnly) {
			$output["content"] = $readVersion;
		} else {
			$output["content"] = "";
			if($file !== null) {
				$output["content"] .= $readVersion . "<br />";
			}
			$output["content"] .= "<input type=\"file\" name=\"$name\"";
			if(isset($cell["accept"]) && $cell["accept"] !== null) {
				$output["content"] .= " accept=\"{$cell["accept"]}\"";
			}
			if($fieldclass !== null) {
				$output["content"] .= " class=\"$fieldclass\"";
			}
			$output["content"] .= " />";
		}
	} else if($cell["type"] == "submit") {
		$output["content"] = "<input type=\"submit\" name=\"$name\" value=\"{$cell["label"]}\" class=\"btn btn-default\" />";
	} else {
		die("Invalid field type {$cell["type"]}");
	}
	
	if(isset($cell["header"]) && $cell["header"] !== null) {
		$output["content"] = $cell["header"] . $output["content"];
	}
	if(isset($cell["footer"]) && $cell["footer"] !== null) {
		$output["content"] .= $cell["footer"];
	}
	
	return forwardFields($output, $cell);
}

function renderRow($row, $values, $readOnly)
{
	$output = array();
	$output["cells"] = array();
	if($row["type"] == "colspan") {
		foreach($row["columns"] as $column) {
			$cell = renderCell(forwardFields($column, $row), $values, $readOnly);
			if(isset($column["fill"]) && $column["fill"]) {
				$cell["width"] = "stretch";
			} else {
				$cell["width"] = null;
			}
			$output["cells"][] = $cell;
		}
	} else if($row["type"] == "splitradioentry") {
		$c = $row;
		$c["type"] = "bareradioentry";
		$cell = renderCell($c, $values, $readOnly);
		$cell["width"] = "left-merge";
		$output["cells"][] = $cell;
		
		$c["type"] = "label";
		$cell = renderCell($c, $values, $readOnly);
		$cell["width"] = "stretch";
		$output["cells"][] = $cell;
	} else {
		$cell = renderCell($row, $values, $readOnly);
		$cell["width"] = "stretch";
		$output["cells"] = array($cell);
	}
	return forwardFields($output, $row);
}

function renderRowspan($rowspan, $values, $readOnly)
{
	if($rowspan["type"] == "rowspan") {
		$rows = array();
		foreach($rowspan["rows"] as $row) {
			$rows[] = renderRow(forwardFields($row, $rowspan), $values, $readOnly);
		}
		return $rows;
	} else if($rowspan["type"] == "subformchooser") {
		$rows = array();
		foreach($rowspan["subforms"] as $subform) {
			$rows[] = renderRow(array("type"=>"splitradioentry", "name"=>$rowspan["name"], "value"=>$subform["value"], "label"=>$subform["label"], "id"=>$subform["id"]), $values, $readOnly);
			foreach($subform["subform"] as $subfield) {
				if(!isset($subfield["title"])) {
					$f = forwardFields($subfield, $subform, $rowspan);
					if(!isset($f["rowclass"]) || $f["rowclass"] === null) {
						$f["rowclass"] = "if-selected-{$subform["id"]}";
					} else {
						$f["rowclass"] .= " if-selected-{$subform["id"]}";
					}
					
					$row = renderRow($f, $values, $readOnly);
					$row["cells"] = array_merge(array(array("width"=>"left-merge", "content"=>"")), $row["cells"]);
					$rows[] = $row;
				}
			}
		}
		return $rows;
	} else if($rowspan["type"] == "radio") {
		$rows = array();
		foreach($rowspan["options"] as $option) {
			$row = array("type"=>"radioentry", "name"=>$rowspan["name"], "value"=>$option["value"], "label"=>$option["label"]);
			$rows[] = renderRow(forwardFields($row, $option, $rowspan), $values, $readOnly);
		}
		return $rows;
	} else {
		return array(renderRow($rowspan, $values, $readOnly));
	}
}

function renderTable($fields, $values, $readOnly, $submitCaption = null, $submitName = null, $caption = null)
{
	foreach($fields as $key=>$value) {
		if($value["type"] == "subformchooser") {
			foreach($value["subforms"] as $i=>$subform) {
				if(!isset($subform["id"])) {
					$fields[$key]["subforms"][$i]["id"] = getHtmlID();
				}
			}
		}
	}
	
	$rowspans = array();
	foreach($fields as $field) {
		$f = $field;
		$rowspan = array();
		if(!isset($field["title"]) || $field["title"] === null) {
			$rowspan["title"] = null;
		} else {
			$rowspan["title"] = $field["title"];
		}
		
		if(isset($field["titleclass"])) {
			$rowspan["titleclass"] = $field["titleclass"];
		}
		
		if(isset($field["confirmtitle"])) {
			$f["confirm"] = 1;
		}
		
		$rowspan["rows"] = renderRowspan($f, $values, $readOnly);
		$rowspans[] = $rowspan;
		
		if(isset($field["confirmtitle"]) && !$readOnly) {
			$f = $field;
			$f["confirm"] = 2;
			$rowspan = array();
			$rowspan["title"] = $field["confirmtitle"];
			$rowspan["rows"] = renderRowspan($f, $values, $readOnly);
			$rowspans[] = $rowspan;
		}
		
		if($field["type"] == "subformchooser") {
			foreach($field["subforms"] as $subform) {
				foreach($subform["subform"] as $subfield) {
					if(isset($subfield["title"])) {
						$rowspan = array();
						$rowspan["title"] = $subfield["title"];
						if(isset($field["titleclass"])) {
							$rowspan["titleclass"] = $field["titleclass"];
						}
						if(isset($subfield["rowclass"]) && $subfield["rowclass"] !== null) {
							$subfield["rowclass"] .= " if-selected-{$subform["id"]}";
						} else {
							$subfield["rowclass"] = "if-selected-{$subform["id"]}";
						}
						
						$rowspan["rows"] = renderRowspan($subfield, $values, $readOnly);
						$rowspans[] = $rowspan;
					}
				}
			}
		}
	}
	
	$leftMergeUsed = false;
	$maxLeftFields = 0;
	$maxRightFields = 0;
	foreach($rowspans as $rowspan) {
		foreach($rowspan["rows"] as $row) {
			$leftFields = 0;
			$rightFields = 0;
			if(isset($rowspan["title"]) && $rowspan["title"] !== null) {
				$leftFields++;
			}
			
			$stretchSeen = false;
			foreach($row["cells"] as $cell) {
				if(isset($cell["width"]) && $cell["width"] == "left-merge") {
					$leftMergeUsed = true;
				} else if(isset($cell["width"]) && $cell["width"] == "stretch") {
					$stretchSeen = true;
				} else if($stretchSeen) {
					$rightFields++;
				} else {
					$leftFields++;
				}
			}
			if($leftFields > $maxLeftFields) {
				$maxLeftFields = $leftFields;
			}
			if($rightFields > $maxRightFields) {
				$maxRightFields = $rightFields;
			}
		}
	}
	if($leftMergeUsed) {
		$maxLeftFields += 1;
	}
	
	$output = "<table>\n";
	if($caption !== null) {
		$output .= "<caption>$caption</caption>\n";
	}
	if($leftMergeUsed && $maxLeftFields > 2) {
		$output .= "<col />";
		$output .= "<col style=\"width: 0.0001%\"/>";
		$output .= "<col style=\"width: 0.1%\" />";
		if($maxLeftFields > 3) {
			$output .= "<col span=\"" . ($maxLeftFields - 3) . "\" />";
		}
	} else {
		if($maxLeftFields == 1) {
			$output .= "<col />";
		} else if($maxLeftFields > 0) {
			$output .= "<col span=\"$maxLeftFields\" />";
		}
	}
	$output .= "<col style=\"width: 100%;\" />";
	if($maxRightFields == 1) {
		$output .= "<col />";
	} else if($maxRightFields > 0) {
		$output .= "<col span=\"$maxRightFields\" />";
	}
	
	foreach($rowspans as $rowspan) {
		$hasTitle = (isset($rowspan["title"]) && $rowspan["title"] !== null);
		$first = true;
		
		foreach($rowspan["rows"] as $row) {
			$output .= "<tr";
			if(isset($row["rowclass"]) && $row["rowclass"] !== null) {
				$output .= " class=\"{$row["rowclass"]}\"";
			}
			if(isset($row["rowid"]) && $row["rowid"] !== null) {
				$output .= " id=\"{$row["rowid"]}\"";
			}
			$output .= ">";
			
			if($first) {
				if($hasTitle) {
					$output .= "<th";
					if(count($rowspan["rows"]) != 1) {
						$rows = count($rowspan["rows"]);
						$output .= " rowspan=\"$rows\"";
					}
					if(isset($rowspan["titleclass"]) && $rowspan["titleclass"] !== null) {
						$output .= " class=\"{$rowspan["titleclass"]}\"";
					}
					$output .= ">";
					if($rowspan["title"] != "") {
						$output .= $rowspan["title"] . ":";
					}
					$output .= "</th>";
				}
				$first = false;
			}
			$output .= "\n";
			
			$stretchWidth = $maxLeftFields + $maxRightFields + 1;
			if($hasTitle) {
				$stretchWidth -= 1;
			}
			if($leftMergeUsed && !(isset($row["cells"][0]["width"]) && $row["cells"][0]["width"] == "left-merge")) {
				$stretchWidth -= 1;
			}
			$stretchWidth -= count($row["cells"]);
			$stretchWidth += 1;
			
			$firstCell = true;
			foreach($row["cells"] as $cell) {
				if(isset($cell["celltype"]) && $cell["celltype"] == "th") {
					$celltype = "th";
				} else {
					$celltype = "td";
				}
				
				$output .= "<$celltype";
				if(isset($cell["width"]) && $cell["width"] == "left-merge") {
					$width = 1;
				} else {
					if(isset($cell["width"]) && $cell["width"] == "stretch") {
						$width = $stretchWidth;
						if(isset($cell["cellclass"]) && $cell["cellclass"] !== null) {
							$cell["cellclass"] .= " stretch";
						} else {
							$cell["cellclass"] = "stretch";
						}
					} else {
						$width = 1;
					}
					if($firstCell && $leftMergeUsed) {
						$width++;
					}
				}
				if($width != 1) {
					$output .= " colspan=\"$width\"";
				}
				if(isset($cell["cellclass"]) && $cell["cellclass"] !== null) {
					$output .= " class=\"{$cell["cellclass"]}\"";
				}
				$output .= ">";
				$firstCell = false;
				
				$output .= $cell["content"];
				
				$output .= "</$celltype>\n";
			}
			
			$output .= "</tr>\n";
		}
	}
	
	if($submitCaption !== null) {
		$stretchWidth = $maxLeftFields + $maxRightFields + 1;
		$nameHtml = ($submitName === null ? "" : "name=\"$submitName\" ");
		$output .= "<tr class=\"submit\"><td colspan=\"$stretchWidth\"><input type=\"submit\" value=\"$submitCaption\" $nameHtml class=\"btn btn-default\" /></td></tr>\n";
	}
	
	$output .= "</table>\n";
	
	return $output;
}

function postfixFieldNames($field, $postfix)
{
	if($field["type"] == "rowspan") {
		$rows = array();
		foreach($field["rows"] as $row) {
			$rows[] = postfixFieldNames($row, $postfix);
		}
		$field["rows"] = $rows;
	} else if($field["type"] == "colspan") {
		$columns = array();
		foreach($field["columns"] as $column) {
			$columns[] = postfixFieldNames($column, $postfix);
		}
		$field["columns"] = $columns;
	} else if(isset($field["name"]) && $field["name"] !== null) {
		$field["name"] .= $postfix;
	}
	return $field;
}

function fieldNames($field)
{
	$names = array();
	if($field["type"] == "rowspan") {
		foreach($field["rows"] as $row) {
			$names = array_merge($names, fieldNames($row));
		}
	} else if($field["type"] == "colspan") {
		foreach($field["columns"] as $column) {
			$names = array_merge($names, fieldNames($column));
		}
	} else if(isset($field["name"]) && $field["name"] !== null) {
		$names[] = $field["name"];
	}
	return $names;
}

function addPostUrl($field, $postUrl)
{
	$found = false;
	if($field["type"] == "rowspan") {
		$rows = array();
		foreach($field["rows"] as $row) {
			list($rowField, $rowFound) = addPostUrl($row, $postUrl);
			$rows[] = $rowField;
			$found = $found || $rowField;
		}
		$field["rows"] = $rows;
	} else if($field["type"] == "colspan") {
		$columns = array();
		foreach($field["columns"] as $column) {
			list($columnField, $columnFound) = addPostUrl($column, $postUrl);
			$columns[] = $columnField;
			$found = $found || $columnField;
		}
		$field["columns"] = $columns;
	} else if($field["type"] == "subformchooser") {
		$subforms = array();
		foreach($field["subforms"] as $subform) {
			$fields = array();
			foreach($subform["subform"] as $subField) {
				list($fieldField, $fieldFound) = addPostUrl($subField, $postUrl);
				$fields[] = $fieldField;
				$found = $found || $fieldFound;
				
			}
			$subform["subform"] = $fields;
			$subforms[] = $subform;
		}
		$field["subforms"] = $subforms;
	} else if($field["type"] == "file") {
		$field["postUrl"] = $postUrl;
		$found = true;
	}
	return array($field, $found);
}

function operationForm($postUrl, $error, $title, $submitCaption, $fields, $values, $messages = null, $properties = null)
{
	if($values === null) {
		$values = array();
	}
	
	if($messages === null) {
		$messages = array();
	}
	
	if($properties === null) {
		$properties = array();
	}
	
	$readOnly = ($error === null);
	$stub = ($error == "STUB");
	
	$mainTable = array();
	$extraTables = array();
	$subformTables = array();
	$hiddenFields = ($error === null ? "<input type=\"hidden\" name=\"confirm\" value=\"1\" />\n" : "");
	foreach($fields as $value) {
		if(!is_array($value)) {
			continue;
		} else if($value["type"] == "typechooser") {
			if($stub && (!isset($value["nostub"]) || $value["nostub"] === null || $value["nostub"])) {
				continue;
			}
			$subformTables = $value["options"];
		} else if($value["type"] == "table") {
			if($stub && (!isset($value["nostub"]) || $value["nostub"] === null || $value["nostub"])) {
				continue;
			}
			$extraTables[] = $value;
		} else {
			$mainTable[] = $value;
		}
	}
	
	$selectedTable = null;
	foreach($subformTables as $table) {
		if(isset($values[$table["name"]])) {
			$selectedTable = $table["name"];
			break;
		}
	}
	
	$tables = array();
	$tables[] = array("subform"=>$mainTable);
	foreach($subformTables as $table) {
		if($table["name"] !== $selectedTable) {
			continue;
		}
		$tables[] = $table;
	}
	foreach($subformTables as $table) {
		if($table["name"] === $selectedTable || $readOnly) {
			continue;
		}
		$tables[] = $table;
	}
	
	$hasFiles = false;
	$filteredTables = array();
	foreach($tables as $table) {
		$fields = array();
		foreach($table["subform"] as $field) {
			if(!is_array($field)) {
				continue;
			}
			
			if($stub) {
				if(isset($field["nostub"]) && $field["nostub"]) {
					continue;
				}
			}
			
			if($field["type"] == "hidden") {
				$valueHtml = htmlentities($field["value"]);
				$hiddenFields .= "<input type=\"hidden\" name=\"{$field["name"]}\" value=\"$valueHtml\" />\n";
				continue;
			}
			
			list($field, $filesFound) = addPostUrl($field, $postUrl);
			$hasFiles = $hasFiles || $filesFound;
			
			if($field["type"] == "array") {
				$names = fieldNames($field["field"]);
				$content = parseArrayField($values, $names);
				$usedFields = count($content);
				$emptyFields = ($readOnly ? 0 : ($usedFields == 0 ? 2 : 1));
				$deleteFields = ($readOnly ? 0 : 10);
				$id = getHtmlID();
				for($i = 0; $i < $usedFields + $emptyFields + $deleteFields; $i++) {
					$f = postfixFieldNames($field["field"], "-$i");
					if($i < $usedFields) {
						$class = "";
					} else if($i == $usedFields + $emptyFields - 1) {
						$f["rowid"] = $id;
						$class = "repeatFieldMaster repeatFieldChild-$id";
					} else if($i < $usedFields + $emptyFields) {
						$class = "repeatFieldChild-$id";
					} else {
						$class = "repeatFieldRemove";
					}
					if(isset($f["rowclass"]) && $f["rowclass"] !== null) {
						$f["rowclass"] .= " $class";
					} else {
						$f["rowclass"] = $class;
					}
					$f["arrayfields"] = $names;
					$fields[] = $f;
				}
				
				break;
			}
			
			$fields[] = $field;
		}
		$filteredTable = $table;
		$filteredTable["subform"] = $fields;
		$filteredTables[] = $filteredTable;
	}
	$mainTable_ = array_shift($filteredTables);
	$mainTable = $mainTable_["subform"];
	$subformTables = $filteredTables;
	
	$output = "<div class=\"operation\">\n";
	$output .= "<h2>";
	$output .= $title;
	if(isset($properties["deleteLink"]) && $properties["deleteLink"] !== null) {
		$output .= "<a href=\"" . $properties["deleteLink"] . "\" class=\"deleteItemLink\"><i class=\"fa fa-trash\"></i></a>\n";
	}
	if(isset($properties["editLink"]) && $properties["editLink"] !== null) {
		$output .= "<a href=\"" . $properties["editLink"] . "\" class=\"editItemLink\"><i class=\"fa fa-pencil\"></i></a>\n";
	}
	$output .= "</h2>\n";
	
	if($error === null) {
		$output .= "<p class=\"alert alert-warning\">Are you sure?</p>\n";
		if(isset($messages["confirmdelete"])) {
			$output .= "<p class=\"confirmdelete\">{$messages["confirmdelete"]}</p>\n";
		}
		if(isset($messages["confirmbilling"])) {
			$output .= "<p class=\"billing\">{$messages["confirmbilling"]}</p>\n";
		}
	} else if($error != "" && $error != "STUB") {
		$output .= "<p class=\"error\">" . $error . "</p>\n";
	}
	if(isset($messages["custom"])) {
		$output .= $messages["custom"];
	}
	
	if($postUrl !== null) {
		$output .= "<form action=\"$postUrl\" method=\"post\"";
		if($hasFiles) {
			$output .= " enctype=\"multipart/form-data\"";
		}
		$output .= ">\n";
	}
	$output .= $hiddenFields;
	
	$output .= renderTable($mainTable, $values, $readOnly, count($subformTables) == 0 ? $submitCaption : null);
	
	foreach($extraTables as $table) {
		$output .= "<div";
		if(isset($table["tableclass"]) && $table["tableclass"] !== null) {
			$output .= " class=\"{$table["tableclass"]}\"";
		}
		$output .= ">\n";
		if(isset($table["title"]) && $table["title"] !== null) {
			$output .= "<h3>{$table["title"]}</h3>\n";
		}
		if(isset($table["summary"]) && $table["summary"] !== null) {
			$output .= "<p>{$table["summary"]}</p>\n";
		}
		$output .= renderTable($table["subform"], $values, $readOnly, null, null, isset($table["caption"]) ? $table["caption"] : null);
		$output .= "</div>\n";
	}
	
	foreach($subformTables as $table) {
		if($table["name"] === $selectedTable) {
			$selectedClass = " selected";
			$selectedTitle = "Currently selected: ";
		} else {
			$selectedClass = "";
			$selectedTitle = "";
		}
		
		$output .= "<div class=\"operation$selectedClass\">\n";
		$output .= "<h3>{$selectedTitle}{$table["title"]}</h3>\n";
		if(isset($table["summary"]) && $table["summary"] !== null) {
			$output .= "<p>{$table["summary"]}</p>\n";
		}
		$output .= renderTable($table["subform"], $values, $readOnly, $table["submitcaption"], $table["name"]);
		$output .= "</div>\n";
	}
	
	if($postUrl !== null) {
		$output .= "</form>\n";
	}
	$output .= "</div>\n";
	
	return $output;
}

function listTableCell($cell, $type)
{
	if(!is_array($cell)) {
		$cell = array("text"=>$cell);
	}
	
	$output = "";
	
	if(isset($cell["celltype"]) && $cell["celltype"] !== null) {
		$celltype = $cell["celltype"];
	} else {
		$celltype = $type;
	}
	
	$output .= "<$celltype";
	if(isset($cell["class"]) && $cell["class"] !== null) {
		$output .= " class=\"{$cell["class"]}\"";
	}
	if(isset($cell["id"]) && $cell["id"] !== null) {
		$output .= " id=\"{$cell["id"]}\"";
	}
	if(isset($cell["colspan"]) && $cell["colspan"] !== null) {
		$output .= " colspan=\"{$cell["colspan"]}\"";
	}
	$output .= ">";
	
	if(isset($cell["url"]) && $cell["url"] !== null) {
		$urlHtml = htmlentities($cell["url"]);
		$output .= "<a href=\"$urlHtml\">";
	}
	
	if(isset($cell["html"]) && $cell["html"] !== null) {
		$output .= $cell["html"];
	} else if(isset($cell["text"]) && $cell["text"] !== null) {
		$output .= nl2br(htmlentities($cell["text"]));
	}
	
	if(isset($cell["url"]) && $cell["url"] !== null) {
		$output .= "</a>";
	}
	
	$output .= "</$celltype>\n";
	return $output;
}

function listTable($header, $rows, $caption, $showIfEmpty, $properties = null)
{
	if($properties === null) {
		$properties = array();
	} else if(!is_array($properties)) {
		$properties = array("divclass"=>$properties);
	}
	
	if (is_array($showIfEmpty) && count($rows) == 0) {
		$caption = $showIfEmpty[0];
		$showIfEmpty = $showIfEmpty[1];
	}
	if ($showIfEmpty === false && count($rows) == 0) {
		return "";
	}
	
	$output = "";
	$output .= "<div";
	if(isset($properties["divclass"]) && $properties["divclass"] !== null) {
		$output .= " class=\"{$properties["divclass"]}\"";
	}
	if(isset($properties["divid"]) && $properties["divid"] !== null) {
		$output .= " id=\"{$properties["divid"]}\"";
	}
	$output .= ">\n";
	if(isset($properties["formtarget"]) && $properties["formtarget"] !== null) {
		$targetHtml = htmlentities($properties["formtarget"]);
		$output .= "<form action=\"$targetHtml\" method=\"post\">\n";
	}
	$output .= "<table";
	if(isset($properties["tableclass"]) && $properties["tableclass"] !== null) {
		$output .= " class=\"{$properties["divclass"]}\"";
	}
	if(isset($properties["tableid"]) && $properties["tableid"] !== null) {
		$output .= " id=\"{$properties["divid"]}\"";
	}
	$output .= ">\n";
	
	if($caption !== null) {
		$output .= "<caption>";
		$output .= $caption;
		if(isset($properties["addNewLink"]) && $properties["addNewLink"] !== null) {
			$output .= "<a href=\"" . $properties["addNewLink"] . "\" class=\"addNewLink\"><i class=\"fa fa-plus-circle\"></i></a>\n";
		}
		$output .= "</caption>\n";
	}
	
	if (is_string($showIfEmpty) && count($rows) == 0) {
		return $output . "<tr><td>$showIfEmpty</td></tr>\n</table>\n</div>\n";
	}
	
	$output .= "<thead>\n";
	$output .= "<tr";
	if(isset($properties["headerclass"]) && $properties["headerclass"] !== null) {
		$output .= " class=\"{$properties["headerclass"]}\"";
	}
	if(isset($properties["headerid"]) && $properties["headerid"] !== null) {
		$output .= " id=\"{$properties["headerid"]}\"";
	}
	$output .= ">\n";
	foreach($header as $cell) {
		$output .= listTableCell($cell, "th");
	}
	$output .= "</tr>\n";
	$output .= "</thead>\n";
	
	$output .= "<tbody>\n";
	foreach($rows as $row) {
		if(!isset($row["cells"])) {
			$row = array("cells"=>$row);
		}
		
		$output .= "<tr";
		if(isset($row["class"]) && $row["class"] !== null) {
			$output .= " class=\"{$row["class"]}\"";
		}
		if(isset($row["id"]) && $row["id"] !== null) {
			$output .= " id=\"{$row["id"]}\"";
		}
		$output .= ">\n";
		
		foreach($row["cells"] as $cell) {
			$output .= listTableCell($cell, "td");
		}
		
		$output .= "</tr>\n";
	}
	$output .= "</tbody>\n";
	
	if(isset($properties["footer"]) && $properties["footer"] !== null) {
		$output .= "<tfoot>\n";
		$output .= "<tr";
		if(isset($properties["footerclass"]) && $properties["footerclass"] !== null) {
			$output .= " class=\"{$properties["footerclass"]}\"";
		}
		if(isset($properties["footerid"]) && $properties["footerid"] !== null) {
			$output .= " id=\"{$properties["footerid"]}\"";
		}
		$output .= ">\n";
		foreach($properties["footer"] as $cell) {
			$output .= listTableCell($cell, "td");
		}
		$output .= "</tr>\n";
		$output .= "</foot>\n";
	}
	
	$output .= "</table>\n";
	if(isset($properties["formtarget"]) && $properties["formtarget"] !== null) {
		$output .= "</form>\n";
	}
	$output .= "</div>\n";
	return $output;
}

function summaryTable($title, $values, $properties = null)
{
	if($properties === null) {
		$properties = array();
	}
	$fields = array();
	foreach($values as $key=>$value) {
		if(!is_array($value)) {
			$value = array("text"=>$value);
		}
		if(isset($value["html"]) && $value["html"] !== null) {
			$html = $value["html"];
		} else if(isset($value["text"]) && $value["text"] !== null) {
			$html = nl2br(htmlentities($value["text"]));
		} else {
			$html = "";
		}
		if(isset($value["url"]) && $value["url"] !== null) {
			$urlHtml = htmlentities($value["url"]);
			$html = "<a href=\"$urlHtml\">$html</a>";
		}
		$fields[] = array("title"=>$key, "type"=>"html", "html"=>$html);
	}
	return operationForm(null, "", $title, null, $fields, null, null, $properties);
}

?>