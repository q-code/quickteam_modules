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
 * @category   Adressbook
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

$oVIP->selfurl = 'qtem_import_install.php';
$oVIP->selfname = 'Installation module IMPORT '.$strVersion;

$bStep1 = true;
$bStep2 = true;

// STEP 1

if ( empty($error) )
{
  $strFile = 'qtem_import_adm.php';
  if ( !file_exists($strFile) ) $error="Missing file: $strFile. Check installation instructions.<br />This module cannot be used.";
  if ( !empty($error) ) $bStep1 = false;
}

// STEP 2

if ( empty($error) )
{
  $oDB->Query('DELETE FROM '.TABSETTING.' WHERE param="module_import"');
  $oDB->Query('INSERT INTO '.TABSETTING.' (param,setting) VALUES ("module_import","Import")');
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
echo '<p>Ok</p>';
echo '<h2>Installation completed</h2>';

include 'qte_adm_p_footer.php';