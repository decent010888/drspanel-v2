<?php
use common\models\Advertisement;
$baseUrl= Yii::getAlias('@frontendUrl');
?>
<div class="col-sm-3 ads-part">
    <div class="mobile_img">
    <?php $advertisement=Advertisement::getAdvertisementList(Advertisement::TYPE_TOP);  ?>

        <div class="slider autoplay1">
            <?php foreach($advertisement as $advertisement){ ?>
                <div><img src="<?php echo $baseUrl.'/'.$advertisement['image_path']; ?>"></div>
            <?php } ?>
        </div>


    <?php $advertisement=Advertisement::getAdvertisementList(Advertisement::TYPE_BOTTOM);  ?>

        <div class="slider autoplay2">
            <?php foreach($advertisement as $advertisement){ ?>
                <div><img src="<?php echo $baseUrl.'/'.$advertisement['image_path']; ?>"></div>
            <?php } ?>
        </div>
    </div>
</div>
