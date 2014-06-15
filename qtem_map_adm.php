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
* @copyright  2013 The PHP Group
* @version    3.0 build:20140608
*/

session_start();
require_once 'bin/qte_init.php';
include Translate('qte_adm.php');
if ( sUser::Role()!='A' ) die($L['E_admin']);

include Translate('qtem_map.php');
include Translate('qtem_map_adm.php');
include 'qtem_map_lib.php';

function IsMapSection($id=0)
{
  return ( isset($_SESSION[QT]["m_map"][$id][0]) && $_SESSION[QT]["m_map"][$id][0] ); //note id can be int or 'U' or 'S'
}
function CountMapSections()
{
  $i=0;
  foreach($_SESSION[QT]['sys_sections'] as $id=>$name)
  {
  if ( IsMapSection($id) ) $i++;
  }
  return $i;
}

// INITIALISE

$oVIP->selfurl = 'qtem_map_adm.php';
$oVIP->selfname = 'Map';
$oVIP->exiturl = $oVIP->selfurl;
$oVIP->exitname = $oVIP->selfname;
$strPageversion = $L['map_Version'].' 3.0<br/>';

// read values
foreach(array('m_map_gkey','m_map_gcenter','m_map_gzoom','m_map_gfind','m_map_gbuttons') as $strValue)
{
  if ( !isset($_SESSION[QT][$strValue]) )
  {
  $arr = GetParam(true,'param="'.$strValue.'"');
  if ( empty($arr) ) die('<span class="error">Parameters not found. The module is probably not installed properly.</span><br/><br/><a href="qte_adm_index.php">&laquo;&nbsp;'.$L['Exit'].'</a>');
  }
}

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
// SUBMITTED
// --------

if ( isset($_POST['ok']) )
{
  // on first initialisation only update gkey
  if ( empty($_SESSION[QT]['m_map_gkey']) )
  {
    $_SESSION[QT]['m_map_gkey'] = trim($_POST['m_map_gkey']); if ( strlen($_SESSION[QT]['m_map_gkey'])<4 ) $_SESSION[QT]['m_map_gkey']='';
    $oDB->Query('UPDATE '.TABSETTING.' SET setting="'.$_SESSION[QT]['m_map_gkey'].'" WHERE param="m_map_gkey"');
  }
  else
  {
    $_SESSION[QT]['m_map_gkey'] = trim($_POST['m_map_gkey']); if ( strlen($_SESSION[QT]['m_map_gkey'])<4 ) $_SESSION[QT]['m_map_gkey']='';
    $_SESSION[QT]['m_map_gcenter'] = trim($_POST['m_map_gcenter']);
    $_SESSION[QT]['m_map_gzoom'] = trim($_POST['m_map_gzoom']);
    $_SESSION[QT]['m_map_gbuttons'] = substr($_POST['maptype'],0,1).(isset($_POST['streetview']) ? '1' : '0').(isset($_POST['map']) ? '1' : '0').(isset($_POST['scale']) ? '1' : '0').(isset($_POST['overview']) ? '1' : '0').(isset($_POST['mousewheel']) ? '1' : '0');
    $_SESSION[QT]['m_map_gfind'] = trim($_POST['m_map_gfind']);
    $_SESSION[QT]['m_map_gsymbol'] = trim($_POST['m_map_gsymbol']); // "iconname" (without extension) or "0" default symbol
    if ( $_SESSION[QT]['m_map_gsymbol']!=='0' && file_exists('qtem_map/'.$_SESSION[QT]['m_map_gsymbol'].'_shadow.png') ) $_SESSION[QT]['m_map_gsymbol'] .= ' '.$_SESSION[QT]['m_map_gsymbol'].'_shadow';

    // save value
    if ( empty($error) )
    {
    $oDB->Query('UPDATE '.TABSETTING.' SET setting="'.$_SESSION[QT]['m_map_gkey'].'" WHERE param="m_map_gkey"');
    $oDB->Query('UPDATE '.TABSETTING.' SET setting="'.$_SESSION[QT]['m_map_gcenter'].'" WHERE param="m_map_gcenter"');
    $oDB->Query('UPDATE '.TABSETTING.' SET setting="'.$_SESSION[QT]['m_map_gzoom'].'" WHERE param="m_map_gzoom"');
    $oDB->Query('UPDATE '.TABSETTING.' SET setting="'.$_SESSION[QT]['m_map_gbuttons'].'" WHERE param="m_map_gbuttons"');
    $oDB->Query('UPDATE '.TABSETTING.' SET setting="'.$_SESSION[QT]['m_map_gfind'].'" WHERE param="m_map_gfind"');
    $oDB->Query('UPDATE '.TABSETTING.' SET setting="'.$_SESSION[QT]['m_map_gsymbol'].'" WHERE param="m_map_gsymbol"');
    }
  }

  // exit
  $_SESSION['pagedialog'] = (empty($error) ? 'O|'.$L['S_save'] : 'E|'.$error);
}

