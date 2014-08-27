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
* @package    QuickTeam
* @author     Philippe Vandenberghe <info@qt-cute.org>
* @copyright  2014 The PHP Group
* @version    3.0 build:20140608
*/

session_start();
require 'bin/qte_init.php';
include Translate(APP.'_adm.php');
include Translate('qtem_export.php');
if ( sUser::Role()!='A' ) die(Error(13));
if ( !defined('QTE_XML_CHAR') ) define('QTE_XML_CHAR','iso-8859-1');

function ToXml($str)
{
  $str = html_entity_decode($str,ENT_QUOTES);
  if ( strstr($str,'&') ) $str = QTencode($str,'-A -Q -L -R -&');
  $str = str_replace(chr(160),' ',$str); // required for xml
  $str = QTencode($str,'& L R'); // required for xml
  return $str;
}

// INITIALISE

$oVIP->selfurl = 'qtem_export_adm.php';
$oVIP->selfname = $L['export']['Admin'];
$oVIP->exiturl = $oVIP->selfurl;
$oVIP->exitname = $oVIP->selfname;
$strPageversion = $L['export']['Version'].' 3.0';

// --------
// SUBMITTED
// --------

if ( isset($_POST['submit']) )
{
  // read and check mandatory
  if ( empty($_POST['title']) ) $error='Filename '.Error(1);
  if ( substr($_POST['title'],-4,4)!='.xml' ) $_POST['title'] .= '.xml';
  if ( $_POST['section']=='-' ) $error='No data found';
  if ( $_POST['status']=='-' ) $error='No data found';

  // EXPORT COUNT
  if ( empty($error) )
  {
    $strWhere = 'WHERE u.id>0';
    if ( $_POST['section']!='all' ) $strWhere = ' INNER JOIN '.TABS2U.' s ON s.userid=u.id WHERE u.id>0 AND s.sid='.$_POST['section'];
    if ( $_POST['status']!='all' ) $strWhere .= ' AND u.status="'.$_POST['status'].'"';
    if ( $_POST['coppa']!='all' ) $strWhere .= ' AND u.children="'.$_POST['coppa'].'"';
    $oDB->Query('SELECT count(u.id) as countid FROM '.TABUSER.' u '.$strWhere );
    $row=$oDB->Getrow();
    if ( $row['countid']==0 ) $error='No data found';
  }

  // ------
  // EXPORT XML
  // ------

  if ( empty($error) )
  {
    $oDB2 = new cDB($qte_dbsystem,$qte_host,$qte_database,$qte_user,$qte_pwd);

    // start export

    if (!headers_sent())
    {
      header('Content-Type: text/xml; charset='.QTE_XML_CHAR);
      header('Content-Disposition: attachment; filename="'.$_POST['title'].'"');
    }

    echo '<?xml version="1.0" encoding="'.QTE_XML_CHAR.'"?>',PHP_EOL;
    echo '<quickteam version="2.5">',PHP_EOL;

    // export topic
    $oDB->Query('SELECT u.* FROM '.TABUSER.' u '.$strWhere );
    while($row=$oDB->Getrow())
    {
      $oItem = new cItem($row);
      $arrVars = get_object_vars($oItem);

      echo '<user id="',$oItem->id,'">',PHP_EOL;
      foreach($arrVars as $name=>$value)
      {
        echo "<$name>".ToXml($value)."</$name>";
      }

      echo '</user>',PHP_EOL;
    }

    // end export

    echo '</quickteam>';
    exit;
  }

}

// --------
// HTML START
// --------

$oHtml->scripts[] = '
<script type="text/javascript">
function ValidateForm(theForm)
{
  if (theForm.title.value.length==0) { alert("'.$L['Missing'].': File"); return false; }
  return null;
}
</script>
';

include APP.'_adm_inc_hd.php';

echo '<form method="post" action="',$oVIP->selfurl,'" onsubmit="return ValidateForm(this);">
<h2 class="subtitle">',$L['export']['Content'],'</h2>
<table class="t-data">
<tr>
<td class="headfirst"><label for="section">',$L['Section'],'</label></td>
<td>
<select id="section" name="section" size="1">
<option value="all">[ ',$L['All'],' ]</option>
',Sectionlist((isset($_POST['section']) ? (int)$_POST['section'] : -1)),'
</select>
</td>
</tr>
<tr>
<td class="headfirst"><label for="status">',$L['Status'],'</label></td>
<td><select id="status" name="status" size="1">
<option value="all">[ ',$L['All'],' ]</option>
';
$str = (isset($_POST['status']) ? $_POST['status'] : '-1');
foreach(memGet('sys_statuses') as $strKey=>$arrValue)
{
echo '<option value="',$strKey,'"'.(strval($strKey)==$str ? QSEL : '').'>',$strKey,' - ',$arrValue['statusname'],'</option>';
}
echo '</select>
</td>
</tr>
<tr>
<td class="headfirst"><label for="coppa">',$L['Coppa_status'],'</label></td>
<td><select id="coppa" name="coppa" size="1">
<option value="all">[ ',$L['All'],' ]</option>
';
$str = (isset($_POST['coppa']) ? $_POST['coppa'] : '-1');
foreach($L['Coppa_child'] as $intKey=>$strValue)
{
echo '<option value="',$intKey,'"'.(strval($intKey)==$str ? QSEL : '').'>',$intKey,' - ',$strValue,'</option>';
}
echo '</select>
</td>
</tr>
</table>';
echo '<h2 class="subtitle">',$L['Destination'],'</h2>
<table class="t-data">
<tr>
<td class="headfirst"><label for="title">',$L['export']['Filename'],'</label></td>
<td><input type="text" id="title" name="title" size="32" maxlength="32" value="export_'.date('Ymd').'.xml" /></td>
</tr>
</table>
<p style="margin:0 0 5px 0;text-align:center"><input type="submit" name="submit" value="',$L['Ok'],'" /></p>
</form>
';

// HTML END

include APP.'_adm_inc_ft.php';