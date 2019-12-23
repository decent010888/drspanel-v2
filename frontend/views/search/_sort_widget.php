<?php
$sort=array(''=>'--Select--','by_name'=>'By Name','new'=>'Newly Added','price_highttolow'=>'Price, High to Low','price_lowtohigh'=>'Price, Low to High','rating_highttolow'=>'Rating, High to Low','rating_lowtohigh'=>'Rating, Low to High',);
?>
<div class="form-group" id="top_sort">
    <span>Sort by</span>
    <select name="sort_field" id="sort" class="form-control">
        <?php foreach($sort as $key=>$value){	?>
            <?php
            if(!empty($_GET['sort'])){
                $q=$_GET['sort'];
                if($key == $q){ ?>
                    <option selected="selected" value="<?php echo $key; ?>"><?php echo $value; ?></option>
                <?php }
                else{ ?>
                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                <?php }
            }
            else{
                if($key == ''){ ?>
                    <option selected="selected" value="<?php echo $key; ?>"><?php echo $value; ?></option>
                <?php }
                else{ ?>
                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                <?php }
            } ?>
        <?php } ?>
    </select>
</div>