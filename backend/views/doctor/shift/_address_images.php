<?php
use yii\helpers\Url;
use kartik\file\FileInput;

?>
    <div class="col-md-12 address_attachment">
        <?php
        $maxcount=8;
        if(!empty($addressImages)){
            $count=count($addressImages);
            $maxcount = 8 - $count;
            ?>
            <div class="address_gallery gallary_images">
                <?php foreach($addressImages as $addressImage) { ?>
                    <?php $image_url=$addressImage->image_base_url.$addressImage->image_path.$addressImage->image; ?>
                    <div class="address_img_attac">
                        <img class="imageThumb" src="<?= $image_url?>" title="<?= $addressImage->image; ?>">
                        <?php if($disable_field == 0) { ?>
                            <span class="address_image_remove remove" id="<?php echo $addressImage->id; ?>" data-keyid="<?php echo $addressImage->id; ?>" data-usertype="doctor"><i class="fa fa-trash"></i></span>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

        <?php } ?>
    </div>


<?php if($disable_field == 0  && $maxcount >= 1 ) { ?>
    <div class="col-md-12">

        <?php echo $form->field($userAdddressImages, 'image[]')->widget(FileInput::classname(), [
            'options' => [
                'accept' => 'image/*',
                'multiple' => true,

            ],
            'pluginOptions' => [
                'overwriteInitial' => false,
                'validateInitialCount'=> true,
                'maxFileCount' => $maxcount,
                'allowedFileExtensions' => ['jpg', 'png', 'jpeg'],
                'minImageWidth'=> 640,
                'minImageHeight'=> 480,

                //'autoReplace'=>true,
                'showCancel' => false,
                'showRemove' => false,
                'showUpload' => false,
                'showCaption'=> false,


                'browseOnZoneClick'=>true,
                'browseIcon'=>'<i aria-hidden="true" class="fa fa-paperclip"></i>',
                'browseClass' => 'btn upload-btn-danger' ,
                'browseLabel' => 'Attach Files',
                'fileActionSettings'=>[
                    'showUpload' => false,
                    'showDownload'=> false,
                    'showZoom'=> false,
                    'showDrag'=> false,
                    'indicatorNew'=>'',
                    //'showRemove' => false,

                ],



            ],
        ])->label(false);
        ?>
    </div>
<?php } ?>