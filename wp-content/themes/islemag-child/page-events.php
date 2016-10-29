<?php
/*
 * Template Name: Events page
 */
get_header(); ?>

    <div id="primary" class="content-area">
        <div class="islemag-content-left col-md-9">
            <main id="main" class="site-main" role="main">

                <?php
                $args = array(  'post_type' => 'events',
                                'posts_per_page' => 10
                );
                $loop = new WP_Query( $args );
                while ( $loop->have_posts() ) : $loop->the_post();
                    get_template_part( 'template-parts/content', 'page-events' );
//                the_title();
//                echo '<div class="entry-content">';
//                    the_content();
//                    echo '</div>';
                endwhile;// End of the loop.
                ?>


            </main><!-- #main -->
        </div><!-- #primary -->
    </div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>