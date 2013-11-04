<?php

require_once "/usr/share/kolab-webadmin/lib/functions.php";
require_once "/usr/share/kolab-webadmin/lib/api/kolab_api_service_user_types.php";

function createDomain($domain_name)
{
global $auth;
        if ($auth->domain_info($domain_name) === false) {
                $attribs = array();
                $attribs['objectclass'] = array("top", "domainrelatedobject", "inetdomain");

                if ($auth->domain_add($domain_name, $attribs) === false) {
                        die("failed to add domain " .$domain_name);
                }
                return true;
        }

        return false;
}


$conf = Conf::get_instance();
$primary_domain = $conf->get('kolab', 'primary_domain');
$ldappassword = $conf->get('ldap', 'bind_pw');
$_SESSION['user'] = new User();
$valid = $_SESSION['user']->authenticate("cn=Directory Manager", $ldappassword, $primary_domain);

if ($valid === false) {
        die ("cannot authenticate user cn=Directory Manager");
}

$auth = Auth::get_instance();
echo "creating domain ".$conf->get('kolab', 'domainadmins_management_domain'). "\n";
createDomain($conf->get('kolab', 'domainadmins_management_domain'));

$user_types = new kolab_api_service_user_types(null);
$list = $user_types->user_types_list(null, null);
# copy the entry for kolab
if ($list['list'][1]['key'] != 'kolab') {
   echo ("failure: expected user type kolab at position 1, but found ".$list['list'][1]['key']). "\n";
   die();
}

$newType = $list['list'][1];
$newType['type'] = 'user';
$newType['key'] = 'domainadmin';
$newType['name'] = 'Domain Administrator';
$newType['description'] = 'A Kolab Domain Administrator';
$newType['attributes']['form_fields']['tbitskolabmaxaccounts'] = array('type' => 'text', 'optional' => 1);
$newType['attributes']['form_fields']['tbitskolaballowgroupware'] = array('type' => 'checkbox', 'optional' => 1);
$newType['attributes']['form_fields']['tbitskolaboverallquota'] = array('type' => 'text-quota', 'optional' => 1);
$newType['attributes']['form_fields']['tbitskolabdefaultquota'] = array('type' => 'text-quota', 'optional' => 1);

$service_type = new kolab_api_service_type();
if (false === $service_type->type_add(null, $newType)) {
    echo "failure: was not able to add new user type domainadmin\n";
    die();
}

echo "added new user type domainadmin\n";

?>
