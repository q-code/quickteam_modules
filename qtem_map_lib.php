<?php

/* ============
 * qte_map_lib.php
 * ------------
 * version: 3.0 build:20140608
 * This is a module library
 * ------------
 * QTgempty()
 * QTgmapscript()
 * QTgmappoints()
 * ============ */

class cMapPoint
{
  // Properties

  public $y = 4.352;
  public $x = 50.847;
  public $title = ''; // marker tips
  public $info = '';  // html to display on click
  public $icon = false;
  public $shadow = false;
  public $printicon = false;
  public $printshadow = false;

  // Class constructor

  function cMapPoint($y,$x,$title='',$info='')
  {
    if ( isset($y) && isset($x) )
    {
      $this->y = $y;
      $this->x = $x;
    }
    else
    {
      global $qte_gcenter;
      if ( isset($qte_gcenter) )
      {
      $this->y = floatval(QTgety($qte_gcenter));
      $this->x = floatval(QTgetx($qte_gcenter));
      }
    }
    if ( !empty($title) ) $this->title = $title;
    if ( !empty($info) ) $this->info = $info;
  }

  // Methods

  function MarkerWith($str='shadow')
  {
    if ( $str=='shadow' )     { if ( $this->icon && $this->shadow ) return 'true'; }
    if ( $str=='printicon' )  { if ( $this->icon && $this->printicon ) return 'true'; }
    if ( $str=='printshadow' ){ if ( $this->icon && $this->printshadow ) return 'true'; }
    return 'false';
  }
}

// Attention x,y,z MUST be FLOAT (or null)
// If x,y,z are NULL or not float, these functions will returns FALSE.
// When entity (item) is created, the x,y,z are null (i.e. no point, no display)

// ---------


// QTgcanmap
// $section is 'S' search result, or 'i' the section id (as integer)
// 'U' means user profile, thus is true by definition

function QTgcanmap($section=null,$strRole='')
{
  // Check

  if ( !isset($section) ) die('QTgcanmap: arg #1 must be a section ref');
  if ( !is_string($strRole) ) die('QTgcanmap: arg #2 must be an string');
  if ( $section===-1 ) return FALSE;

  // Check module

  if ( empty($_SESSION[QT]['m_map_gkey']) ) return FALSE;

  // Added section registery if missing

  global $oVIP;

  if ( !isset($_SESSION[QT]['m_map']) )
  {
    $_SESSION[QT]['m_map'] = array();
    if ( file_exists('qtem_map/config.php') ) require_once 'qtem_map/config.php';

    foreach($oVIP->sections as $intSecid=>$strSectitle)
    {
    if ( !isset($_SESSION[QT]['m_map'][$intSecid]) ) $_SESSION[QT]['m_map'][$intSecid] = array(0=>false);
    }
    if ( !isset($_SESSION[QT]['m_map']['S']) ) $_SESSION[QT]['m_map']['S'] = array(0=>false);
  }

  // check section
  if ( !isset($_SESSION[QT]['m_map'][$section]) ) return FALSE;
  if ( !$_SESSION[QT]['m_map'][$section][0] ) return FALSE;

  // check section list access right
  if ( !empty($strRole) )
  {
    if ( !$_SESSION[QT]['m_map'][$section]['list'] || $_SESSION[QT]['m_map'][$section]['list']==0 ) return FALSE;
    if ( $_SESSION[QT]['m_map'][$section]['list']==='M' ) $_SESSION[QT]['m_map'][$section]['list']=2; // compatibility with version 2.x
    if ( $_SESSION[QT]['m_map'][$section]['list']==2 && $strRole=='V' ) return FALSE;
    if ( $_SESSION[QT]['m_map'][$section]['list']==2 && $strRole=='U' ) return FALSE;
  }

  // exit
  return TRUE;

}

// ---------

function QTgmapApi($strKey='',$strAddLibrary='')
{
  if ( empty($strKey) ) return '';
  return '<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key='.$strKey.'&amp;sensor=false"></script>'.PHP_EOL.(empty($strAddLibrary) ? '' : $strAddLibrary);
}

// ---------

// QTgempty
// Returns true when $i is empty or a value starting with '0.000000'

function QTgempty($i)
{
  if ( empty($i) ) return true;
  if ( !is_string($i) && !is_float($i) && !is_int($i) ) die('QTgempty: Invalid argument #1');
  if ( substr((string)$i,0,8)=='0.000000' ) return true;
  return false;
}

// QTgemptycoord
// Returns true when $a has empty coordinates in both Y and X.
// $a can be a cMapPoint or cItem object or a string Y,X. ex: "51.75,4.12"
// Note: returns true if $a is not correctly formatted or when properties x or y are missing.
// Note: Z coordinate is NOT evaluated. ex: QTgemptycoord("0,0,125") returns true.

