<?php 
use yii\helpers\Html;
use common\models\MetaValues;

$social_links = MetaValues::socialLinks();
$baseUrl= Yii::getAlias('@frontendUrl');
?>
<!-- Footer -->
<footer class="bg-black" >
  <div class="container">
     <div class="row">
       <div class="col-sm-12 text-center">
           <h3 class="lg_pb_20">MakeCall Care</h3>
           <ul class="footer-link">
               <li><a href="<?php echo $baseUrl ?>/page/about-us"><i class="fa fa-caret-right"></i> About Us</a></li>
               <li><a href="<?php echo $baseUrl ?>/page/refund-policy"><i class="fa fa-caret-right"></i> Refund Policy</a></li>
               <li><a href="<?php echo $baseUrl ?>/page/privacy-policy"><i class="fa fa-caret-right"></i> Privacy Policy</a></li>
               <li><a href="<?php echo $baseUrl ?>/page/terms-condition"><i class="fa fa-caret-right"></i> Terms & Conditions</a></li>
               <li><a href="<?php echo $baseUrl ?>/contact-us"><i class="fa fa-caret-right"></i> Contact us</a></li>

           </ul>
       </div>
       

       

</div>
</div>
<!-- /.container -->
<div class="copy-part">
    <p>&copy; Copyright <?php echo date('Y');?> DRSPANEL | All Rights Reserved.</p>
</div>
    <input type="hidden" name="uribase" id="uribase" value="<?php echo $baseUrl; ?>"/>

</footer>