// --------
// HTML START
// --------

// prepare section settings

$_SESSION[QT]['m_map'] = array();
if ( file_exists('qtem_map/config.php') ) require_once 'qtem_map/config.php';

  if ( !isset($_SESSION[QT]['m_map']['U']) ) $_SESSION[QT]['m_map']['U'] = array(0=>false);
  foreach($arrSections as $intSecid=>$strSectitle)
  {
  if ( !isset($_SESSION[QT]['m_map'][$intSecid]) ) $_SESSION[QT]['m_map'][$intSecid] = array(0=>false);
  }
  if ( !isset($_SESSION[QT]['m_map']['S']) ) $_SESSION[QT]['m_map']['S'] = array(0=>false);

if ( $_SESSION[QT]['m_map_gzoom']==='' ) $_SESSION[QT]['m_map_gzoom']='7';


$oHtml->links[]='<link rel="stylesheet" type="text/css" href="qtem_map.css" />';
$oHtml->scripts[] = '<script type="text/javascript" src="bin/js/qte_base.js"></script>';
$oHtml->scripts[] = '<script type="text/javascript">
var enterkeyPressed=false;
function ValidateForm(theForm,enterkeyPressed)
{
  if (enterkeyPressed) return false;
}
function radioHighlight(id)
{
  var doc = document;
  var radios = doc.getElementsByClassName("marker");
  for(var i=radios.length-1;i>=0;i--) radios[i].style.backgroundColor="transparent";
  var imgid = "image_" + id.substring(7);
  if ( doc.getElementById(imgid) )
  {
    doc.getElementById(imgid).style.backgroundColor="#ffffff";
    if ( doc.getElementById("markerpicked") )
    {
    doc.getElementById("markerpicked").src = doc.getElementById(imgid).src;
    }
  }
}
</script>
';
include 'qte_adm_p_header.php';

echo '
<form method="post" action="',Href(),'" onsubmit="return ValidateForm(this,enterkeyPressed);">
<h2 class="subtitle">',$L['map_Mapping_settings'],'</h2>
<table class="t-data">
<tr>
<td class="headfirst" style="width:120px"><label for="m_map_gkey">Google API key</label></td>
<td class="t-data" colspan="2"><input id="m_map_gkey" name="m_map_gkey" size="70" maxlength="100" value="',$_SESSION[QT]['m_map_gkey'],'" onchange="bEdited=true;"/></td>
</tr>
';

