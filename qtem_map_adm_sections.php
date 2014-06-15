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
* @copyright  20013 The PHP Group
* @version    3.0 build:20140608
*/

session_start();
require_once 'bin/qte_init.php';
include Translate('qte_adm.php');
if ( sUser::Role()!='A' ) die(Error(13));

include Translate('qtem_map.php');
include Translate('qtem_map_adm.php'); unset($L['map_List'][0]);
include 'qtem_map_lib.php';

// INITIALISE

$oVIP->selfurl = 'qtem_map_adm_sections.php';
$oVIP->selfname = 'Map';
$oVIP->exiturl = 'qtem_map_adm.php';
$oVIP->exitname = '&laquo; '.$oVIP->selfname;
$strPageversion = $L['map_Version'].' 3.0';

$arrSections = QTarrget(GetSections('A'));

// Read png in directory

$intHandle = opendir('qtem_map');
$arrFiles = array();
while ( false!==($strFile = readdir($intHandle)) )
{
  if ( $strFile!='.' && $strFile!='..' ) {
  if ( substr($strFile,-4,4)=='.png' ) {
  if ( !strstr($strFile,'shadow') ) {
    $arrFiles[substr($strFile,0,-4)]=ucfirst(substr(str_replace('_',' ',$strFile),0,-4));
  }}}
}
closedir($intHandle);
asort($arrFiles);

// --------
// SUBMITTED for changes
// --------

if ( isset($_POST['ok']) && !empty($_SESSION[QT]['m_map_gkey']) )
{
  // save setting files
  $strFilename = 'qtem_map/config.php';
  $b = false;
  $content = '<?php ';
  foreach($arrSections as $intSecid=>$strSectitle)
  {
  $strIcon = 'false'; if ( isset($_POST['mark_'.$intSecid]) ) { if ( !empty($_POST['mark_'.$intSecid]) ) $strIcon = '"'.$_POST['mark_'.$intSecid].'"'; }
  $content .= '
  $_SESSION[QT]["m_map"]['.$intSecid.'] = array();
  $_SESSION[QT]["m_map"]['.$intSecid.'][0] = '.(isset($_POST['sec_'.$intSecid]) ? 'true' : 'false').';';
  if ( isset($_POST['sec_'.$intSecid]) )
  {
  $content .= '
  $_SESSION[QT]["m_map"]['.$intSecid.']["list"] = '.(isset($_POST['list_'.$intSecid]) ? $_POST['list_'.$intSecid] : 'false').';
  $_SESSION[QT]["m_map"]['.$intSecid.']["icon"] = '.$strIcon.';
  $_SESSION[QT]["m_map"]['.$intSecid.']["shadow"] = '.(file_exists('qtem_map/'.$_POST['mark_'.$intSecid].'_shadow.png') ? 'true' : 'false').';
  $_SESSION[QT]["m_map"]['.$intSecid.']["printicon"] = '.(file_exists('qtem_map/'.$_POST['mark_'.$intSecid].'.gif') ? 'true' : 'false').';
  $_SESSION[QT]["m_map"]['.$intSecid.']["printshadow"] = '.(file_exists('qtem_map/'.$_POST['mark_'.$intSecid].'_shadow.gif') ? 'true' : 'false').';
  ';
  }
  if ( isset($_POST['sec_'.$intSecid]) ) $b=true;
  }

  if ( $b )
  {
  $strIcon = 'false'; if ( isset($_POST['mark_S']) ) { if ( !empty($_POST['mark_S']) ) $strIcon = '"'.$_POST['mark_S'].'"'; }
  $content .= '$_SESSION[QT]["m_map"]["S"] = array();
  $_SESSION[QT]["m_map"]["S"][0] = '.(isset($_POST['sec_S']) ? 'true' : 'false').';';
  if ( isset($_POST['sec_S']) )
  {
  $content .= '
  $_SESSION[QT]["m_map"]["S"]["list"] = '.(isset($_POST['list_S']) ? $_POST['list_S'] : '1').';
  $_SESSION[QT]["m_map"]["S"]["icon"] = '.$strIcon.';
  $_SESSION[QT]["m_map"]["S"]["shadow"] = '.(file_exists('qtem_map/'.$_POST['mark_S'].'_shadow.png') ? 'true' : 'false').';
  $_SESSION[QT]["m_map"]["S"]["printicon"] = '.(file_exists('qtem_map/'.$_POST['mark_S'].'.gif') ? 'true' : 'false').';
  $_SESSION[QT]["m_map"]["S"]["printshadow"] = '.(file_exists('qtem_map/'.$_POST['mark_S'].'_shadow.gif') ? 'true' : 'false').';
  ';
  }
  }
  if (!is_writable($strFilename)) $error="Impossible to write into the file [$strFilename].";
  if ( empty($error) )
  {
  if (!$handle = fopen($strFilename, 'w')) $error="Impossible to open the file [$strFilename].";
  }
  if ( empty($error) )
  {
  if ( fwrite($handle, $content)===FALSE ) $error="Impossible to write into the file [$strFilename].";
  fclose($handle);
  }

  if ( !$b && isset($_POST['sec_S']) ) $warning=$L['map_E_nosearch'];

  // exit
  $_SESSION['pagedialog'] = (empty($error) ? 'O|'.$L['S_save'] : 'E|'.$error);
}

