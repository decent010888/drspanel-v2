<?php
$this->title = Yii::t('frontend','DrsPanel::Doctor List');
$base_url= Yii::getAlias('@frontendUrl');
?>
<section class="mid-content-part inner-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-7 mx-auto">
                    <div class="today-appoimentpart">
                        <h3 class="text-left mb-3 cat_heading"> Specialization </h3>
                    </div>
                    <?php if(!empty($lists)) {
                        ?>
                        <?php foreach ($lists as $list) { ?>
                            <?php if($list['count'] > 0) { ?>
                                <div class="speciality-part">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <a href="<?php echo $base_url?>/specialization/<?php echo strtolower($list['slug'])?>?type=<?php echo $type ?>">
                                                <div class="bord-part">
                                                     <?php if(!empty($list['icon'])) { ?>
                                                    <div class="speleft-part">
                                                         <img src="<?php echo $list['icon']?>" alt="image">
                                                         </div>
                                                        <?php } else { ?>
                                                    <div class="speleft-part">
                                                            <span class="first_letter"><?php echo strtoupper(mb_substr($list['label'], 0, 1)); ?></span>
                                                    </div>
                                                        <?php
                                                        } ?>

                                                </div>
                                                <div class="speright-part">
                                                    <h6><?php echo $list['label']?><!--(--><?php /*echo $list['count']*/?></h6>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php }
                        }
                    } ?>
                </div>
                <?php echo $this->render('/layouts/rightside'); ?>
            </div>
        </div>
    </div>
</section>
