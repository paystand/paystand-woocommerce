<?php
/*
Plugin Name: WooCommerce-PayStand
Plugin URI: http://www.paystand.com/
Description: Adds PayStand payment gateway to WooCommerce.
Version: 1.0.0
Author: PayStand
Author URI: http://www.paystand.com/
*/

/*
Copyright 2014 PayStand Inc.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

if (!function_exists('add_action')) {
  echo 'No direct access.';
  exit;
}


function init_paystand_gateway_class() {
  include_once('class-wc-gateway-paystand.php');
}
add_action('plugins_loaded', 'init_paystand_gateway_class');


function add_paystand_gateway_class($methods) {
  $methods[] = 'WC_Gateway_PayStand'; 
  return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_paystand_gateway_class');


function paystand_menu() {
  add_options_page('paystand plugin options', 'Paystand', 'manage_options', 'paystand-options', 'paystand_options_page');
  add_menu_page('Paystand Console', 'Paystand', 'edit_posts', 'paystand_console', 'paystand_console', plugins_url('media/paystand.png', __FILE__));
}
add_action('admin_menu', 'paystand_menu');


function paystand_console() {
  if (!current_user_can('edit_posts'))  {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  $domain = apply_filters('paystand_config_domain', paystand_get_display_url(get_option('home')));
  ?>
  <style type="text/css">
    #wpbody-content { height:100%; }
    #paystand-iframe {
      padding: 10px 0;
      min-height: 640px;
    }
  </style>
  <?php
    $iframe_url = add_query_arg(array(
      'url' => paystand_get_display_url($domain),
      'api_key' => get_option('paystand_apikey'),
    ), '//paystand.com/dashboard/' );
    ?>
    <iframe id="paystand-iframe" width="100%" height="100%" src="<?php echo esc_url( $iframe_url ); ?>"></iframe>
    <?php
  } else {
    $iframe_url = add_query_arg( array(
      'url' => paystand_get_display_url( $domain ),
      'k' => get_option( 'paystand_apikey' ),
      'slim' => 1,
    ), '//paystand.com/publishing/dashboard/' );
    ?>
    <iframe id="paystand-iframe"width="100%" height="100%" src="<?php echo esc_url( $iframe_url ); ?>"></iframe>
  <?php
  }
}


function paystand_options_page() {
  $domain = apply_filters( 'paystand_config_domain', paystand_get_display_url (get_option('home')) );
  ?>
  <div class="wrap">
    <h2>Paystand</h2>
    <form method="post" action="options.php" onsubmit="buildOptions()">
      <?php
      // outputs all of the hidden fields that options.php will check, including the nonce
      wp_nonce_field('update-options');
      settings_fields('paystand-options'); ?>

      <script>
      function showSettings() {
        window.open('//paystand.com/wordpress/?site=' + encodeURIComponent(window.location.host));
      }
      </script>
      To enable tracking, you must enter your paystand account id. <a href="#" onclick="showSettings()">Find yours.</a> <br />
      <table class="form-table">
        <tr>
          <th scope="row">Account ID</th>
          <td><input size="30" type="text" name="paystand_userid"
            value="<?php echo esc_attr( get_option('paystand_userid') ); ?>" />
          </td>
        </tr>

        <tr>
          <th scope="row"><?php _e('Track visits by Site Admins?','paystand'); ?><br />
            <small>Administrators must be logged in to avoid tracking.</small>
          </th>
          <td>
            <input type="radio" name="paystand_trackadmins" value="1" <?php checked( get_option('paystand_trackadmins'), 1 ); ?> />
            Yes
            <input type="radio" name="paystand_trackadmins" value="0" <?php checked( get_option('paystand_trackadmins'), 0 ); ?> />
            No
          </td>
        </tr>
  
        <tr>
          <th scope="row"><?php _e('Enable Paystand Publishing?','paystand'); ?><br /> <small>Sign
              up for <a href="http://paystand.com/publishing/">Paystand Publishing</a>.
          </small></th>
          <td>
            <input type="radio" name="paystand_enable_newsbeat" value="1" <?php checked( get_option('paystand_enable_newsbeat'), 1 ); ?> />
            Yes
            <input type="radio" name="paystand_enable_newsbeat" value="0" <?php checked( get_option('paystand_enable_newsbeat'), 0 ); ?> />
            No
          </td>
        </tr>
        <tr>
          <th scope="row">API Key<br/>
          <?php if (get_option('paystand_enable_newsbeat')) { ?>
            <small>Get API key <a href="http://paystand.com/newsbeat/settings/api-keys/">here</a></small>
          <?php } else { ?>
            <small>Get API key <a href="http://paystand.com/apikeys/">here</a></small>
          <?php } ?>
          </th>
          <td><input size="30" type="text" name="paystand_apikey" value="<?php echo esc_attr( get_option('paystand_apikey') ); ?>" />
          </td>
        </tr>
  
      </table>
      <br /> <br />

      <script src="<?php echo plugins_url('media/topwidget.compiled.js', __FILE__); ?>" type="text/javascript"></script>
      <script type="text/javascript"> 
      var themes = { 'doe':   { 'bgcolor': '', 'border': '#dde7d4', 'text': '#555' },
        'gray':  { 'bgcolor': '#e3e3e3', 'border': '#333333', 'text': '#555', 'header_bgcolor': '#999999', 'header_color': '#fff' },
        'red':   { 'bgcolor': '#ffffff', 'border': '#cc3300', 'text': '#555', 'header_bgcolor': '#f5c5be', 'header_color': '#fff' },
        'blue':  { 'bgcolor': '#e0ecff', 'border': '#3a5db0' },
        'green': { 'bgcolor': '#c9edcc', 'border': '#69c17d', 'text': '#555' } };
      var theme = 'doe';
      var limit = 10;


      function changeTheme(select) {
        theme = select.options[select.selectedIndex].value;
        renderWidget();
      }


      function changeLimit(select) {
        limit = select.options[select.selectedIndex].value;
        renderWidget();
      }


      function renderWidget() {
        new CBTopPagesWidget( '<?php echo esc_js(get_option('paystand_apikey')) ?>',
                   { 'host': '<?php echo esc_js( paystand_get_display_url( $domain ) ); ?>',
                   'background': themes[theme]['bgcolor'],
                   'border': themes[theme]['border'],
                   'header_bgcolor': themes[theme]['header_bgcolor'],
                   'header_color': themes[theme]['header_color'],
                   'text': themes[theme]['text'],
                   'limit': limit });
      }


      function addOption(array, key, val) {
        array.push("'" + key + "': '" + val + "'");
      }


      function buildOptions() {
        var options = [];
        addOption(options, 'background', themes[theme]['bgcolor']);
        addOption(options, 'border', themes[theme]['border']);
        addOption(options, 'header_bgcolor', themes[theme]['header_bgcolor']);
        addOption(options, 'header_color', themes[theme]['header_color']);
        addOption(options, 'text', themes[theme]['text']);
        addOption(options, 'limit', limit);
        options = '{' + options.join(',') + '}';
        document.getElementById('paystand_widgetconfig').value = options;
        console.debug("options:" + options);
      }
      renderWidget();
      </script>

      If your theme supports it, you can also add a widget under
      <tt>Appearance > Widgets</tt>
      to show where users currently are on your site. <br> <br>
      <table cellspacing="10">
        <tr>
          <td valign="top">Number of pages to show
            <select name="metric" id="toplimit" onChange="changeLimit(this);">
              <option value="5">5</option>
              <option value="10" selected="selected">10</option>
              <option value="20">20</option>
              <option value="30">30</option>
            </select>
            <br /> <br />

            Color scheme
            <select name="theme" id="toptheme" onChange="changeTheme(this);">
              <option value="doe">John Doe</option>
              <option value="gray">Dorian Gray</option>
              <option value="red">Red Rum</option>
              <option value="blue">Blue Moon</option>
              <option value="green">Green Giant</option>
            </select>
          </td>
          <td>&nbsp;</td>
          <td>
            Sample:
            <br> <br>
            <div id="cb_top_pages"></div>
          </td>
        </tr>
      </table>
      <input type="hidden" id="paystand_widgetconfig" name="paystand_widgetconfig" value="{}" />

      <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
      </p>
    </form>
  </div>
<?php
}


// Function to register settings and sanitize output. To be called later in add_action
function paystand_register_settings() {
  register_setting('paystand-options','paystand_userid', 'intval');
  register_setting('paystand-options','paystand_apikey','paystand_is_validmd5');
  register_setting('paystand-options','paystand_widgetconfig','paystand_is_validjson');
  register_setting('paystand-options','paystand_trackadmins','intval'); // add trackadmin setting
  register_setting('paystand-options','paystand_enable_newsbeat','intval');
}


function paystand_is_validmd5($md5) {
  if( !empty($md5) && preg_match('/^[a-f0-9]{32}$/', $md5) ) { 
    return $md5;
  } else {
    add_settings_error( 'paystand_apikey','invalid_apikey','API Key is not correct, please check again','error');
    return 777;
  }
}


function paystand_is_validjson($json_str) {
  if( is_null(json_decode($json_str)) ) { 
    return $json_str; 
  } else {
    add_settings_error( 'paystand_widgetconfig','invalid_widgetconfig','Widget config is malformed','error');
  }
}


function add_paystand_head() {
  echo "\n<script type=\"text/javascript\">var _sf_startpt=(new Date()).getTime()</script>\n";
}


function add_paystand_footer() {
  $user_id = get_option('paystand_userid');
  if ($user_id) {
    // if visitor is admin AND tracking is off, do not load paystand
    if ( current_user_can( 'manage_options') && get_option('paystand_trackadmins') == 0)
      return;

    if ( apply_filters( 'paystand_config_use_canonical', true ) )
      $use_canonical = 'true';
    else
      $use_canonical = 'false';
    ?>

    <!-- /// LOAD CHARTBEAT /// -->
    <script type="text/javascript">
    var _sf_async_config={};
    _sf_async_config.uid = <?php echo intval( $user_id ); ?>;
    _sf_async_config.useCanonical = <?php echo $use_canonical; ?>;
    <?php
    $enable_newsbeat = get_option('paystand_enable_newsbeat');
    $domain = apply_filters( 'paystand_config_domain', paystand_get_display_url (get_option('home')) );
    if ($enable_newsbeat) { ?>
      _sf_async_config.domain = '<?php echo esc_js( $domain ); ?>';
      <?php 
      // Only add these values on blog posts use the queried object in case there
      // are multiple Loops on the page.
      if (is_single()) {
        $post = get_queried_object();

        // Use the author's display name 
        $author = get_the_author_meta('display_name', $post->post_author);
        $author = apply_filters( 'paystand_config_author', $author );
        printf( "_sf_async_config.authors = '%s';\n", esc_js( $author ) );
      
        // Use the post's categories as sections
        $cats = get_the_terms($post->ID, 'category');

        if ($cats) {
          $cat_names = array();
          foreach ( $cats as $cat ) {
            $cat_names[] = $cat->name;
          }
        }

        $cat_names = (array)apply_filters( 'paystand_config_sections', $cat_names );
        if ( count( $cat_names ) ) {
          foreach( $cat_names as $index => $name ) {
            $cat_names[ $index ] = '"' . esc_js( $name ) . '"';
          }
          
          printf( "_sf_async_config.sections = [%s];\n", implode( ', ', $cat_names ) );
        }
      }
    } // if $enable_newsbeat
    ?>

    (function(){
      function loadPaystand() {
      window._sf_endpt=(new Date()).getTime();
      var e = document.createElement('script');
      e.setAttribute('language', 'javascript');
      e.setAttribute('type', 'text/javascript');
      e.setAttribute('src',
         (("https:" == document.location.protocol) ? "https://" : "http://") +
         "static.paystand.com/js/paystand.js");
      document.body.appendChild(e);
      }
      var oldonload = window.onload;
      window.onload = (typeof window.onload != 'function') ?
       loadPaystand : function() { try { oldonload(); } catch (e) { loadPaystand(); throw e} loadPaystand(); };
    })();
    </script>
    <?php
  }
}


class Paystand_Widget extends WP_Widget {

  function __construct() {
        parent::__construct('paystand_widget', 'Paystand Widget',array( 'description' => __('Display your site\'s top pages')));
        
        if ( is_active_widget(false,false,$this->id_base,true) || is_admin() ) {
          wp_enqueue_script( 'paystand_topwidget', plugins_url('media/topwidget.compiled.js', __FILE__) );
          wp_localize_script( 'paystand_topwidget', 'cbproxy', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'cbnonce' => wp_create_nonce( 'cbproxy-nonce' ) ) );
        }
    }

  function widget( $args ) {
    extract( $args );
    echo $before_widget;
    
    $api_key = get_option( 'paystand_apikey' );
    $widget_config = get_option('paystand_widgetconfig');
    
    if ( $api_key ) : ?>
    <div id="cb_top_pages"></div>
    <script type="text/javascript">
    var options = { };
    new CBTopPagesWidget( <?php echo get_option('paystand_widgetconfig'); ?> );
    </script>
    <?php
    endif;
    echo $after_widget;
  }
}

// Add proxy for Paystand API requests
add_action( 'wp_ajax_nopriv_cbproxy-submit', 'cbproxy_submit' );
add_action( 'wp_ajax_cbproxy-submit', 'cbproxy_submit' );

function cbproxy_submit() {
  // check nonce
  $nonce = $_GET['cbnonce'];
  if ( ! wp_verify_nonce( $nonce, 'cbproxy-nonce' ) ) die ( 'cbproxy-nonce failed!');
  $domain = apply_filters( 'paystand_config_domain', paystand_get_display_url (get_option('home')) );
  $url = 'http://api.paystand.com';
  $url .= $_GET['url'];
  $url .= '&host=' . paystand_get_display_url(esc_js($domain)) .'&apikey=' . get_option('paystand_apikey');
  $transient = 'cbproxy_' . md5($url);
  header( 'Content-Type: application/json' );
  $response = get_transient( $transient );
  if ( !$response ) {
    $get = wp_remote_get( $url , array( 'timeout' => 3 ) );
    if( is_wp_error( $response ) ) {
      $response = json_encode( array( 'error' => $get->get_error_message() ) );
    } else {
      $response = wp_remote_retrieve_body($get);
    }
    set_transient($transient,$response,5);
  }
  
  echo $response;
  exit;
}

// Dashboard Widget
function paystand_widget_init() {
  register_widget( 'Paystand_Widget' );
}

add_action('widgets_init', 'paystand_widget_init');

function paystand_get_display_url( $url ){
  return strtok(preg_replace("/(https?:\/\/)?(www\.)?/i","",$url),"/");
}

function paystand_dashboard_widget_function() {
  ?>
  <div id="paystandGauge"></div>
  <div id="paystandRefsTable" class="paystandWidget">
    <table id="paystandLinks" class="paystandTable">
      <thead>
        <tr>
          <th colspan=2 class="paystandLabel">Top Referrers</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
    <table id="paystandSearch" class="paystandTable">
      <thead>
        <tr>
          <th colspan=2 class="paystandLabel">Top Search</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
    <div class="clear"></div>
  </div>
  <div id="paystandGraph" class="paystandWidget clear">
    <div class="paystandLabel">Visits - Last 3 Day</div>
    <div id="paystandHist">
      <div id="annotations"></div>
    </div>
  </div>
  <script type="text/javascript">
  <?php add_filter( 'posts_where', 'paystand_filter_where_last_three_days' ); ?>
  var events = [];
  // Get published post Events 
  <?php
  $args = array( 'post_type' => array( 'post' ),'post_status' => 'publish', 'orderby' => 'date', 'order' => 'ASC' );
  $the_query = new WP_Query( $args );
  $domain = apply_filters( 'paystand_config_domain', paystand_get_display_url (get_option('home')) );
  while ( $the_query->have_posts() ) : $the_query->the_post(); 
    $tstamp = get_the_time('Y,n-1,j,G,i');
    if ($tstamps[$tstamp])
      continue; 

    $tstamps[$tstamp] = true;
    $category = get_the_category();
    if($category[0])
      $category_link = get_category_link($category[0]->cat_ID );

    ?>var ev = {domain:'<?php echo esc_js( paystand_get_display_url( $domain ) );?>',title:'<?php echo esc_js( get_the_title() ); ?>',
      value:'<?php echo esc_js( paystand_get_display_url( $category_link ) ); ?>', group_name:'<?php echo esc_js( paystand_get_display_url( get_page_link() ) ); ?>',
      t: new Date(<?php echo the_time('Y,n-1,j,G,i'); ?>).getTime()/1000,group_type:'page',num_referrers:10,id:'<?php echo esc_js( get_the_ID() ); ?>',type:'wp',data:{action_type:"create"}};
    events.push(ev);
  <?php
  endwhile;
  wp_reset_postdata();?>

  // Get revisions
  <?php
  $args = array( 'post_type' => array( 'revision' ), 'post_status' => 'inherit', 'orderby' => 'date', 'order' => 'ASC' );
  $the_query = new WP_Query( $args );
  while( $the_query->have_posts() ) : $the_query->the_post();
    $tstamp = get_the_time('Y,n-1,j,G,i');
    if ($tstamps[$tstamp])
      continue;

    $tstamps[$tstamp] = true;
    $category = get_the_category();
    if($category[0])
      $category_link = get_category_link($category[0]->cat_ID );

    ?>var ev = {domain:'<?php echo esc_js( paystand_get_display_url( $domain ) ); ?>',title:'<?php echo esc_js( get_the_title() ); ?>',
      value:'<?php echo esc_js( paystand_get_display_url( $category_link ) ); ?>',group_name:'<?php echo esc_js( paystand_get_display_url( get_page_link() ) ); ?>',
      t: new Date(<?php echo the_time('Y,n-1,j,G,i'); ?>).getTime()/1000,group_type:'page',num_referrers:10,id:'<?php echo esc_js( get_the_ID() ); ?>',type:'wp',data:{action_type:"update"}};
    events.push(ev);
  <?php
  endwhile; 
  wp_reset_postdata();

  remove_filter( 'posts_where', 'paystand_filter_where_last_three_days' );
  ?>

  function loadPayStandWidgets(){
    new CBDashboard('paystandGauge','paystandRefsTable','paystandHist',200,"<?php echo esc_js( paystand_get_display_url( $domain ) ); ?>","<?php echo esc_js( get_option('paystand_apikey') ); ?>",events);
  };
  
  var currOnload = window.onload;
  window.onload = (typeof window.onload != 'function') ? loadPayStandWidgets : function() { oldonload(); loadPayStandWidgets(); };
  </script>
  <?php
}

// Create a new filtering function that will add our where clause to the query
function paystand_filter_where_last_three_days( $where = '' ) {
  global $wpdb;
  $where .= $wpdb->prepare( " AND $wpdb->posts.post_modified > %s", date( 'Y-m-d', strtotime( '-3 days' ) ) );
  return $where;
}

function paystand_add_dashboard_widgets() {
  // Don't add widgets if we haven't set up Paystand yet
  if ( ! get_option('paystand_userid') )
    return;

  wp_enqueue_style( 'cbplugin_css' );
  // wp_enqueue_script( 'closure' );
  // wp_enqueue_script( 'deps' );
  wp_enqueue_script( 'cbdashboard' );
  wp_add_dashboard_widget('paystand_dashboard_widget', 'Paystand', 'paystand_dashboard_widget_function');
}

function paystand_plugin_admin_init() {
  wp_register_style('cbplugin_css',plugins_url('media/cb_plugin.css', __FILE__));
  // wp_register_script( 'closure','http://local.paystand.com/paystand/frontend/js/closure-library-read-only/closure/goog/base.js');
  // wp_register_script( 'deps','http://local.paystand.com/paystand/frontend/js/deps.js');
  // wp_register_script( 'cbdashboard','http://local.paystand.com/paystand/frontend/js/cmswidgets/cbdashboard.js');

  wp_register_script( 'cbdashboard',plugins_url('media/cbdashboard.compiled.js', __FILE__));
}

add_action('wp_dashboard_setup', 'paystand_add_dashboard_widgets' );
add_action( 'admin_init', 'paystand_plugin_admin_init' );

// Add Column to Posts Manager
add_filter('manage_posts_columns', 'paystand_columns');
function paystand_columns($defaults) {
  if ( ! get_option('paystand_userid') )
    return $defaults;

  $defaults['cb_visits'] = __('Active Visits');
  return $defaults;
}
add_action('manage_posts_custom_column', 'paystand_custom_columns', 10, 2);

function paystand_custom_columns($column_name, $id) {
  $domain = apply_filters( 'paystand_config_domain', paystand_get_display_url (get_option('home')) );
  if( $column_name == 'cb_visits' ) {
    $post_url = parse_url(get_permalink( $id ));
    $json_url = add_query_arg( array(
      'host' => paystand_get_display_url( $domain ),
      'apikey' => get_option('paystand_apikey'),
      'path' => urlencode( $post_url["path"] ),
    ), '//api.paystand.com/live/quickstats/' );
    ?>
  
    <script type="text/javascript">
    jQuery.getJSON('<?php echo esc_js( $json_url ); ?>',
      function(data) {
        if ( !data.visits ) data.visits = 0;
        jQuery('#post-<?php echo $id; ?> .cb_visits').append(data.visits);
      }
    );
    </script>
    <?php
  }
}

// Returns URL for asset on the static.paystand domain
function paystand_get_static_asset_url( $path = '' ) {
  $domain = (is_ssl() ? 'https' : 'http') . '://static.paystand.com/';
  return $domain . ltrim( $path, '/' );
}

// If admin register settings on page that have been saved
// if not, add content to wp_head and wp_footer.
if ( is_admin() ){
  add_action( 'admin_init', 'paystand_register_settings' );
}else {
  add_action('wp_head', 'add_paystand_head');
  add_action('wp_footer', 'add_paystand_footer');
}

?>