// --------
// HTML START
// --------

// prepare section settings

$_SESSION[QT]['m_map'] = array();
if ( file_exists('qtem_map/config.php') ) require_once 'qtem_map/config.php';

  foreach($arrSections as $intSecid=>$strSectitle)
  {
  if ( !isset($_SESSION[QT]['m_map'][$intSecid]) ) $_SESSION[QT]['m_map'][$intSecid] = array(0=>false);
  }
  if ( !isset($_SESSION[QT]['m_map']['S']) ) $_SESSION[QT]['m_map']['S'] = array(0=>false);


$oHtml->scripts[] = '<script type="text/javascript" src="bin/js/qte_base.js"></script>
<script type="text/javascript">
function mapsection(section)
{
  var doc = document;
  if (doc.getElementById("sec_"+section).checked)
  {
  doc.getElementById("off_"+section).style.display="none";
  doc.getElementById("mark_"+section).style.display="inline";
  doc.getElementById("list_"+section).style.display="inline";
  }
  else
  {
  doc.getElementById("off_"+section).style.display="inline";
  doc.getElementById("mark_"+section).style.display="none";
  doc.getElementById("list_"+section).style.display="none";
  }
  return null;
}
</script>
';

// DISPLAY

include 'qte_adm_p_header.php';

echo '<form method="post" action="',$oVIP->selfurl,'">
<h2 class="subtitle">',L('Sections'),'</h2>
<table class="t-data">
<tr>
<td style="background-color:#ffffff">
<p>',$L['map_Allowed'],'</p>
<table class="subtable">
<tr>
<th style="width:30px">&nbsp;</th>
<th style="width:200px">',$L['Sections'],'</th>
<th>',$L['map_symbols'],'</th>
<th>',$L['map_Main_list'],'</th>
</tr>
';

