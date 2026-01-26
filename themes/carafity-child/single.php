<?php

get_header(); ?>
<section class="full-width-banner">
    <div class="banner" style="background-image: url('/wp-content/uploads/2024/09/portfolio_banner.jpg');">
        <div class="banner-content">
            <h1><?php the_title(); ?></h1>
            <nav class="breadcrumbs">
                <a href="<?php echo home_url(); ?>">Home</a> > 
                <?php if (get_post_type() == 'portfolio') : ?>
                    <a href="<?php echo home_url('/portfolio'); ?>">Portfolio</a> > 
                <?php else : ?>
                    <a href="<?php echo home_url('/blog'); ?>">Blog</a> > 
                <?php endif; ?>
                <span><?php the_title(); ?></span>
            </nav>
        </div>
    </div>
</section>
	<div id="primary" class="content-area">
		<main id="main" class="site-main">

		<?php
		while ( have_posts() ) :
			the_post();

			do_action( 'carafity_single_post_before' );

			get_template_part( 'content', 'single' );

			do_action( 'carafity_single_post_after' );

		endwhile; // End of the loop.
		?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
do_action( 'carafity_sidebar' );
get_footer();
