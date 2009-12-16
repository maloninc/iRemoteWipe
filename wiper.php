<?php
/***********************************************
* File      :   wiper.php
* Project   :   
* Descr     :   This backend is ONLY for 
*               remote wipe
*
* Created   :   12.14.2009
* Author    :   Hiroyuki Nakamura (hiroyuki@maloninc.com)
************************************************/
include_once('diffbackend.php');

define('WIPER_DB', '/var/db/wiper/wiper.db');
define('LDAP_SERVER', 'your.server');
define('LDAP_DOMAIN', 'dc=your,dc=server');

class BackendWiper extends BackendDiff {
    var $_user;
    var $_devid;
    var $_protocolversion;

    function Logon($user, $domain, $pass) {
        debugLog('Wiper::Logon: ' . $user . '/'. $pass);
		if( $user == "" ) {
        	debugLog('Wiper::Logon: No user name.');
		}
		if( $pass == "" ) {
        	debugLog('Wiper::Logon: No password.');
		}
		$link_id = ldap_connect(LDAP_SERVER, 389);
		if(! $link_id){
        	debugLog('Wiper::Logon: Cannot connect LDAP server.');
		}
  		if(! ldap_set_option($link_id, LDAP_OPT_PROTOCOL_VERSION, 3)){
        	debugLog('Wiper::Logon: Failed to set v3 protocol.');
		}
		$dn = LDAP_DOMAIN;
		$filter = "(&(objectclass=person)(userPassword=*)(|(uid=$user)(cn=$user)) )";
		$attributes = array( 'cn', 'userpassword', 'uid');

		$search = ldap_search($link_id, $dn, $filter, $attributes);
  		$info = ldap_get_entries($link_id, $search);
		if($info['count'] == 0) {
        	debugLog("Wiper::Logon: No such ID: $user");
			return false;
		}
  		if(! ldap_bind($link_id, $info[0]['dn'], $pass)){
        	debugLog("Wiper::Logon: Invalid Password: $user");
			return false;
		}
        return true;
	}

    function Logoff() {
        debugLog('Wiper::Logoff');
        return true;
	}

    function Setup($user, $devid, $protocolversion) {
        debugLog('Wiper::Setup');
        $this->_user = $user;
        $this->_devid = $devid;
        $this->_protocolversion = $protocolversion;

        return true;
    }

    function SendMail($rfc822, $forward = false, $reply = false, $parent = false) {
        return false;
    }

    function GetWasteBasket() {
        return false;
    }

    function GetMessageList($folderid, $cutoffdate) {
        debugLog('Wiper::GetMessageList('.$folderid.')');
        $messages = array();
        return $messages;
    }

    function GetFolderList() {
        debugLog('Wiper::GetFolderList()');
        $contacts = array();
        $folder = $this->StatFolder("root");
        $contacts[] = $folder;

        return $contacts;
    }

    function GetFolder($id) {
        debugLog('Wiper::GetFolder('.$id.')');
        if($id == "root") {
            $folder = new SyncFolder();
            $folder->serverid = $id;
            $folder->parentid = "0";
            $folder->displayname = "Dummy";
            $folder->type = SYNC_FOLDER_TYPE_CONTACT;

            return $folder;
        } else return false;
    }

    function StatFolder($id) {
        debugLog('Wiper::StatFolder('.$id.')');
        $folder = $this->GetFolder($id);

        $stat = array();
        $stat["id"] = $id;
        $stat["parent"] = $folder->parentid;
        $stat["mod"] = $folder->displayname;

        return $stat;
    }

    function GetAttachmentData($attname) {
        return false;
    }

    function GetMessage($folderid, $id, $truncsize) {
        debugLog('Wiper::GetMessage('.$folderid.', '.$id.', ..)');
        return false;;
    }

    function SetReadFlag($folderid, $id, $flags) {
        return false;
    }

    function ChangeMessage($folderid, $id, $message) {
        debugLog('Wiper::ChangeMessage('.$folderid.', '.$id.', ..)');
    }

    function MoveMessage($folderid, $id, $newfolderid) {
        debugLog('Wiper::MoveMessage('.$folderid.', '.$id.', ..)');
        return false;
    }

    // -----------------------------------

    function escape($data){
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = $this->escape($val);
            }
            return $data;
        }
        $data = str_replace("\r\n", "\n", $data);
        $data = str_replace("\r", "\n", $data);
        $data = str_replace(array('\\', ';', ',', "\n"), array('\\\\', '\\;', '\\,', '\\n'), $data);
        return u2wi($data);
    }

    function unescape($data){
        $data = str_replace(array('\\\\', '\\;', '\\,', '\\n','\\N'),array('\\', ';', ',', "\n", "\n"),$data);
        return $data;
    }

    function getDeviceRWStatus($user, $pass, $devid) {
        debugLog('Wiper::getDeviceRWStatus');
		$dbh = new PDO('sqlite:'.WIPER_DB, null, null);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		try{
			$sth = $dbh->prepare('select rwstatus from wipe_table where user = ? and devid = ?');
			$sth->execute(array($user, $devid));
			$result = $sth->fetch();
			if($result == null) {
				$sth = $dbh->prepare('insert into wipe_table values(?, ?, ?, 0)');
				$sth->execute(array($user, $devid, SYNC_PROVISION_RWSTATUS_NA));
        		return false;
			}
		}catch(PDOException $e){
			debugLog("    ".$e->getMessage());
		}
		$status = $result[0];
		$dbh = null;
		return $status;
	}

    function setDeviceRWStatus($user, $pass, $devid, $status) {
        debugLog('Wiper::setDeviceRWStatus: '.$status);
		$dbh = new PDO('sqlite:'.WIPER_DB, null, null);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		try{
			$sth = $dbh->prepare('update  wipe_table set status = ? where user = ? and devid = ?');
			$sth->execute(array($status, $user, $devid));
		}catch(PDOException $e){
			debugLog("    ".$e->getMessage());
		}
		$dbh = null;
		return true;;
	}

    function getPolicyKey($user, $pass, $devid) {
        debugLog('Wiper::getPolicyKey');
		$dbh = new PDO('sqlite:'.WIPER_DB, null, null);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		try{
			$sth = $dbh->prepare('select policykey from wipe_table where user = ? and devid = ?');
			$sth->execute(array($user, $devid));
			$result = $sth->fetch();
			if($result == null) {
				$sth = $dbh->prepare('insert into wipe_table values(?, ?, ?, 1)');
				$sth->execute(array($user, $devid, SYNC_PROVISION_RWSTATUS_NA));
        		return false;
			}
		}catch(PDOException $e){
			debugLog("    ".$e->getMessage());
		}
		$key = $result[0];
		$dbh = null;
        return $key;
    }

    function setPolicyKey($policykey, $devid) {
        debugLog('Wiper::setPolicyKey: ' . $policykey . ' ' . $devid);
		$dbh = new PDO('sqlite:'.WIPER_DB, null, null);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		try{
			$sth = $dbh->prepare('update  wipe_table set policykey = ? where devid = ?');
			$sth->execute(array($policykey, $devid));
		}catch(PDOException $e){
			debugLog("    ".$e->getMessage());
		}
		$dbh = null;
        return true;
    }

};
?>