foreach($arrSections as $intSecid=>$strSectitle)
{
  // compatibility with verion 2.x
  if ( isset($_SESSION[QT]['m_map'][$intSecid]['list']) && $_SESSION[QT]['m_map'][$intSecid]['list']=='M' ) $_SESSION[QT]['m_map'][$intSecid]['list']='2';

echo '<tr class="hover">
<td style="background-color:#c3d9ff"><input type="checkbox" id="sec_',$intSecid,'" name="sec_',$intSecid,'"'.($_SESSION[QT]['m_map'][$intSecid][0] ? QCHE : '').' style="vertical-align: middle" onclick="mapsection(\'',$intSecid,'\')" /></td>
<td><label for="sec_',$intSecid,'">',$strSectitle,'</label></td>
<td>
<select class="small" id="mark_',$intSecid,'" name="mark_',$intSecid,'" size="1" style="',($_SESSION[QT]['m_map'][$intSecid][0] ? '' : 'display:none'),'">
<option value="0">',$L['map_Default'],'</option>
',QTasTag($arrFiles,(isset($_SESSION[QT]['m_map'][$intSecid]['icon']) ? $_SESSION[QT]['m_map'][$intSecid]['icon'] : null)),'
</select>&nbsp;
</td>
<td>
<select class="small" id="list_',$intSecid,'" name="list_',$intSecid,'" size="1" style="',($_SESSION[QT]['m_map'][$intSecid][0] ? '' : 'display:none'),'">',QTasTag($L['map_List'],(isset($_SESSION[QT]['m_map'][$intSecid]['list']) ? $_SESSION[QT]['m_map'][$intSecid]['list'] : null)),'</select><span id="off_',$intSecid,'" style="',($_SESSION[QT]['m_map'][$intSecid][0] ? 'display:none' : ''),'">',$L['N'],'</span>
</td>
</tr>
';
}

  // compatibility with verion 2.x
  if ( isset($_SESSION[QT]['m_map']['S']['list']) && $_SESSION[QT]['m_map']['S']['list']=='M' ) $_SESSION[QT]['m_map']['S']['list']=2;

echo '<tr class="hover">
<td style="background-color:#c3d9ff"><input type="checkbox" id="sec_S" name="sec_S"'.($_SESSION[QT]['m_map']['S'][0] ? QCHE : '').' style="vertical-align: middle" onclick="mapsection(\'S\')" /></td>
<td style="border-top:solid 1px #c3d9ff"><label for="sec_S"><i>Search results</i></label></td>
<td style="border-top:solid 1px #c3d9ff">
<select class="small" id="mark_S" name="mark_S" size="1" style="',($_SESSION[QT]['m_map']['S'][0] ? '' : 'display:none'),'">
<option value="0">',$L['map_Default'],'</option>
',QTasTag($arrFiles,(isset($_SESSION[QT]['m_map']['S']['icon']) ? $_SESSION[QT]['m_map']['S']['icon'] : null)),'
</select>&nbsp;
</td>
<td style="border-top:solid 1px #c3d9ff">
<select class="small" id="list_S" name="list_S" size="1" style="',($_SESSION[QT]['m_map']['S'][0] ? '' : 'display:none'),'">',QTasTag($L['map_List'],(isset($_SESSION[QT]['m_map']['S']['list']) ? $_SESSION[QT]['m_map']['S']['list'] : null)),'</select><span id="off_S" style="',($_SESSION[QT]['m_map'][$intSecid][0] ? 'display:none' : ''),'">',$L['N'],'</span>
</td>
</tr>
</table>
';
echo '</td>
</tr>
</table>
<p class="submit"><input type="submit" name="ok" value="',$L['Save'],'"/></p>
</form>
';

// show table symbols

echo '<h2 class="subtitle">',L('map_symbols'),'</h2>
<table class="t-data">
<tr>
<td class="center"><img alt="i" class="marker" src="bin/css/gmap_marker.png"/><br/><span class="small">Default</span></td>
';
$i=0;
foreach ($arrFiles as $strFile=>$strName)
{
echo '<td class="center"><img alt="i" class="marker" src="qtem_map/'.$strFile.'.png"/><br/><span class="small">'.$strName.'</span></td>
';
$i++;
if ( $i>=9 ) { echo '</tr><tr>'; $i=0; }
}
echo '</tr>
</table>
<p><a href="',$oVIP->exiturl,'" onclick="return qtEdited(bEdited,\'',$L['E_editing'],'\');">',$oVIP->exitname,'</a></p>
';

// HTML END

include 'qte_adm_p_footer.php';