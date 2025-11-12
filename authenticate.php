<?php
//session_start();
function authenticate($user, $password) {
    if(empty($user) || empty($password)) return false;

    $ldap_host = "copeland.local";
    $ldap_dn = "DC=copeland, DC=local";
    $ldap_user_group = "CTAMRWR_CHI Quality Audit";
    $ldap_manager_group = "CTAMRWR_CHI Quality Audit CHG";
    $ldap_usr_dom = "copeland\\";

    $ldap = ldap_connect($ldap_host);
    ldap_set_option($ldap,LDAP_OPT_PROTOCOL_VERSION,3);
    ldap_set_option($ldap,LDAP_OPT_REFERRALS,0);

    if($bind = @ldap_bind($ldap, $ldap_usr_dom.$user, $password)) {
        $filter = "(sAMAccountName=".$user.")";
        $attr = array('displayname','mail','manager','memberof');
        $result = ldap_search($ldap, $ldap_dn, $filter, $attr) or exit("Unable to search LDAP server");
        $entries = ldap_get_entries($ldap, $result);
        ldap_unbind($ldap);
        $displayname=$entries[0]['displayname'][0];
        $mail = $entries['0']['mail']['0'];
        if(isset($entries['0']['manager']['0'])){
            $arrmgr = explode(",",$entries['0']['manager']['0']);
            $manager = str_replace('CN=','',$arrmgr[0]);
        }else{
            $manager = 'Not Defined';
        }

        $access = 0;
        if (isset($entries[0]['memberof'])) {
            foreach($entries[0]['memberof'] as $grps) {
                if(strpos($grps, $ldap_manager_group)) { $access = 2; break; }
                if(strpos($grps, $ldap_user_group)) $access = 1;
            }
        }

        if($access != 0) {
            $_SESSION['user'] = $user;
            $_SESSION['access'] = $access;
            $_SESSION['displayname'] = $displayname;
            $_SESSION['mail'] = $mail;
            $_SESSION['manager'] = $manager;
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}
?>
