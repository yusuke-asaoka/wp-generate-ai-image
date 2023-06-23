<?php 
/*
Plugin Name: Generate AI Image
Plugin URI: https://example.com/
Description: This plugin generates AI images using openai generate image api.
Version: 1.0
Author: YUSUKE ASAOKA
Author URI: https://cinca.dev
License: GPLv2 or later
*/

class Generate_Ai_Image_Plugin {

    public function __construct() {
        $this->initialize();
    }
    

    private function initialize() {
        add_action('admin_menu', array($this, 'add_plugin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_scripts() {
      wp_register_script(
          'generate-ai-image',
          plugin_dir_url(__FILE__) . 'generate-ai-image.js',
          array('jquery'),
          '1.0',
          true
      );

      wp_localize_script(
        'generate-ai-image',
        'GenerateAiImageSettings',
        array(
            'api_key' => get_option('generate_ai_image_api_key'),
            'image_size' => get_option('generate_ai_image_size')
            

        )
      );
  
      if (isset($_GET['page']) && $_GET['page'] === 'generate-ai-image') {
          wp_enqueue_script('generate-ai-image');
      }
    }

    public function enqueue_styles() {
      wp_enqueue_style(
          'generate-ai-image-styles',
          plugin_dir_url(__FILE__) . 'generate-ai-image.css',
          array(),
          '1.0'
      );
  
      if (isset($_GET['page']) && $_GET['page'] === 'generate-ai-image') {
          wp_enqueue_style('generate-ai-image-styles');
      }
    }
    
    public function add_plugin_menu() {
      add_menu_page(
          'Generate AI Image',
          'Generate AI Image',
          'manage_options',
          'generate-ai-image',
          array($this, 'render_generate_page') 
      );
      

      add_submenu_page(
          'generate-ai-image',
          'Setting',
          'Setting',
          'manage_options',
          'generate-ai-image-settings',
          array($this, 'render_settings_page')
      );
    }
  

    // generate page
    public function render_generate_page() {

      if (isset($_POST['generate_ai_image_save_submit']) && wp_verify_nonce($_POST['generate_ai_image_nonce'], 'generate_ai_image')) {
        $base64_image = $_POST['image_src'];
        if ($base64_image != '') {
          $image_data = base64_decode($base64_image);
        
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          $mime_type = finfo_buffer($finfo, $image_data);
          
          $upload_dir = wp_upload_dir();
          
          $timestamp = date('YmdHis');
          $filename = wp_unique_filename($upload_dir['path'], 'ai_image_'.$timestamp.'.jpg');
          $filepath = $upload_dir['path'] . '/' . $filename;
          file_put_contents($filepath, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64_image)));
          
          $attachment = array(
            'post_mime_type' => $mime_type,
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
          );
          $attach_id = wp_insert_attachment($attachment, $filepath);
          $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
          wp_update_attachment_metadata($attach_id, $attach_data);
          echo '<div class="notice notice-success"><p>Image uploaded successfully!</p></div>';
        } else {
          echo '<div class="notice notice-success"><p>Image upload failed.</p></div>';
        }

        //die();
        
      }

      $nonce = wp_create_nonce('generate_ai_image');

      echo <<<HTML
    <div>
        <div id="loading" class="loading"></div>
        <h1>Generate Image</h1>
        <h2><label for="generate_ai_image_text">Please enter text.</label></h2>
        <p><input type="text" name="generate_ai_image_text" id="generate_ai_image_text" value="" placeholder="a white siamese cat" style="max-width:1000px;width: 100%;" /></p> 
        <p id="error_msg" style="color:red;"></p>
        <p><input id="generate_ai_image_submit" type="submit" name="generate_ai_image_submit" value="GENERATE" class="button-primary" /></p> 
    </div>
    
    <form method="post" action="">
      <div id="preview"></div>
      <input type="hidden" name="image_src" id="image_src" value="" />
      <p id="generate_ai_image_btns" style="display:none;">
        <input id="generate_ai_image_save_submit" type="submit" name="generate_ai_image_save_submit" value="SAVE MEDIA GALLERY" class="button-primary"/>
        <a name="generate_ai_image_submit" class="button-secondary">REGENERATE</a>
      </p>
      <input type="hidden" name="generate_ai_image_nonce" value="{$nonce}" />
    </form>
HTML;
    }

    // setting page
    public function render_settings_page() {
      
      if (isset($_POST['generate_ai_image_settings_submit']) && wp_verify_nonce($_POST['generate_ai_image_settings_nonce'], 'generate_ai_image_settings')) {

        $api_key = sanitize_text_field($_POST['generate_ai_image_api_key']);
        $size = isset($_POST['generate_ai_image_size']) ? sanitize_text_field($_POST['generate_ai_image_size']) : '512x512';

        update_option('generate_ai_image_api_key', $api_key);
        update_option('generate_ai_image_size', $size);

        echo '<div class="notice notice-success"><p>Settings have been saved.</p></div>';
    }

      $api_key = get_option('generate_ai_image_api_key');
      $lastFour = mb_substr($api_key, -4);
      $rest = mb_substr($api_key, 0, -4);
      $masked = str_repeat("*", mb_strlen($rest)) . $lastFour;
      $size = get_option('generate_ai_image_size', 'medium') ?  get_option('generate_ai_image_size', 'medium') : "512x512";
      
      
      $size_256x256_checked = ($size === '256x256') ? 'checked="checked"' : '';
      $size_512x512_checked = ($size === '512x512') ? 'checked="checked"' : '';
      $size_1024x1024_checked = ($size === '1024x1024') ? 'checked="checked"' : '';
      $nonce = wp_create_nonce('generate_ai_image_settings');

      echo <<<HTML
      <div>
          <h1>API SETTING</h1>
          <form method="post" action="">
              <h2>API KEY</h2>
              <p><input type="text" name="generate_ai_image_api_key" value="{$masked}" size="55" /></p>
              <p>Please obtain the API KEY from your <a href="https://openai.com/" target="_blank" rel="nofollow">OpenAI account.</a></p>
              <h2>SIZE</h2>
              <label><input type="radio" name="generate_ai_image_size" value="256x256" {$size_256x256_checked} /> 256x256</label><br />
              <label><input type="radio" name="generate_ai_image_size" value="512x512" {$size_512x512_checked} /> 512x512</label><br />
              <label><input type="radio" name="generate_ai_image_size" value="1024x1024" {$size_1024x1024_checked} /> 1024x1024</label><br />
              <p>Please confirm the generation fees for each size on the <a href="https://openai.com/pricing" target="_blank" rel="nofollow">OpenAI Pricing page.</a></p>
              <p><input type="submit" name="generate_ai_image_settings_submit" value="SAVE" class="button-primary" /></p>
              <input type="hidden" name="generate_ai_image_settings_nonce" value="{$nonce}" />
          </form>
      </div>
      
HTML;
    }

}

new Generate_Ai_Image_Plugin();
