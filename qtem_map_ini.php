<?php

$bMap=true;
if ( empty($_SESSION[QT]['m_map_gkey']) ) $bMap=false;
if ( $bMap ) { require_once 'qtem_map_lib.php'; if ( !QTgcanmap($strCheck) ) $bMap=false; }
if ( $bMap ) 
{
  include Translate('qtem_map.php');
  $bMapGoogle=true;
  $bMapSitework=false;
  if ( !empty($_SESSION[QT]['m_sitework']) ) { $bMapSitework=true; $bMapGoogle=false; }
  if ( $bMapGoogle ) { $strBodyAddOnunload='GUnload()'; $oHtml->links[] = '<link rel="stylesheet" type="text/css" href="qtem_map.css" />'; }
  if ( isset($_GET['hidemap']) ) $_SESSION[QT]['m_map_hidelist']=($_GET['hidemap']=='1');
  if ( !isset($_SESSION[QT]['m_map_hidelist']) ) $_SESSION[QT]['m_map_hidelist']=false;
}