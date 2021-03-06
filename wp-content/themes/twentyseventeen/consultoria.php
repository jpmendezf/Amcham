<?php /* Template Name: Consultoria */ 
get_header(); ?>
<div class="container bread">
	<div class="cont-bread sobre-amcham">
		<a class="home" href="<?php echo get_home_url(); ?>"><?php $currentlang = get_bloginfo('language'); if($currentlang=="en-US"):?>Home<?php else: ?>Inicio<?php endif; ?></a>
		<span class="slash">/</span>
		<div class="home"><?php $currentlang = get_bloginfo('language'); if($currentlang=="en-US"):?>Services<?php else: ?>Servicios<?php endif; ?></div>
		<span class="slash">/</span>
		<a class="home" href="<?php echo get_home_url(); ?>/trade-center/">Trade Center</a>
		<span class="slash">/</span>
		<div class="home"><?php the_title(); ?></div>
	</div>
</div>
<div id="primary" class="content-area">
	<div class="banner">
		<?php echo get_the_post_thumbnail( get_the_ID() , 'full' );	?>
		<div class="containertits">
		 	<h1 class="tit1 titulo-light tit-light-margin text-largo"><?php echo get_post_meta($post->ID, 'titulobanner1', true); ?></h1>
		 	<h1 class="titulo-bold-dos text-largo"><?php echo get_post_meta($post->ID, 'titulobanner2', true); ?></h1>
		</div>	
		<div class="containerbanner cajablanca padding1yj ancho-caja-consultoria">
		 	<?php echo get_post_meta($post->ID, 'textobanner', true); ?>		 	
		</div> 
	</div>
</div> 
<div class="container consultoria-img-mujer">
	<img src="http://142.93.201.64/Amcham/wp-content/uploads/2019/08/Grupo-2753.png">
</div>
<!-- <div class="fondoblanco"> -->
<div class="container boxconsultoria">
	<?php   
      while ( have_posts() ) : the_post();
        the_content();
      endwhile; // End of the loop.
    ?>
</div>
<!-- </div> -->
<!-- <div class="container">
	<div class="center margintit">
		<h3>Descargar gratis el perfil de estado de:</h3>
		<div class="linea-roja"></div>
		<div>Consultoría sobre internalización de Empresa en USA</div>
	</div>

</div> -->


<?php get_footer();