 <?php
 use common\components\DrsPanel;
 $base_url= Yii::getAlias('@frontendUrl'); 
 $aboutusData = DrsPanel::getAboutUs($user_id);
 if(!empty($aboutusData))
 { ?>
 	<div class="_aboutus">
         <?php if(!empty($aboutusData['description'])) { ?>
            <h4> About</h4>
            <p><?php echo $aboutusData['description'] ?></p>
         <?php } ?>
         <?php if(!empty($aboutusData['vision'])) { ?>
            <p> <strong> Vision </strong> </p>
            <p><?php echo $aboutusData['vision'] ?></p>
        <?php } ?>
     <?php if(!empty($aboutusData['mission'])) { ?>
	 	<p> <strong> Mission </strong>
	 	<p><?php echo $aboutusData['mission'] ?></p>
     <?php } ?>
     <?php if(!empty($aboutusData['timing'])) { ?>
	 	<p> <strong> Timing </strong>
	 	<p><?php echo $aboutusData['timing'] ?></p>
     <?php } ?>
 	</div>
<?php 
} 
?>