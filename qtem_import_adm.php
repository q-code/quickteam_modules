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
require_once 'bin/qte_init.php';
include Translate('qte_adm.php');
include Translate('qtem_import.php');
if ( sUser::Role()!='A' ) die(Error(13));

// FUNCTIONS

function startElement($parser, $strTag, $arrTagAttr)
{
  $strTag = strtolower($strTag);
  global $arrItems,$strValue;
  $strValue='';
  switch($strTag)
  {
  case 'user': $arrItems = array(); break; // reset for a new user
  }
}
function characterData($parser, $data)
{
  global $strValue;
  $strValue = trim($data);
}
function endElement($parser, $strTag)
{
  $strTag = strtolower($strTag);
  global $arrItems,$arrDbFields,$intItemInsertId,$strValue,$oDB,$intCounts;
  switch($strTag)
  {
  case 'user':
    $oItem = new cItem($arrItems);
    $oItem->id = $intItemInsertId; $intItemInsertId++;
    $oItem->status = $_SESSION['m_import_xml']['status'];
    if ( $_SESSION['m_import_xml']['dropdate'] ) $oItem->firstdate=date('Ymd'); //registration date
    // check fields names and values
    $arrVars = get_object_vars($oItem);
    if ( isset($arrVars['coppa']) ) { $arrVars['children']=$arrVars['coppa']; unset($arrVars['coppa']); } // change key for coppa
    // drop field not in db and add quotes
    foreach($arrVars as $name=>$values)
    {
      if ( !in_array($name,$arrDbFields) ) { unset($arrVars[$name]); continue; }
      if ( in_array($name,array('id','teamvalue1','teamvalue2','x','y','z')) )
      {
      $arrVars[$name]=(strlen($arrVars[$name])==0 || $arrVars[$name]==null ? 'NULL' : $values);
      }
      else
      {
      $arrVars[$name]='"'.(strlen($arrVars[$name])==0 ? '' : htmlspecialchars($values,ENT_QUOTES)).'"';
      }
    }
    //insert the user
    if ( $oDB->Query( 'INSERT INTO '.TABUSER.' ('.implode(',',array_keys($arrVars)).') VALUES ('.implode(',',$arrVars).')' ) )
    {
      $oDB->Query( 'INSERT INTO '.TABS2U.' (sid,userid,issuedate) VALUES ('.$_SESSION['m_import_xml']['dest'].','.$oItem->id.',"'.date('Ymd').'")' );
      $oItem->SaveKeywords($oItem->GetKeywords(GetFields('index_p')));
      $intCounts++;
    }
    else
    {
      echo ' - Cannot insert a new user with username ',$arrVars['username'],'<br />';
    }
    break;
  default:
    if ( trim($strValue)!='' ) $arrItems[$strTag]=$strValue;
    break;
  }
}

// INITIALISE

$intDest   = -1;
$strStatus = 'Z';
$bDropdate = false;
$intCounts = 0;
$arrDbFields=array('id','username','pwd','role','type','status','children','title','firstname','midname','lastname','alias','birthdate','nationality','sexe','picture','address','phones','emails','www','privacy','signature','descr','firstdate','teamid1','teamid2','teamrole1','teamrole2','teamdate1','teamdate2','teamvalue1','teamvalue2','teamflag1','teamflag2','x','y','z','ip','secret_q','secret_a');

$oVIP->selfurl = 'qtem_import_adm.php';
$oVIP->selfname = $L['import']['Admin'];
$oVIP->exiturl = $oVIP->selfurl;
$oVIP->exitname = $oVIP->selfname;
$strPageversion = $L['import']['Version'].' 3.0';

// --------
// SUBMITTED
// --------

