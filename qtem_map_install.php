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
 * @category   Team
 * @package    QuickTeam
 * @author     Philippe Vandenberghe <info@qt-cute.org>
 * @copyright  2014 The PHP Group
 * @version    3.0 build:20140608
 */

session_start();
require_once 'bin/qte_init.php';
include Translate('qte_adm.php');
if ( sUser::Role()!='A' ) die($L['E_admin']);

// INITIALISE

$strVersion='v3.0';

$oVIP->selfurl = 'qtem_map_install.php';
$oVIP->selfname = 'Installation module MAP '.$strVersion;

$bStep1 = true;
$bStepZ = true;

// STEP 1

foreach(array('qtem_map_uninstall.php','qtem_map_adm.php','qtem_map/config.php','qtem_map_load.php','qtem_map_ini.php','qtem_map_lib.php') as $strFile)
{
if ( !file_exists($strFile) ) $error='Missing file: '.$strFile.'<br />This module cannot be used.';
}
if ( !empty($error) ) $bStep1 = false;

// STEP Z
if ( empty($error) )
{
  $oDB->Query('DELETE FROM '.TABSETTING.' WHERE param="module_map" OR param="m_map_gkey" OR param="m_map_gcenter" OR param="m_map_gzoom" OR param="m_map_gbuttons" OR param="m_map_gsymbol"');
  $oDB->Query('INSERT INTO '.TABSETTING.' (param,setting) VALUES ("module_map","Map")');
  $oDB->Query('INSERT INTO '.TABSETTING.' (param,setting) VALUES ("m_map_gkey","")');
  $oDB->Query('INSERT INTO '.TABSETTING.' (param,setting) VALUES ("m_map_gcenter","50.8468142558,4.35238838196")');
  $oDB->Query('INSERT INTO '.TABSETTING.' (param,setting) VALUES ("m_map_gzoom","10")');
  $oDB->Query('INSERT INTO '.TABSETTING.' (param,setting) VALUES ("m_map_gbuttons","P10100")');
  $oDB->Query('INSERT INTO '.TABSETTING.' (param,setting) VALUES ("m_map_gfind","Brussels, Belgium")');
  $oDB->Query('INSERT INTO '.TABSETTING.' (param,setting) VALUES ("m_map_gsymbol","0")');
  $_SESSION[QT]['module_map'] = 'Map';
  $_SESSION[QT]['m_map_gkey'] = '';
  $_SESSION[QT]['m_map_gcenter'] = '50.8468142558,4.35238838196';
  $_SESSION[QT]['m_map_gzoom'] = '10';
  $_SESSION[QT]['m_map_gbuttons'] = 'P10100';
  $_SESSION[QT]['m_map_gfind'] = 'Brussels, Belgium';
  $_SESSION[QT]['m_map_gsymbol'] = '0'; // Default symbol
}

// --------
// Html start
// --------
include 'qte_adm_p_header.php';

echo '<h2>Checking components</h2>';
if ( !$bStep1 )
{
  echo '<p class="error">',$error,'</p>';
  include 'qte_adm_p_footer.php';
  exit;
}
echo '<p>Ok</p>';
echo '<h2>Database settings</h2>';
if ( !$bStepZ )
{
  echo '<p class="error">',$error,'</p>';
  include 'qte_adm_p_footer.php';
  exit;
}
echo '<p>Ok</p>';
echo '<h2>Installation completed</h2>';

if ( $_SESSION[QT]['version']=='1.6' )
{
  echo '<p class="error">Your database version is 1.6. We recommand you to upgrade to 3.0 (use the installation wizard of QuickTeam).</p>';
}