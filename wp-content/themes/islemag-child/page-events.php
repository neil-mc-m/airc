<?php
/*
 * Template Name: Events page
 */
get_header(); ?>
    <div class="container">
    <div class="row">
<!--    <div id="primary" class="content-area">-->
        <div class="islemag-content-left col-md-12">
<!--            <main id="main" class="site-main" role="main">-->

<?php
$args = array(  'post_type' => 'events',
    'posts_per_page' => 10
);
$loop = new WP_Query( $args );
while ( $loop->have_posts() ) : $loop->the_post();
    get_template_part( 'template-parts/content', get_post_format() );


        endwhile;
?>
    </div><!-- #primary -->
</div><!-- row -->
</div><!-- container -->
<?php //get_sidebar(); ?>
<?php get_footer(); ?>

