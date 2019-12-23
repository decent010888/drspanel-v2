<div class="slider-part homeslider">
    <div class="slider fade1">
        <?php
        if(!empty($sliders)){
            foreach ($sliders as $slide) { ?>
                <div>
                    <?php if(!empty($slide->link)) { ?>
                        <a target="_blank" href="<?php echo $slide->link; ?>">
                            <div class="image">
                                <img src="<?php echo  $slide->base_path.$slide->file_path.$slide->image?>" />
                            </div>

                        </a>
                    <?php } else { ?>
                        <div class="image">
                            <img src="<?php echo  $slide->base_path.$slide->file_path.$slide->image?>" />
                        </div>
                    <?php } ?>


                </div>
            <?php }
        } ?>
    </div>
</div>