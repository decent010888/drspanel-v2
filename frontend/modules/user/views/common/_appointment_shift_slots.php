<?php
$js ="function myFunction() {
    var input, filter, ul, li, a,b, i, txtValue,b,txtValue2;
    input = document.getElementById('myInput');
    filter = input.value.toUpperCase();
    ul = document.getElementsByClassName('search-tokens');
    
    $( 'div.search-tokens' ).each(function( i ) {        
        li = $(this).children('div')
        for (i = 0; i < li.length; i++) {
            a = li[i].getElementsByTagName('h3')[0];
            b = li[i].getElementsByTagName('h4')[0];
            txtValue = a.textContent || a.innerText;
            txtValue2= b.textContent || b.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                li[i].style.display = '';
            }  
            else if(txtValue2.indexOf(filter) > -1) {
                li[i].style.display = '';
            }
            else {
                li[i].style.display = 'none';
            }
        }
    });   
    
}";
$this->registerJs($js,\yii\web\VIEW::POS_END);

if(isset($message)){
    $message=$message;
}
else{
    $message='';
}
?>
<div class="doc-timingslot">
    <ul>
        <?php echo $this->render('/common/_shifts',['shifts'=>$appointments['shifts'],'current_shifts'=>$current_shifts,'doctor'=>$doctor,'type'=>$type,'userType'=>$userType]);?>
    </ul>
</div>
<?php if(!empty($appointments['shifts'])) { ?>
    <?php if(($type == 'current_appointment')){ ?>
        <div class="row">
            <div class="col-sm-12">
                <div class="search-boxicon booking_icon">
                    <div class="search-iconmain"> <i class="fa fa-search"></i> </div>
                    <input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search patient, token ..." title="Type in a name" class="form-control">
                </div>
            </div>
        </div>
     <?php } ?>
    <div class="doc-boxespart-book" id="shift-tokens">
        <?php
        if(($type == 'current_appointment')){
             echo $this->render('/common/_bookings',['bookings'=>$bookings,'doctor_id'=>$doctor->id,'userType'=>$userType]);
        }
        else{
            echo $this->render('/common/_slots',['slots'=>$slots,'doctor_id'=>$doctor->id,'userType'=>$userType,'message'=>($message)?$message:'']);
        }
        ?>
    </div>
<?php } ?>