if ( isset($_POST['ok']) )
{
  // check file

  $error = InvalidUpload($_FILES['title'],'xml');

  // check form value

  if ( empty($error) )
  {
    $intDest = (int)$_POST['section']; if ( $intDest<0 ) $intDest=0;
    $strStatus = $_POST['status'];
    if ( isset($_POST['dropdate']) ) $bDropdate = true;
    $_SESSION['m_import_xml']=array('dest'=>$intDest,'status'=>$strStatus,'dropdate'=>$bDropdate);
  }

  // import xml

  if ( empty($error) )
  {
    $arrItems = array();
    $strValue = '';
    $intItemInsertId = $oDB->Nextid(TABUSER);

    $xml_parser = xml_parser_create();
    xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
    xml_set_element_handler($xml_parser, 'startElement', 'endElement');
    xml_set_character_data_handler($xml_parser, 'characterData');
    if ( !($fp = fopen($_FILES['title']['tmp_name'],'r')) ) die('could not open XML input');
    while ($data = fread($fp,4096))
    {
      if ( !xml_parse($xml_parser, $data, feof($fp)) ) die(sprintf('XML error: %s at line %d', xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
    }
    xml_parser_free($xml_parser);
  }

  if ( empty($error) )
  {
    // Clean file

    unlink($_FILES['title']['tmp_name']);

    // Unregister global sys (will be recomputed on next page)

    UpdateSectionStats($intDest);
    Unset($_SESSION[QT]['sys_members']);
    Unset($_SESSION[QT]['sys_newuserid']);

    // End message (pause)
    if ( $intCounts==0 )
    {
      $oHtml->PageBox(NULL, 'No user inserted... Check the file and check that you don\'t have duplicate usernames.', 'admin',0);
    }
    else
    {
      $oHtml->PageBox(NULL,L('User',$intCounts).'<br />'.$L['import']['S_import'],'admin',0);
    }
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

$oHtml->scripts_end[] = '<script type="text/javascript">
// drag info
var doc = document.getElementById("draganddrop");
if (doc)
{
if (navigator.userAgent.toLowerCase().indexOf("firefox") != -1) doc.style.display="inline";
if (navigator.userAgent.toLowerCase().indexOf("opera") != -1) doc.style.display="inline";
if (navigator.userAgent.toLowerCase().indexOf("chrome") != -1) doc.style.display="inline";
}
</script>
';

include 'qte_adm_p_header.php';

if ( isset($_SESSION['m_import_xml']['dest']) )      $intDest   = $_SESSION['m_import_xml']['dest'];
if ( isset($_SESSION['m_import_xml']['status']) )    $strStatus = $_SESSION['m_import_xml']['status'];
if ( isset($_SESSION['m_import_xml']['dropdate']) )  $bDropdate = $_SESSION['m_import_xml']['dropdate'];

echo '<form method="post" action="',$oVIP->selfurl,'" enctype="multipart/form-data" onsubmit="return ValidateForm(this);">
<input type="hidden" name="maxsize" value="5242880" />
<h2 class="subtitle">',$L['import']['File'],'</h2>
<table class="t-data">
<tr>
<td class="headfirst" style="width:200px"><label for="title">',$L['import']['File'],'</label></td>
<td><input type="file" id="title" name="title" size="32" /> <span class="small" id="draganddrop" style="display:none">(',L('or_drop_file'),')</span></td>
</tr>
</table>
<h2 class="subtitle">',$L['Options'],'</h2>
<table class="t-data">
<tr>
<td class="headfirst" style="width:200px"><label for="section">',$L['import']['Destination'],'</label></td>
<td><select id="section" name="section">',Sectionlist(0),'</select> <a href="qte_adm_sections.php?add">',$L['Section_add'],'</a></td>
</tr>
<tr>
<td class="headfirst"><label for="status">',$L['Status'],'</label></td>
<td>
<select id="status" name="status">
';
foreach($oVIP->statuses as $strKey=>$arrStatus) echo '<option value="',$strKey,'"',($strStatus==$strKey ? QSEL : ''),'>',$strKey.' - '.$arrStatus['name'].'</option>';
echo '
</select></td>
</tr>
<tr>
<td class="headfirst">',$L['import']['Dropdate'],'</td>
<td><input type="checkbox" id="dropdate" name="dropdate"',($bDropdate ? QCHE : ''),'/> <label for="dropdate">',$L['import']['HDropdate'],'</label></td>
</tr>
</table>
<p style="margin:0 0 5px 0;text-align:center"><input type="submit" name="submit" value="',$L['Ok'],'" /></p>
</form>
';

// HTML END

include 'qte_adm_p_footer.php';