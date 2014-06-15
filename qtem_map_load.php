<?php

if ( !isset($gmap_markers) ) $gmap_markers = array();
if ( !isset($gmap_events) ) $gmap_events = array();
if ( !isset($gmap_functions) ) $gmap_functions = array();

$oHtml->scripts_end[] = QTgmapApi($_SESSION[QT]['m_map_gkey']).'
<script type="text/javascript">
var map, mapOptions, geocoder;
var markers = [];
var infowindow = new google.maps.InfoWindow({maxWidth: 220});

function initialize()
{
  geocoder = new google.maps.Geocoder();
  mapOptions = {
    center: new google.maps.LatLng('.$_SESSION[QT]['m_map_gcenter'].'),
    mapTypeId: '.QTgmapMarkerMapTypeId(substr($_SESSION[QT]['m_map_gbuttons'],0,1)).',
    streetViewControl: '.(substr($_SESSION[QT]['m_map_gbuttons'],1,1)=='1' ? 'true' : 'false' ).',
    mapTypeControl: '.(substr($_SESSION[QT]['m_map_gbuttons'],2,1)=='1' ? 'true' : 'false' ).',
    zoom: '.$_SESSION[QT]['m_map_gzoom'].',
    scaleControl:'.(substr($_SESSION[QT]['m_map_gbuttons'],3,1)=='1' ? 'true' : 'false' ).',
    overviewMapControl:'.(substr($_SESSION[QT]['m_map_gbuttons'],4,1)=='1' ? 'true' : 'false' ).',
    scrollwheel:'.(substr($_SESSION[QT]['m_map_gbuttons'],5,1)=='1' ? 'true' : 'false' ).'
    };
  map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
  var marker;
'.implode(PHP_EOL,$gmap_markers).'
'.implode(PHP_EOL,$gmap_events).'
}

google.maps.event.addDomListener(window,"load",initialize);

function gmapInfo(marker,info)
{
  if ( !marker || !info || info=="" ) return;
  google.maps.event.addListener(marker, "click", function() { infowindow.setContent(info); infowindow.open(map,marker); });
}
function gmapPan(latlng)
{
  if ( !latlng ) return;
  if ( latlng.length==0 ) return;
  if ( infowindow ) infowindow.close();
  var yx = latlng.split(",");
  map.panTo(new google.maps.LatLng(parseFloat(yx[0]),parseFloat(yx[1])));
}
function gmapRound(num)
{
  return Math.round(num*Math.pow(10,11))/Math.pow(10,11);
}
function gmapYXfield(id,marker)
{
  if (!document.getElementById(id)) return;
  if ( marker )
  {
    document.getElementById(id).value = gmapRound(marker.getPosition().lat()) + "," + gmapRound(marker.getPosition().lng());
  } else {
    document.getElementById(id).value = "";
  }
}
'.implode(PHP_EOL,$gmap_functions).'
</script>
';