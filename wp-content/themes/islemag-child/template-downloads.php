<?php

/**
 * Template name: Downloads
 */
get_header(); ?>
    <div class="container">
        <div class="row">
            <div id="primary" class="content-area">
                <div class="islemag-content-left col-md-12">
                    <main id="main" class="site-main" role="main">

                        <?php
                        $args = array(  'post_type' => 'downloads',
                            'posts_per_page' => 10
                        );
                        $loop = new WP_Query( $args );
                        while ( $loop->have_posts() ) : $loop->the_post();
                            get_template_part('template-parts/content', 'page');


                        endwhile;
                        ?>

                    </main><!-- #main -->
                </div><!-- #primary -->
            </div><!-- #primary -->
        </div>
    </div>
<?php get_footer(); ?>