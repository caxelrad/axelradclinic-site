<?php


function ax_mercury_load_app($atts)
{
  if ($atts['name'])
  {
    //AxLogger::set_enabled(true);
    $name = $atts['name'];

    //_ax_debug('app name is '.$name);
    
    $attrs = [];

    foreach ($atts as $key => $value)
    {
      if ($key != 'name')
        $attrs[$key] = $value;
    }

    //load the special styles, etc.

    AxelradUtil::append_css( 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css',  ['location' => 'footer']);
    AxelradUtil::append_css( 'https://use.fontawesome.com/releases/v5.7.2/css/all.css',  ['location' => 'footer']);    
    AxelradUtil::append_script_src( 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js',  ['location' => 'footer']);

    return AxelradMercuryLoader::run_app($name, $attrs);
  }
  else
    return 'no app name specified.';
}

add_shortcode('ax_mercury_app', 'ax_mercury_load_app');