//-----------
if ( !empty($_SESSION[QT]['m_map_gkey']) ) {
//-----------


// current symbol
$arr=explode(' ',$_SESSION[QT]['m_map_gsymbol']);// read first icon (can be '0')
$strSymbol=$arr[0];

echo '<tr>
<td class="headfirst" style="width:120px">',$L['map_API_ctrl'],'</td>
<td class="t-data" colspan="2">
<input type="checkbox" id="streetview" name="streetview"'.(substr($_SESSION[QT]['m_map_gbuttons'],1,1)=='1' ? QCHE : '').' style="vertical-align: middle" onchange="bEdited=true;"/><label for="streetview">',$L['map_Ctrl_streetview'],'</label>
&nbsp;<input type="checkbox" id="map" name="map"'.(substr($_SESSION[QT]['m_map_gbuttons'],2,1)=='1' ? QCHE : '').' style="vertical-align: middle" onchange="bEdited=true;"/><label for="map">',$L['map_Ctrl_background'],'</label>
&nbsp;<input type="checkbox" id="scale" name="scale"'.(substr($_SESSION[QT]['m_map_gbuttons'],3,1)=='1' ? QCHE : '').' style="vertical-align: middle" onchange="bEdited=true;"/><label for="scale">',$L['map_Ctrl_scale'],'</label>
&nbsp;<input type="checkbox" id="overview" name="overview"'.(substr($_SESSION[QT]['m_map_gbuttons'],4,1)=='1' ? QCHE : '').' style="vertical-align: middle" onchange="bEdited=true;"/><label for="overview">',$L['map_Ctrl_overview'],'</label>
&nbsp;<input type="checkbox" id="mousewheel" name="mousewheel"'.(substr($_SESSION[QT]['m_map_gbuttons'],5,1)=='1' ? QCHE : '').' style="vertical-align: middle" onchange="bEdited=true;"/><label for="mousewheel">',$L['map_Ctrl_mousewheel'],'</label>
</td>
</tr>
<tr>
<td class="headfirst" style="width:120px">',$L['map_default_symbol'],'</td>
<td style="width:70px;text-align:center"><img id="markerpicked" title="default" alt="i" src="',(empty($strSymbol) ? 'bin/css/gmap_marker.png' : 'qtem_map/'.$strSymbol.'.png' ),'"/></td>
<td>
<p class="small" style="margin:0;text-align:center">',$L['map_Click_to_change'],'</p>
<div class="markerpicker">
<input type="radio" name="m_map_gsymbol" value="0" id="symbol_0"'.(empty($_SESSION[QT]['m_map_gsymbol']) ? QCHE : '').' onchange="radioHighlight(this.id);bEdited=true;"/><label for="symbol_0"><img id="image_0" alt="i" class="marker'.($_SESSION[QT]['m_map_gsymbol']==$strFile ? ' checked' : '').'" title="default" src="bin/css/gmap_marker.png"/></label>
';
foreach ($arrFiles as $strFile=>$strName)
{
echo '<input type="radio" name="m_map_gsymbol" value="'.$strFile.'" id="symbol_'.$strFile.'"'.($strSymbol===$strFile ? QCHE : '').' onchange="radioHighlight(this.id);bEdited=true;"/><label for="symbol_'.$strFile.'"><img id="image_'.$strFile.'" alt="i" class="marker'.($strSymbol==$strFile ? ' checked' : '').'" title="'.$strName.'" src="qtem_map/'.$strFile.'.png"/></label>'.PHP_EOL;
}
echo '</div>
</td>
</tr>
<tr class="t-data">
<td class="headfirst" style="width:120px;">',L('Sections'),'</td>
<td colspan="2"><strong>
',CountMapSections(),'</strong>/',count($_SESSION[QT]['sys_sections']),
(IsMapSection('U') ? ' '.L('and').' '.L('Users') : ''),
(IsMapSection('S') ? ' '.L('and').' '.L('Search_result') : ''),
' &middot; <a href="',Href('qtem_map_adm_sections.php'),'">',L('map_define_sections'),'...</a>
</td>
</tr>
</table>
';
echo '<h2 class="subtitle">',$L['map_Mapping_config'],'</h2>
<table class="t-data">
<tr class="t-data">
<td class="headfirst" style="width:120px;"><label for="m_map_gcenter">',$L['map_Center'],'</label></td>
<td class="t-data" style="width:280px;"><input type="text" id="m_map_gcenter" name="m_map_gcenter" size="28" maxlength="100" value="',$_SESSION[QT]['m_map_gcenter'],'" onchange="bEdited=true;"/><span class="small"> ',$L['map_Latlng'],'</span></td>
<td><span class="help">',$L['map_H_Center'],'</span></td>
</tr>
<tr class="t-data">
<td class="headfirst" style="width:120px;"><label for="m_map_gzoom">',$L['map_Zoom'],'</label></td>
<td class="t-data">
<input type="text" id="m_map_gzoom" name="m_map_gzoom" size="2" maxlength="2" value="',$_SESSION[QT]['m_map_gzoom'],'" onchange="bEdited=true;"/></td>
<td><span class="help">',$L['map_H_Zoom'],'</span></td>
</tr>
<tr>
<td class="headfirst" style="width:120px;">',$L['map_Background'],'</td>
<td class="t-data"><select id="maptype" name="maptype" size="1" onchange="bEdited=true;">',QTasTag($L['map_Back'],substr($_SESSION[QT]['m_map_gbuttons'],0,1)),'</select></td>
<td><span class="help">',$L['map_H_Background'],'</span></td>
</tr>
<tr>
<td class="headfirst" style="width:120px;"><label for="m_map_gfind">',$L['map_Address_sample'],'</label></td>
<td class="t-data">
<input type="text" id="m_map_gfind" name="m_map_gfind" size="20" maxlength="100" value="',$_SESSION[QT]['m_map_gfind'],'" onchange="bEdited=true;"/></td>
<td><span class="help">',$L['map_H_Address_sample'],'</span></td>
</tr>
';

//-----------
}
//-----------

