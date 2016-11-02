<?php
/**
 * Template name: FrontPage
 */
get_header(); ?>

    <div id="primary" class="content-area">
        <div class="islemag-content-left col-md-12">
            <main id="main" class="site-main" role="main">
                <div class="container">
                    <div class="row">
               <div class="col-md-4 blog">
                   <h1 class="text-center">FOLLOW OUR BLOG</h1>
                   <?php
                   $latest_blog_posts = new WP_Query( array( 'posts_per_page' => 1 ) );

                   if ( $latest_blog_posts->have_posts() ) : while ( $latest_blog_posts->have_posts() ) : $latest_blog_posts->the_post();
                   // Loop output goes here
                       get_template_part( 'template-parts/content', get_post_format() );
                    endwhile;
                   endif;
                   ?>


               </div>
                <div class="col-md-4 events">
                    <h1 class="text-center">UPCOMING EVENTS</h1>
                    <?php
                    $args = array(  'post_type' => 'events',
                        'posts_per_page' => 1
                    );
                    $loop = new WP_Query( $args );
                    while ( $loop->have_posts() ) : $loop->the_post();
                        get_template_part('template-parts/content', get_post_format());

                    endwhile;// End of the loop.
                    ?>
                </div>
                <div class="col-md-4 downloads">
                    <h1 class="text-center">DOWNLOADS</h1>
                    <?php
                    $args = array(  'post_type' => 'downloads',
                        'posts_per_page' => 1
                    );
                    $loop = new WP_Query( $args );
                    while ( $loop->have_posts() ) : $loop->the_post();
                        get_template_part('template-parts/content', get_post_format());

                    endwhile;// End of the loop.
                    ?>

                </div>
                        </div>
                    </div>
                        <div class="container">
                            <div class="row">
                <div class="col-md-4">
                    <h1 class="text-center">
                        KNOW YOUR RIGHTS
                    </h1>
                    </div>
                                </div>
                            </div>
                </div>
            </main><!-- #main -->
        </div><!-- #primary -->
    </div><!-- #primary -->
    </div>
</div>
<?php get_footer(); ?>
