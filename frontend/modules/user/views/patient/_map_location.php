<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\date\DatePicker;
use common\components\DrsPanel;
use yii\web\View;




$this->registerJs(" 

var map;
var marker;
var myLatlng = new google.maps.LatLng($lat,$lng);
var geocoder = new google.maps.Geocoder();
var infowindow = new google.maps.InfoWindow();
function initialize(){
    var mapOptions = {
        zoom: 11,
        center: myLatlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
     
    map = new google.maps.Map(document.getElementById(\"myMap\"), mapOptions);
    
    marker = new google.maps.Marker({
        map: map,
        position: myLatlng,
        draggable: true 
    });  
       
    markerSet(marker,map,myLatlng);
    markerDrag(marker,map,myLatlng);      
   
   google.maps.event.addListener(map, 'click', function(event) {
        var result = [event.latLng.lat(), event.latLng.lng()];
        console.log(result);
        var myLatlng = new google.maps.LatLng(event.latLng.lat(), event.latLng.lng());
        
        marker.setMap(null);
        
        marker = new google.maps.Marker({
            map: map,
            position: myLatlng,
            draggable: true 
        }); 
       markerSet(marker,map,myLatlng);
       markerDrag(marker,map,myLatlng);    
   });
    
    var options = {
      //types: ['(cities)'],
      componentRestrictions: {country: \"in\"}
     };
    var input = document.getElementById('pac-input');   
 
    var places = new google.maps.places.Autocomplete(input,options);
    //places.setTypes(['geocode']);

    google.maps.event.addListener(places, 'place_changed', function () {
        var place = places.getPlace();
        var latitude = place.geometry.location.lat();
        var longitude = place.geometry.location.lng();        
        
        var myLatlng = new google.maps.LatLng(latitude,longitude);
        marker.setMap(null);
        
        marker = new google.maps.Marker({
            map: map,
            position: myLatlng,
            draggable: true 
        });  
        
        markerSet(marker,map,myLatlng);
        markerDrag(marker,map,myLatlng);            
    }); 


}

function markerSet(marker,map,myLatlng){
     geocoder.geocode({'latLng': myLatlng }, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                if (results[0]) {
                    $('#latitude,#longitude').show();
                    $('#address').val(results[0].formatted_address);
                    $('.pin_address').html(results[0].formatted_address);
                    $('#useraddress-lat').val(marker.getPosition().lat());
                    $('#useraddress-lng').val(marker.getPosition().lng());
                    infowindow.setContent(results[0].formatted_address);
                    map.setCenter(marker.getPosition());
                    infowindow.open(map, marker);
                }
            }
        });
}

function markerDrag(marker,map,myLatlng){
   google.maps.event.addListener(marker, 'dragend', function() {    
        geocoder.geocode({'latLng': marker.getPosition()}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                if (results[0]) {
                    $('#address').val(results[0].formatted_address);
                    $('.pin_address').html(results[0].formatted_address);
                    $('#useraddress-lat').val(marker.getPosition().lat());
                    $('#useraddress-lng').val(marker.getPosition().lng());
                    infowindow.setContent(results[0].formatted_address);
                    map.setCenter(marker.getPosition());
                    infowindow.open(map, marker);
                }
            }
        });
   }); 
}


google.maps.event.addDomListener(window, 'load', initialize);
       
", View::POS_END);

?>

<style>
   /* span.select2-container {
        z-index:999;
    }*/
    #myMap {
       display:block;
        width:100%;
        height:450px;
    }
    #address{
        display:block;
    }
    .pac-card {
        margin: 10px 10px 0 0;
        border-radius: 2px 0 0 2px;
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        outline: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        background-color: #fff;
        font-family: Roboto;
    }

    .pac-container {
        padding-bottom: 12px;
        margin-right: 12px;
        z-index:9999999999;
    }

    .pac-controls {
        display: inline-block;
        padding: 5px 11px;
    }

    .pac-controls label {
        font-family: Roboto;
        font-size: 13px;
        font-weight: 300;
    }

    #pac-input {
        background-color: #fff;
        font-family: Roboto;
        font-size: 15px;
        font-weight: 300;
        margin-left: 12px;
        padding: 0 11px 0 13px;
        text-overflow: ellipsis;
        width: 400px;
    }

    #pac-input:focus {
        border-color: #4d90fe;
    }

</style>

<input id="pac-input" class="controls" type="text" placeholder="Search Box">
<div id="myMap"></div>
<input id="address" type="text"/>