echo '</table>
<p style="text-align:center"><input type="submit" name="ok" value="',$L['Save'],'"/></p>
</form>
';

if ( !empty($_SESSION[QT]['m_map_gkey']) )
{
  echo '<div class="gmap">',PHP_EOL;
  echo '<p class="gmap commands" style="margin:2px 0 4px 2px;text-align:right">',$L['map_canmove'],' | <a class="gmap" href="javascript:void(0)" onclick="undoChanges(); return false;" />',$L['map_undo'],'</a></p>',PHP_EOL;
  echo '<div id="map_canvas"></div>',PHP_EOL;
  echo '<p class="gmap commands" style="margin:4px 0 2px 2px;text-align:right">',$L['map_addrlatlng'];
  echo ' <input type="text" size="24" id="find" name="find" class="small" value="'.$_SESSION[QT]['m_map_gfind'].'" title="'.$L['map_H_addrlatlng'].'" onkeypress="enterkeyPressed=qtKeyEnter(event); if (enterkeyPressed) showLocation(this.value,null);"/>';
  echo '<img id="findit" src="qtem_map_find.png" alt="find" onclick="showLocation(document.getElementById(\'find\').value,null);" title="'.L('Search').'"/>',PHP_EOL;
  echo '</div>',PHP_EOL;
}
else
{
  echo '<p class="disabled">',$L['map_E_disabled'],'</p>';
}

// HTML END

if ( !empty($_SESSION[QT]['m_map_gkey']) )
{
  $gmap_shadow = false;
  $gmap_symbol = false;
  if ( !empty($_SESSION[QT]['m_map_gsymbol']) )
  {
    $arr = explode(' ',$_SESSION[QT]['m_map_gsymbol']);
    $gmap_symbol=$arr[0];
    if ( isset($arr[1]) ) $gmap_shadow=$arr[1];
  }

  $gmap_markers = array();
  $gmap_events = array();
  $gmap_functions = array();

  $gmap_markers[] = QTgmapMarker($_SESSION[QT]['m_map_gcenter'],true,$gmap_symbol,$L['map_Default_center'],'',$gmap_shadow);
  $gmap_events[] = '
	google.maps.event.addListener(marker, "position_changed", function() {
		if (document.getElementById("m_map_gcenter")) {document.getElementById("m_map_gcenter").value = gmapRound(marker.getPosition().lat(),10) + "," + gmapRound(marker.getPosition().lng(),10);}
	});
	google.maps.event.addListener(marker, "dragend", function() {
		map.panTo(marker.getPosition());
	});';
  $gmap_functions[] = '
  function undoChanges()
  {
  	if (infowindow) infowindow.close();
  	if (markers[0]) markers[0].setPosition(mapOptions.center);
  	if (mapOptions) map.panTo(mapOptions.center);
  	return null;
  }
  function showLocation(address,title)
  {
    if ( infowindow ) infowindow.close();
    geocoder.geocode( { "address": address}, function(results, status) {
      if (status == google.maps.GeocoderStatus.OK)
      {
        map.setCenter(results[0].geometry.location);
        if ( markers[0] )
        {
          markers[0].setPosition(results[0].geometry.location);
        } else {
          markers[0] = new google.maps.Marker({map: map, position: results[0].geometry.location, draggable: true, animation: google.maps.Animation.DROP, title: title});
        }
        gmapYXfield("qte_gcenter",markers[0]);
      } else {
        alert("Geocode was not successful for the following reason: " + status);
      }
    });
  }
  ';
  include 'qtem_map_load.php';
}

include 'qte_adm_p_footer.php';