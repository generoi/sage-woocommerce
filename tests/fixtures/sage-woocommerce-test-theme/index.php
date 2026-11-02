<!doctype html>
<html <?php language_attributes(); ?>>
  <head>
    <?php wp_head(); ?>
  </head>
  <body <?php body_class(); ?>>
    <?php echo \Roots\view(\Roots\app('sage.view'), \Roots\app('sage.data'))->render(); ?>
    <?php wp_footer(); ?>
  </body>
</html>