function QTgemptycoord($a)
{
  if ( is_a($a,'cMapPoint') || is_a($a,'cItem') )
  {
    if ( !property_exists($a,'y') ) return true;
    if ( !property_exists($a,'x') ) return true;
    if ( QTgempty($a->y) && QTgempty($a->x) ) return true;
    return false;
  }
  if ( is_string($a) )
  {
    if ( QTgempty(QTgety($a,true)) && QTgempty(QTgetx($a,true)) ) return true;
    return false;
  }
  die('QTgemptycoord: invalid argument #1');
}

// ---------

function QTgmapMarker($centerLatLng='',$draggable=false,$gsymbol=false,$title='',$info='',$gshadow=false)
{
  if ( $centerLatLng==='' || $centerLatLng==='0,0' ) return 'marker = null;';
  if ( $centerLatLng=='map' )
  {
    $centerLatLng = 'map.getCenter()';
  }
  else
  {
    $centerLatLng = 'new google.maps.LatLng('.$centerLatLng.')';
  }
  if ( $draggable=='1' || $draggable==='true' || $draggable===true )
  {
  	$draggable='draggable:true, animation:google.maps.Animation.DROP,';
  }
  else
  {
  	$draggable='draggable:false,';
  }
  return '	marker = new google.maps.Marker({
		position: '.$centerLatLng.',
		map: map,
		' . $draggable . QTgmapMarkerIcon($gsymbol,$gshadow) . '
		title: qtHtmldecode("'.$title.'")
		});
		markers.push(marker); '.PHP_EOL.(empty($info) ? '' : '	gmapInfo(marker,\''.$info.'\');');
}

// ---------

function QTgmapMarkerIcon($gsymbol=false,$gshadow=false,$qte_gprinticon=true,$qte_gprintshadow=false)
{
  // returns the google.maps.Marker.icon argument (or nothing in case of default symbol)
  if ( empty($gsymbol) ) return '';
  $str = '';
  // icons are 32x32 pixels and the anchor depends on the name: (10,32) for puhspin, (16,32) for point, center form others
  $arr = explode('_',$gsymbol);
  switch($arr[0])
  {
    case 'pushpin':
      $str = 'icon: new google.maps.MarkerImage("qtem_map/'.$gsymbol.'.png",new google.maps.Size(32,32),new google.maps.Point(0,0),new google.maps.Point(10,32)),';
      if ( $gshadow ) $str .= 'shadow: new google.maps.MarkerImage("qtem_map/'.$gshadow .'.png",new google.maps.Size(59,32),new google.maps.Point(0,0),new google.maps.Point(10,32)),';
      break;
    case 'point':
     $str = 'icon: new google.maps.MarkerImage("qtem_map/'.$gsymbol.'.png",new google.maps.Size(32,32),new google.maps.Point(0,0),new google.maps.Point(16,32)),';
      if ( $gshadow ) $str .= 'shadow: new google.maps.MarkerImage("qtem_map/'.$gshadow .'.png",new google.maps.Size(59,32),new google.maps.Point(0,0),new google.maps.Point(16,32)),';
     break;
    default:
     $str = 'icon: new google.maps.MarkerImage("qtem_map/'.$gsymbol.'.png",new google.maps.Size(32,32),new google.maps.Point(0,0),new google.maps.Point(16,16)),';
      if ( $gshadow ) $str .= 'shadow: new google.maps.MarkerImage("qtem_map/'.$gshadow .'.png",new google.maps.Size(59,32),new google.maps.Point(0,0),new google.maps.Point(16,16)),';
     break;
  }
  return $str;
}

// ---------

function QTgmapMarkerMapTypeId($qte_gbuttons)
{
  switch($qte_gbuttons)
  {
	case 'S':
	case 'SATELLITE': return 'google.maps.MapTypeId.SATELLITE'; break;
	case 'H':
	case 'HYBRID': return 'google.maps.MapTypeId.HYBRID'; break;
	case 'P':
	case 'T':
	case 'TERRAIN': return 'google.maps.MapTypeId.TERRAIN'; break;
	default: return 'google.maps.MapTypeId.ROADMAP';
  }
}

// ---------

