$(function () {
                



$('.modal-call').on('click',function(){

	var thisId=$(this).attr('id');
	var modalId=thisId.split('-');
	$('#'+modalId[0]+'-modal').modal({backdrop: 'static',keyboard: false,show: true})
});

$('.check-empty').on('change',function(){

	var thisId=$(this).attr('id');
	var value=$(this).val();
	if(value==''){
		$('.field-'+thisId).addClass('has-error required');
		$('#'+thisId).attr('aria-invalid',true);
		$('#'+thisId).next('.help-block').addClass('error').text('This field cannot be blank.');
	}else{
		$('.field-'+thisId).removeClass('has-error required');
		$('#'+thisId).removeAttr('aria-invalid');
		$('#'+thisId).next('.help-block').removeClass('error').text('');  
	}
});

$('.check-unique').on('change',function(){

	var thisId=$(this).attr('id');
	var splitIds=thisId.split('-');
	var value=$(this).val();
	var groupId='#'+splitIds[0]+'-groupid';
	var groupid=$(groupId).val();
	if(groupid==''){
		$('.field-'+splitIds[0]+'-groupid').addClass('has-error required');
		$(groupId).attr('aria-invalid',true);
		$(groupId).next('.help-block').addClass('error').text('This field cannot be blank.');
	}else{
		$('.field-'+splitIds[0]+'-groupid').removeClass('has-error required');
		$(groupId).removeAttr('aria-invalid');
		$(groupId).next('.help-block').removeClass('error').text('');
	}
	if(value==''){
		$('.field-'+thisId).addClass('has-error required');
		$('#'+thisId).attr('aria-invalid',true);
		$('#'+thisId).next('.help-block').addClass('error').text('This field cannot be blank.');
		
	}else{

		$('.field-'+thisId).removeClass('has-error required');
		$('#'+thisId).removeAttr('aria-invalid');
		$('#'+thisId).next('.help-block').removeClass('error').text('');
		

	}
});


// signup form

 



});

// Shift time picker 
$('.addscheduleform-start_time').timepicker({defaultTime: '08:00 A'});
$('.addscheduleform-end_time').timepicker({defaultTime: '12:00 P'});

function signupValidate(formId,baseUrl){ 
	error=true;
 	form=$('#'+formId).serializeArray();
 	var blankGroup=[];
 	var groupid=$('#'+formId+'-groupid').val();
 	var i=0;
$.each($('#'+formId).serializeArray(), function() { 
	var name=this.name
	var value=this.value;
	var name=name.split('[');
	if(typeof name[1] !== 'undefined'){
		name=(name[1].slice(0,-1));
		if(value==''){
			blankGroup[i]=name;
			$('.field-'+formId+'-'+name).addClass('has-error required');
			$('#'+formId+'-'+name).attr('aria-invalid',true);
			$('#'+formId+'-'+name).next('.help-block').addClass('error').text('This field cannot be blank.');
			i++;		
		}else{

			$('.field-'+formId+'-'+name).removeClass('has-error required');
			$('#'+formId+'-'+name).removeAttr('aria-invalid');
			$('#'+formId+'-'+name).next('.help-block').removeClass('error').text('');	
			
		}
	}

});

if(groupid==4 || groupid==3){
	$('#gender_list').show();
	$('#signup_dob').show();
	$('#title_list').show();
	if((jQuery.inArray("gender", blankGroup) !== -1) || (jQuery.inArray("dob", blankGroup) !== -1) || (jQuery.inArray("title", blankGroup) !== -1))
	{
		error=true;
	}else{
		error= false;
	}

}else if(groupid==5){
	$('#gender_list').hide();
	$('#signup_dob').hide();
	$('#title_list').hide();
	if((jQuery.inArray("gender", blankGroup) !== -1) || (jQuery.inArray("dob", blankGroup) !== -1) || (jQuery.inArray("title", blankGroup) !== -1))
	{
		error= false;;
	}else{
		error= true;
	}
}

if(error==false){

		$.ajax({
	 		method: 'POST',
	 		url: baseUrl+'/ajax-unique',
	 		data: $('#'+formId).serialize(),
	 	})
	 	.done(function( resJson ) { 
	 		if(resJson){
	 			var obj = jQuery.parseJSON(resJson);
	 			if(obj.email && obj.phone){
	 				$('.field-'+formId+'-'+'email').addClass('has-error required');
					$('#'+formId+'-'+'email').attr('aria-invalid',true);
					$('#'+formId+'-'+'email').next('.help-block').addClass('error').text('This email address already exists.');

					$('.field-'+formId+'-'+'phone').addClass('has-error required');
					$('#'+formId+'-'+'phone').attr('aria-invalid',true);
					$('#'+formId+'-'+'phone').next('.help-block').addClass('error').text('This phone number already exists.');
	 				error=true;
	 			}else if(obj.email){
	 				$('.field-'+formId+'-'+'email').addClass('has-error required');
					$('#'+formId+'-'+'email').attr('aria-invalid',true);
					$('#'+formId+'-'+'email').next('.help-block').addClass('error').text('This email address already exists.');
	 				error=true;
	 			}else if(obj.phone){
	 				$('.field-'+formId+'-'+'phone').addClass('has-error required');
					$('#'+formId+'-'+'phone').attr('aria-invalid',true);
					$('#'+formId+'-'+'phone').next('.help-block').addClass('error').text('This phone number already exists.');
	 			error=true;
	 			}
		 			
	 			}
	 		
	 	});
	 	return error;

}else{
	return true
}


 }


 function checkGroupUniqueNum(formId,baseUrl,id,groupid)
 {
    var phone=$('#'+formId+'-phone').val();
    //if(!$('#'+formId+'-phone').val().match('[0-9]{10}')){ 
    if(phone!=''){
    if(phone.match('[0-9]{10}')){
	 	$.ajax({
		 		method: 'POST',
		 		url: baseUrl+'/ajax-unique-group-number',
		 		data: {phone:phone,id:id,groupid:groupid},
		 	})
		 	.done(function( res ) { 

		 		if(res!=''){
		 			$('.field-'+formId+'-phone').addClass('has-error required');
					$('#'+formId+'-phone').attr('aria-invalid',true);
					$('#'+formId+'-phone').next('.help-block').addClass('error').text('This phone number already exists.');
		 			
		 		}

		 	});
		} else{
			$('.field-'+formId+'-phone').addClass('has-error required');
			$('#'+formId+'-phone').attr('aria-invalid',true);
			$('#'+formId+'-phone').next('.help-block').addClass('error').text('phone number exactly 10 digits.');
			
			}
	}/*
	else{
		$('.field-'+formId+'-phone').addClass('has-error required');
		$('#'+formId+'-phone').attr('aria-invalid',true);
		$('#'+formId+'-phone').next('.help-block').addClass('error').text('phone number exactly 10 digits.');
	} */
 }