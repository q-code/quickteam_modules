<?php

/**
* PHP versions 5
*
* LICENSE: This source file is subject to version 3.0 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license. If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* @package    LDAP
* @author     Philippe Vandenberghe <info@qt-cute.org>
* @copyright  2014 The PHP Group
* @version    2.0 build:20141222
*/

session_start();
require 'bin/qte_init.php';
include Translate(APP.'_adm.php');
if ( sUser::Role()!='A' ) die($L['E_admin']);

// INITIALISE

$strVersion='v1.0';
$oVIP->selfurl = 'qtem_ldap_uninstall.php';
$oVIP->selfname = 'Uninstallation module LDAP '.$strVersion;

// UNINSTALL

$oDB->Exec('DELETE FROM '.TABSETTING.' WHERE param="module_ldap" OR param="m_ldap:login" OR param="m_ldap"');
if ( isset($_SESSION[QT]['m_ldap']) ) unset($_SESSION[QT]['m_ldap']);

// --------
// Html start
// --------
include APP.'_adm_inc_hd.php';

echo '
<h1>',$oVIP->selfname,'</h1>
<h2>Removing database settings</h2>
<p>Ok</p>
<h2>Uninstall completed</h2>
';

include APP.'_adm_inc_ft.php';