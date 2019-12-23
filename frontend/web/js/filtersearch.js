var url_s=window.location.href;
var baseurl=url_s.split('?')[0];

function checkWidth() {
    var width=$(window).width();
    return width;
}
checkWidth();
$(window).resize(checkWidth);

$(document).on('click','.filter_btn_reset',function(){
    var value = baseurl;
    history.pushState('', 'DrsPanel', value);

    location.reload();
});

$(document).on('click','#filter_apply_btn',function() {
    var areaArr = $("input[type='checkbox'].filter_areas:checked").map(function(){
        return this.value;
    }).get();

    var specializationArr = $("input[type='checkbox'].filter_speciality:checked").map(function(){
        return this.value;
    }).get();

    var genderArr = $("input[type='radio'].filter_gender:checked").map(function(){
        return this.value;
    }).get();
    var availabilityArr = $("input[type='radio'].filter_availability:checked").map(function(){
        return this.value;
    }).get();
    var sortArray = $("input[type='radio'].filter_sort:checked").map(function(){
        return this.value;
    }).get();


    var a=specializationArr.join('&speciality[]=');
    var c=genderArr.join('&gender[]=');
    var d=availabilityArr.join('&availability[]=');
    var e=areaArr.join('&areas[]=');
    var f=sortArray.join('&sort[]=');

    var query = url_make(specializationArr,a,genderArr,c,availabilityArr,d,areaArr,e,sortArray,f);
    var value = baseurl+query;
    history.pushState('', 'DrsPanel', value);

   location.reload();

});

/* make url after click on metal and stone */
function url_make(specializationArr,a,genderArr,c,availabilityArr,d,areaArr,e,sortArr,f){

    if(specializationArr != '' && a != ''){
        var q_level = 'speciality[]='+a;
    }
    if(genderArr != '' && c != ''){
        var c_level = 'gender[]='+c;
    }
    if(availabilityArr != '' && d != ''){
        var s_level = 'availability[]='+d;
    }
    if(areaArr != '' && e != ''){
        var a_level = 'areas[]='+e;
    }
    if(sortArr != '' && f != ''){
        var sort = 'sort[]='+f;
    }


    var query1 = '?'+q_level+'&'+c_level+'&'+s_level+'&'+a_level+'&'+sort;
    var query = query1.replace(/&undefined/g, '').replace(/undefined&/g, '').replace(/undefined/g, '');
    if(query == '?'){ var query = ''; }
    return query;
}