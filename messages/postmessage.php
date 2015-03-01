<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
require_once($rootpath."includes/inc_mailfunctions.php");
require_once($rootpath."includes/inc_userinfo.php");

session_start();
$s_id = $_SESSION["id"];
$s_name = $_SESSION["name"];
$s_letscode = $_SESSION["letscode"];
$s_accountrole = $_SESSION["accountrole"];

if(!isset($s_id)){
        exit;
}

$mode = $_POST["mode"];
$validity = $_POST["validity"];
//echo $validity;
$vtime = count_validity($validity);
$posted_list = array();
$posted_list["validity"] = $_POST["validity"];
$posted_list["vtime"] = $vtime;
$posted_list["content"] = pg_escape_string($_POST["content"]);
$posted_list["description"] = pg_escape_string($_POST["description"]);
$posted_list["msg_type"] = $_POST["msg_type"];
$posted_list["id_user"] = $_POST["id_user"];
$posted_list["id_category"] = $_POST["id_category"];
$posted_list["amount"] = $_POST["amount"];
$posted_list["units"] = pg_escape_string($_POST["units"]);
//$posted_list["announce"] = $_POST["announce"];
$msgid = $_POST["id"];
//$posted_list["id"] = $_POST["id"];
$uuid = uniqid();
$posted_list["uuid"] = $uuid;

$errors= validate_input($posted_list,$mode);
if (empty($errors)){
	#Build status message
	$statusmessage = "";
	//$fullurl = "http://" . $baseurl ."messages";
	
	switch ($mode){
	        case "new":
			$result = insert_msg($posted_list);
                        if($result == TRUE) {
                                echo "<font color='green'><strong>OK</font> - Vraag/Aanbod is opgeslagen";
								setstatus("Vraag/Aanbod is opgeslagen",0);
                                echo "<script type='text/javascript'>self.close();</script>";
                                
                        } else {
                                echo "<font color='red'><strong>Fout bij het opslaan van je V/A";
                                setstatus("Vraag/Aanbod is NIET opgeslagen", 1);
                        }
                        break;
		case "edit":
			if($posted_list["id_user"] == $s_id || $s_accountrole == 'admin'){
				$result = update_msg($msgid, $posted_list);
				if($result == TRUE) {
					echo "<font color='green'><strong>OK</font> - Vraag/Aanbod $msgid aangepast";
					setstatus("Vraag/Aanbod $msgid aangepast",0);
					echo "<script type='text/javascript'>self.close();</script>";
				} else {
					echo "<font color='red'><strong>Fout bij de update van V/A $msgid";
					setstatus("Fout bij de update van V/A $msgid", 1);
				}
			} else {
				echo "<font color='red'><strong>Geen toegang tot deze V/A $msgid";
			}
			break;
	}
} else {
	echo "<font color='red'><strong>Fout: ";
        foreach($errors as $key => $value){
		echo $value;
		echo " | ";
	}
	echo "</strong></font>";
}


///////////////// FUNCTIONS //////////////////
function validate_input($posted_list,$mode){
        global $db;
        $error_list = array();
        if (empty($posted_list["content"]) || (trim($posted_list["content"]) == "")){
                $error_list["content"] = "Wat is niet ingevuld";
        }
	if(!($posted_list["vtime"] > date("Y-m-d H:i:s")) && $mode == "new" ){
		$error_list["validity"] = "Geldigheid is niet correct of niet ingevuld";
	}
	if (empty($posted_list["id_category"])){
		$error_list["id_category"] = "Categorie is leeg";
        }

        return $error_list;
}

function count_validity($validity){
        $valtime = time() + ($validity*30*24*60*60);

        $vtime =  date("Y-m-d H:i:s",$valtime);
        return $vtime;
}


function update_msg($id, $posted_list){
    global $db;
    if(!empty($posted_list["validity"])){
    	$posted_list["validity"] = $posted_list["vtime"];
    } else {
	unset($posted_list["validity"]);
    }
    $posted_list["mdate"] = date("Y-m-d H:i:s");
    if(empty($posted_list["amount"])){
	$query = "UPDATE messages SET MDATE='" .$posted_list["mdate"] ."', ID_CATEGORY=" .$posted_list["id_category"] .", ID_USER=" .$posted_list["id_user"] . ", CONTENT='" .$posted_list["content"] . "', \"Description\"='" .$posted_list["description"] . "', AMOUNT=NULL, UNITS='" .$posted_list["units"] ."', MSG_TYPE=" .$posted_list["msg_type"] .", UUID='" .$posted_list["uuid"] ."' WHERE id=" .$id;	
    } else {
	$query = "UPDATE messages SET MDATE='" .$posted_list["mdate"] ."', ID_CATEGORY=" .$posted_list["id_category"] .", ID_USER=" .$posted_list["id_user"] . ", CONTENT='" .$posted_list["content"] . "', \"Description\"='" .$posted_list["description"] . "', AMOUNT=" .$posted_list["amount"] . ", UNITS='" .$posted_list["units"] ."', MSG_TYPE=" .$posted_list["msg_type"] .", UUID='" .$posted_list["uuid"] ."' WHERE id=" .$id;
    }
    //print $query;
    $result = $db->Execute($query);
    return $result;
}

function insert_msg($posted_list){
        global $db;
	$posted_list["cdate"] = date("Y-m-d H:i:s");
        $posted_list["validity"] = $posted_list["vtime"];
	if(empty($posted_list["amount"]) || $posted_list["amount"] ==0 ){
		$query = "INSERT INTO messages ( CDATE, VALIDITY, ID_CATEGORY, ID_USER, CONTENT, \"Description\", UNITS, MSG_TYPE, UUID ) VALUES ('" .$posted_list["cdate"] ."', '" .$posted_list["validity"] ."', " .$posted_list["id_category"] .", " .$posted_list["id_user"] . ", '" .$posted_list["content"] . "', '" .$posted_list["description"] ."', '" .$posted_list["units"] ."', " .$posted_list["msg_type"] .", '" .$posted_list["uuid"] ."')";
	} else {
		$query = "INSERT INTO messages ( CDATE, VALIDITY, ID_CATEGORY, ID_USER, CONTENT, \"Description\", AMOUNT, UNITS, MSG_TYPE, UUID ) VALUES ('" .$posted_list["cdate"] ."', '" .$posted_list["validity"] ."', " .$posted_list["id_category"] .", " .$posted_list["id_user"] . ", '" .$posted_list["content"] . "', '" .$posted_list["description"] ."', " .$posted_list["amount"] .", '" .$posted_list["units"] ."', " .$posted_list["msg_type"] .", '" .$posted_list["uuid"] ."')";
	}
	//print $query;
        $result = $db->Execute($query);
	return $result;
}



?>