function QTgetx($str=null,$onerror=0.0)
{
  // checks
  if ( !is_string($str) ) { if ( isset($onerror) ) return $onerror; die('QTgetx: arg #1 must be a string'); }
  if ( !strstr($str,',') ) { { if ( isset($onerror) ) return $onerror; die('QTgetx: arg #1 must be a string with 2 values'); }}
  $arr = explode(',',$str);
  if ( count($arr)<2 ) { if ( isset($onerror) ) return $onerror; die('QTgetx: coordinate must include at least 2 values'); }
  $str = trim($arr[1]);
  if ( !is_numeric($str) ) { if ( isset($onerror) ) return $onerror; die('QTgetx: x-coordinate is not a float'); }
  return (float)$str;
}
function QTgety($str=null,$onerror=0.0)
{
  // checks
  if ( !is_string($str) ) { if ( isset($onerror) ) return $onerror; die('QTgety: arg #1 must be a string'); }
  if ( !strstr($str,',') ) { { if ( isset($onerror) ) return $onerror; die('QTgety: arg #1 must be a string with 2 values'); }}
  $arr = explode(',',$str);
  if ( count($arr)<2 ) { if ( isset($onerror) ) return $onerror; die('QTgety: coordinate must include at least 2 values'); }
  $str = trim($arr[0]);
  if ( !is_numeric($str) ) { if ( isset($onerror) ) return $onerror; die('QTgety: y-coordinate is not a float'); }
  return (float)$str;
}
function QTgetz($str=null,$onerror=0.0)
{
  // checks
  if ( !is_string($str) ) { if ( isset($onerror) ) return $onerror; die('QTgetz: arg #1 must be a string'); }
  if ( !strstr($str,',') ) { { if ( isset($onerror) ) return $onerror; die('QTgetz: arg #1 must be a string with at least 3 values'); }}
  $arr = explode(',',$str);
  if ( count($arr)<3 ) { if ( isset($onerror) ) return $onerror; die('QTgetz: coordinate must include at least 3 values'); }
  $str = trim($arr[2]);
  if ( !is_numeric($str) ) { if ( isset($onerror) ) return $onerror; die('QTgetz: z-coordinate is not a float'); }
  return (float)$str;
}

// ---------

function QTstr2yx($str)
{
  // check

  if ( !is_string($str) ) die('QTstr2dd: arg #1 must be a string');
  $str = trim($str);
  $str = str_replace('+','',$str);
  $str = str_replace(';',',',$str);
  $arr = explode(',',$str);
  if ( count($arr)!=2 ) return false;

  // analyse each values

  foreach($arr as $intKey=>$str)
  {
    $str = trim(strtoupper($str));
    if ( substr($str,0,1)=='N' || substr($str,0,1)=='E' ) $str = substr($str,1);
    if ( substr($str,0,1)=='S' || substr($str,0,1)=='W' ) $str = '-'.substr($str,1);
    if ( substr($str,-1,1)=='N' || substr($str,-1,1)=='E' ) $str = trim(substr($str,0,-1));
    if ( substr($str,-1,1)=='S' || substr($str,-1,1)=='W' ) $str = '-'.trim(substr($str,0,-1));
    $str = str_replace('--','-',$str);

    // convert dms to dd
    if ( strstr($str,'D') || strstr($str,'?') || strstr($str,"'") || strstr($str,'"') || strstr($str,'?') )
    {
      $str = str_replace(array('SEC','S',"''",'??','"'),'/',$str);
      $str = str_replace(array('MIN','M',"'",'?'),'/',$str);
      $str = str_replace(array('DEG','D','?',':'),'/',$str);
      if ( substr($str,-1,1)=='/' ) $str = substr($str,0,-1);
      $arrValues = explode('/',$str);
      $intD = intval($arrValues[0]); if ( !QTisbetween($intD,($intKey==0 ? -90 : -180),($intKey==0 ? 90 : 180)) ) return false;
      $intM = 0;
      $intS = 0;
      if ( isset($arrValues[1]) ) { $intM = intval($arrValues[1]); if ( !QTisbetween($intM,0,59) ) return false; }
      if ( isset($arrValues[2]) ) { $intS = intval($arrValues[2]); if ( !QTisbetween($intS,0,59) ) return false; }
      $str = $intD+($intM/60)+($intS/3600);
    }

    if ( !QTisbetween(intval($str),($intKey==0 ? -90 : -180),($intKey==0 ? 90 : 180)) ) return false;
    $arr[$intKey]=$str;
  }

  // returns 2 dd in a string

  return $arr[0].','.$arr[1];
}

// ---------

function QTdd2dms($dd,$intDec=0)
{
  $dms_d = intval($dd);
  $dd_m = abs($dd - $dms_d);
  $dms_m_float = 60 * $dd_m;
  $dms_m = intval($dms_m_float);
  $dd_s = abs($dms_m_float - $dms_m);
  $dms_s = 60 * $dd_s;
  return $dms_d.'&#176;'.$dms_m.'&#039;'.round($dms_s,$intDec).'&quot;';
}