<?php

/**
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @package    QuickTeam
 * @author     Philippe Vandenberghe <info@qt-cute.org>
 * @copyright  2014 The PHP Group
 * @version    3.0 build:20141222
 */

session_start();
require 'bin/qte_init.php';
include Translate(APP.'_adm.php');
if ( sUser::Role()!='A' ) die($L['E_admin']);

// INITIALISE

$strVersion='v3.0';

$oVIP->selfurl = 'qtem_map_uninstall.php';
$oVIP->selfname = 'Uninstall module MAPPING '.$strVersion;

// UNINSTALL

$oDB->Exec('DELETE FROM '.TABSETTING.' WHERE param="module_map" OR param="m_map_gkey" OR param="m_map_gcenter" OR param="m_map_gzoom" OR param="m_map_gbuttons" OR param="m_map_gfind" OR param="m_map_gsymbol"');

if ( isset($_SESSION[QT]['module_map']) ) unset($_SESSION[QT]['module_map']);

// --------
// Html start
// --------

include APP.'_adm_inc_hd.php';

echo '
<h2>Removing database settings</h2>
<p>Ok</p>
<h2>Uninstall completed</h2>
';

include APP.'_adm_inc_ft.php';