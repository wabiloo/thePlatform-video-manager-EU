<?php
/* thePlatform Video Manager Wordpress Plugin
  Copyright (C) 2013-2014  thePlatform for Media Inc.

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License along
  with this program; if not, write to the Free Software Foundation, Inc.,
  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA. */

/*
 * Load scripts and styles 
 */
wp_print_scripts( 'mediaview_js' );
wp_print_scripts( 'jquery-ui-dialog' );
wp_print_styles( 'dashicons' );
wp_print_styles( 'bootstrap_tp_css' );
wp_print_styles( 'theplatform_css' );
wp_print_styles( 'wp-jquery-ui-dialog' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>

		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="tp:EnableExternalController" content="true" />
		<?php
		if ( !defined( 'ABSPATH' ) ) {
			exit;
		}
		$tp_viewer_cap = apply_filters( 'tp_viewer_cap', 'edit_posts' );
		if ( !current_user_can( $tp_viewer_cap ) ) {
			wp_die( '<p>' . __( 'You do not have sufficient permissions to browse MPX Media' ) . '</p>' );
		}

		if ( !class_exists( 'ThePlatform_API' ) ) {
			require_once( dirname( __FILE__ ) . '/thePlatform-API.php' );
		}

		$tp_api = new ThePlatform_API;
		$metadata = $tp_api->get_metadata_fields();

		define( 'TP_MEDIA_BROWSER', true );

		$metadata_options = get_option( 'theplatform_metadata_options' );
		$upload_options = get_option( 'theplatform_upload_options' );
		$preferences = get_option( 'theplatform_preferences_options' );

		if ( strcmp( $preferences['mpx_account_id'], "" ) == 0 ) {
			wp_die( 'MPX Account ID is not set, please configure the plugin before attempting to manage media' );
		}

		//Embed only stuff
		$players = $tp_api->get_players();
		$IS_EMBED = strpos( $_SERVER['QUERY_STRING'], '&embed=true' ) !== false ? true : false;

		function writePlayers( $players, $preferences ) {
			$html = '<p class="navbar-text sort-bar-text">Player:</p><form class="navbar-form navbar-left sort-bar-nav" role="sort"><select id="selectpick-player" class="form-control">';
			foreach ( $players as $player ) {
				$html .= '<option value="' . esc_attr( $player['pid'] ) . '"' . selected( $player['pid'], $preferences['default_player_pid'], false ) . '>' . esc_html( $player['title'] ) . '</option>';
			}
			$html .= '</select></form>';
			echo $html;
		}
		?>

		<script type="text/javascript">
			tpHelper = { };
			tpHelper.token = "<?php echo $tp_api->mpx_signin(); ?>";
			tpHelper.account = "<?php echo $preferences['mpx_account_id']; ?>";
			tpHelper.accountPid = "<?php echo $preferences['mpx_account_pid']; ?>";
			tpHelper.isEmbed = "<?php echo $IS_EMBED; ?>";
		</script>

    </head>
    <body>
		<div class="tp">
			<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
				<div class="row">
					<div class="navbar-header" style="margin-left: 15px">
						<a class="navbar-brand" href="#"><img height="25px" width="25px" src="<?php echo plugins_url( '/images/embed_button.png', __FILE__ ); ?>"> thePlatform</a>
					</div>            
					<form class="navbar-form navbar-left" role="search" onsubmit="return false;"><!--TODO: Add seach functionality on Enter -->
						<div class="form-group">
							<input id="input-search" type="text" class="form-control" placeholder="Keywords">
						</div>
						<button id="btn-feed-preview" type="button" class="btn btn-default">Search</button>
					</form>

					<p class="navbar-text sort-bar-text">Sort:</p>
					<form class="navbar-form navbar-left sort-bar-nav" role="sort">
						<select id="selectpick-sort" class="form-control">
							<option>Added</option>
							<option>Title</option>
							<option>Updated</option>
						</select>
					</form>

					<div id="my-content" class="navbar-left">
						<p class="navbar-text sort-bar-text">
							<!-- Render My Content checkbox -->
							<?php if ( $preferences['user_id_customfield'] !== '(None)' ) { ?>
								<input type="checkbox" id="my-content-cb" <?php checked( $preferences['filter_by_user_id'] === 'TRUE' ); ?> />
								<label for="my-content-cb" style="font-weight: normal">My Content</label>													
							<?php } ?>
							<!-- End My Content Checkbox -->	
						</p>
					</div>
					<?php
					if ( $IS_EMBED ) {
						writePlayers( $players, $preferences );
					}
					?>    
					<img id="load-overlay" src="<?php echo plugins_url( '/images/loading.gif', __FILE__ ) ?>" class="loadimg navbar-right">
				</div>           
			</nav>

			<div class="fs-main">
				<div id="filter-container">
					<div id="filter-affix" class="scrollable affix-top">
						<div id="list-categories" class="list-group">
							<a class="list-group-item active">
								Categories
							</a>
							<a href="#" class="list-group-item cat-list-selector">All Videos</a>
						</div>                    
					</div>
				</div>

				<div id="content-container">
					<div id="message-panel"></div>
					<div id="media-list"></div>
				</div>
				<div id="info-container">
					<div id="info-affix" class="scrollable affix-top">
						<div id="info-player-container">
							<div id="modal-player" class="marketplacePlayer">
								<img id="modal-player-placeholder" data-src="holder.js/320x180/text:No Preview Available" src="" style="position: absolute"><!-- holder.js/128x72/text:No Thumbnail" -->
								<iframe id="player" width="320px" height="180px" frameBorder="0" seamless="seamless" src="<?php echo TP_API_PLAYER_EMBED_BASE_URL; ?><?php echo $preferences['mpx_account_pid'] . '/' . $preferences['default_player_pid']; ?>/embed?autoPlay=false"
										webkitallowfullscreen mozallowfullscreen msallowfullscreen allowfullscreen></iframe>
							</div>
							<br>
							<div id="panel-contentpane" class="panel panel-default">
								<div class="panel-heading">
									<h3 class="panel-title">Metadata</h3>
								</div>
								<div class="panel-body">
									<?php
									foreach ( $upload_options as $upload_field => $val ) {
										if ( $val == 'hide' ) {
											continue;
										}

										$field_title = (strstr( $upload_field, '$' ) !== false) ? substr( strstr( $upload_field, '$' ), 1 ) : $upload_field;
										$display_title = mb_convert_case( $field_title, MB_CASE_TITLE );

										//Custom names
										if ( $field_title === 'guid' ) {
											$display_title = 'Reference ID';
										}
										if ( $field_title === 'link' ) {
											$display_title = 'Related Link';
										}
										$html = '<div class="row">';
										$html .= '<strong>' . esc_html( $display_title ) . ': </strong>';
										$html .= '<span id="media-' . esc_attr( strtolower( $field_title ) ) . '"' . '" data-name="' . esc_attr( strtolower( $field_title ) ) . '"></span></div>';
										echo $html;
									}

									foreach ( $metadata_options as $custom_field => $val ) {
										if ( $val == 'hide' ) {
											continue;
										}

										$metadata_info = NULL;
										foreach ( $metadata as $entry ) {
											if ( array_search( $custom_field, $entry ) ) {
												$metadata_info = $entry;
												break;
											}
										}

										if ( is_null( $metadata_info ) ) {
											continue;
										}

										$field_title = $metadata_info['fieldName'];
										$field_prefix = $metadata_info['namespacePrefix'];
										$field_namespace = $metadata_info['namespace'];
										$field_type = $metadata_info['dataType'];
										$field_structure = $metadata_info['dataStructure'];

										if ( $field_title === $preferences['user_id_customfield'] ) {
											continue;
										}

										$html = '<div class="row">';
										$html .= '<strong>' . esc_html( mb_convert_case( $field_title, MB_CASE_TITLE ) ) . ': </strong>';
										$html .= '<span id="media-' . esc_attr( $field_title ) . '" data-type="' . esc_attr( $field_type ) . '" data-structure="' . esc_attr( $field_structure ) . '" data-name="' . esc_attr( $field_title ) . '" data-prefix="' . esc_attr( $field_prefix ) . '" data-namespace="' . esc_attr( $field_namespace ) . '"></span></div>';
										echo $html;
									}
									?>                      
								</div>
								<div id="btn-container">
									<?php if ( $IS_EMBED ) { ?>
										<button type="button" id="btn-embed" class="btn btn-primary btn-xs">Embed</button>
										<button type="button" id="btn-embed-close" class="btn btn-primary btn-xs">Embed and close</button>
										<button type="button" id="btn-set-image" class="btn btn-primary btn-xs">Set Featured Image</button>  
									<?php } else {
										?>
										<button type="button" id="btn-edit" class="btn btn-primary btn-xs">Edit Media</button>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		$tp_editor_cap = apply_filters( 'tp_editor_cap', 'upload_files' );
		if ( !$IS_EMBED && current_user_can( $tp_editor_cap ) ) {
			?>
			<div id="tp-edit-dialog" class="tp" style="display: none; padding-left:10px;">
				<h1> Edit Media </h1><div id="media-mpx-upload-form" class="tp">
					<?php require_once( dirname( __FILE__ ) . '/thePlatform-upload.php' ); ?>
				</div>
			<?php } ?>
    </body>

</html>