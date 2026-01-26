<?php

function portfolio_masonry_function(){

    $query = new WP_Query(array(
        'post_type'         => 'portfolio',
        'post_status'       => 'publish',
        'posts_per_page'    =>  -1
    ));

    ?>
    <div class="grid">
        <div class="grid-sizer"></div>
        <div class="gutter-sizer"></div>
        <?php
        $count = 1;
        if($query->have_posts()){
            while($query->have_posts()){
                $query->the_post();
                $image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'single-post-thumbnail' );
                $categories = get_the_terms(get_the_ID(),'portfolio-category');

                if($count % 2 == 0){
                    ?>
                    <div class="grid-item item2">
                        <a href="<?php echo get_permalink(get_the_ID()); ?>">
                            <img src="<?php echo $image[0]; ?>" alt="Featured Image">
                            <div class="grid-item-overlay">
                                <div class="gio-content">
                                    <h3><?php echo get_the_title(); ?></h3>
                                    <?php
                                    foreach($categories as $cat_count => $cd){
                                        if(($cat_count + 1) !== count($categories)){
                                            ?>
                                            <span><?php echo $cd->name . ', '; ?></span>
                                            <?php
                                        }else{
                                            ?>
                                                <span><?php echo $cd->name; ?></span>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="gio-bg-overlay"></div>
                            </div>
                        </a>
                    </div>
                    <?php
                }elseif($count % 3 == 0){
                    ?>
                    <div class="grid-item item3">
                        <a href="<?php echo get_permalink(get_the_ID()); ?>">
                            <img src="<?php echo $image[0]; ?>" alt="Featured Image">
                            <div class="grid-item-overlay">
                                <div class="gio-content">
                                    <h3><?php echo get_the_title(); ?></h3>
                                    <?php
                                    foreach($categories as $cat_count => $cd){
                                        if(($cat_count + 1) !== count($categories)){
                                            ?>
                                            <span><?php echo $cd->name . ', '; ?></span>
                                            <?php
                                        }else{
                                            ?>
                                                <span><?php echo $cd->name; ?></span>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="gio-bg-overlay"></div>
                            </div>
                        </a>
                    </div>
                    <?php
                }else{
                    ?>
                    <div class="grid-item item1">
                        <a href="<?php echo get_permalink(get_the_ID()); ?>">
                            <img src="<?php echo $image[0]; ?>" alt="Featured Image">
                            <div class="grid-item-overlay">
                                <div class="gio-content">
                                    <h3><?php echo get_the_title(); ?></h3>
                                    <?php
                                    foreach($categories as $cat_count => $cd){
                                        if(($cat_count + 1) !== count($categories)){
                                            ?>
                                            <span><?php echo $cd->name . ', '; ?></span>
                                            <?php
                                        }else{
                                            ?>
                                                <span><?php echo $cd->name; ?></span>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="gio-bg-overlay"></div>
                            </div>
                        </a>
                    </div>
                    <?php
                }
                $count++;
            }
            wp_reset_postdata();
        }
        
        ?>
       
       
    <style>
        .grid-item{
            margin-bottom: 50px;
        }
        .gutter-sizer,
        .grid-sizer{
            width: 2.5%;
        }
        .grid-item.item1 {
            max-width: 477px;
        }
        .grid-item.item2 {
            max-width: 467px;
        }
        .grid-item.item3 {
            max-width: 476px;
        }
        .grid-item a {
            position: relative;
        }
        .grid-item-overlay {
            position: absolute;
            bottom: 0;
            height: 100%;
            width: 100%;
            display: flex;
            align-items: flex-end;
            visibility: hidden;
            opacity: 0;

            transition: all 0.3s ease-in-out;
        }
        .grid-item a:hover .grid-item-overlay {
            visibility: visible;
            opacity: 1;
        }
        .grid-item-overlay .gio-bg-overlay {
            width: 100%;
            height: 100%;
            position: absolute;
            background-color: #000000;
            opacity: 0.48;
        }
        .gio-content {
            margin-left: 30px;
            margin-bottom: 20px;
            z-index: 2;
        }
        .gio-content * {
            font-family: 'Proxima Nova';
        }
        .gio-content * {
            font-family: 'Proxima Nova';
            color: #fff;
            text-transform: uppercase;
        }
        .gio-content h3 {
            font-size: 22px;
            margin-bottom: 0;
        }
        .gio-content span {
            font-size: 16px;
        }
        @media screen and (max-width:1562px) {
            .grid-item {
                max-width: 30.2vw !important;
            }
        }
        @media screen and (max-width:1272px) {
            .grid-item {
                max-width: 30vw !important;
            }
        }
        @media screen and (max-width:1120px) {
            .grid-item {
                margin-bottom: 30px;
                max-width: 29.8vw !important;
            }
        }
        @media screen and (max-width:1000px) {
            .grid-item {
                margin-bottom: 20px;
                max-width: 47% !important;
            }
            .grid {
                max-width: 660px;
                margin: 0 auto;
            }
        }
        @media screen and (max-width:450px) {
            .grid-item {
                margin-bottom: 25px;
                max-width: 100% !important;
            }
        }
    </style>
    <?php
 }
 
 add_shortcode("portfolio-masonry","portfolio_masonry_function");