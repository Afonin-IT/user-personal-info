<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Дополнительные метаданные пользователя
 * Version:           1.0.0
 * Author:            Sergey Afonin
 */

include plugin_dir_path( __FILE__ ) . "Enc.class.php";

if ( ! defined( 'WPINC' ) ) {
	die;
}

function get_meta_keys_to_encrypt(){
    return [
		'personal_info_address',
		'personal_info_phone',
		'personal_info_gender',
		'personal_info_marriage'
	];
}

function get_private_key(){
	return file_get_contents(plugin_dir_path( __FILE__ ) . "keys/private.key");
}

function get_public_key(){
	return file_get_contents(plugin_dir_path( __FILE__ ) . "keys/public.key");
}

// INSERT FIELDS IN USER PROFILE
function insert_fields( $user ){
	$address = isset($user->ID) ? get_the_author_meta( 'personal_info_address', $user->ID) : "";
	$phone = isset($user->ID) ? get_the_author_meta( 'personal_info_phone', $user->ID) : "";
	$gender = isset($user->ID) ? get_the_author_meta('personal_info_gender', $user->ID) : "";
	$marriage = isset($user->ID) ? get_the_author_meta('personal_info_marriage', $user->ID) : "";

	?>
	<h2>Личная информация</h2>
	<table class="form-table" role="presentation">
		<tbody>
			<tr class="user-personal-info-address">
				<th>
					<label for="personal-info-address">Адрес</label>
				</th>
				<td>
					<input type="text" name="personal_info_address" id="personal-info-address" value="<?php echo $address ?>" class="regular-text">
				</td>
			</tr>
			<tr class="user-personal-info-phone">
				<th>
					<label for="personal-info-phone">Телефон</label>
				</th>
				<td>
					<input type="tel" name="personal_info_phone" id="personal-info-phone" value="<?php echo $phone ?>" class="regular-text">
				</td>
			</tr>
			<tr class="user-personal-info-gender">
				<th>
					<label for="personal-info-gender">Пол</label>
				</th>
				<td>
					<select name="personal_info_gender" id="personal-info-gender">
						<option value="" <?php if( empty($gender) ) echo 'selected="selected"' ?>>Не указано</option>
						<option value="Мужчина" <?php if( $gender == "Мужчина" ) echo 'selected="selected"' ?> >Мужчина</option>
						<option value="Женщина" <?php if( $gender == "Женщина" ) echo 'selected="selected"' ?>>Женщина</option>
					</select>
				</td>
			</tr>
			<tr class="user-personal-info-marriage">
				<th>
					<label for="personal-info-marriage">Семейный статус</label>
				</th>
				<td>
					<select name="personal_info_marriage" id="personal-info-marriage">
						<option value="" <?php if( empty($marriage) ) echo 'selected="selected"' ?>>Не указано</option>
						<option value="Не женат / Не замужем" <?php if( $marriage == "Не женат / Не замужем" ) echo 'selected="selected"' ?>>Не женат / Не замужем</option>
						<option value="Женат / Замужем" <?php if( $marriage == "Женат / Замужем" ) echo 'selected="selected"' ?>>Женат / Замужем</option>
					</select>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

// SAVE USER PROFILE
function save_user_profile( $user_id ) {
	$public_key_path = plugin_dir_path( __FILE__ ) . "keys/public.key";
	$public_key = file_get_contents($public_key_path);

	if ( !current_user_can( 'edit_user', $user_id ) ) { 
		return false; 
	}else{
		foreach (get_meta_keys_to_encrypt() as $value) {
			if(isset($_POST[$value])){
				update_usermeta( $user_id, $value, base64_encode(Enc::encrypt(get_public_key(), $_POST[$value])) );
			}
		}
	}
}

// DECRYPT USER META
function decrypt_user_meta($value, $object_id, $meta_key, $single){
	if(!in_array($meta_key, get_meta_keys_to_encrypt()))
        return null;

    $meta_cache = wp_cache_get($object_id, 'user' . '_meta');

    if ( !$meta_cache ) {
        $meta_cache = update_meta_cache( 'user', array( $object_id ) );
        $meta_cache = $meta_cache[$object_id];
    }

    if ( ! $meta_key ) {
        return Enc::decrypt(get_private_key(), base64_decode($meta_cache));
    }

    if ( isset($meta_cache[$meta_key]) ) {
        if ( $single ){
            return Enc::decrypt(get_private_key(), base64_decode( $meta_cache[$meta_key][0]));
		}
        else{
			return Enc::decrypt(get_private_key(), base64_decode($meta_cache[$meta_key]));
		}
    }

    if ($single)
        return '';
    else
        return array();
	
}	

function users_list_shortcode( $atts ){
	$total_users = count_users()['total_users'];

	$count_at_display = isset($atts['count']) ? (is_numeric($atts['count']) ? $atts['count'] : 3) : 3;
	$slug = (isset($atts['slug']) && !empty(trim($atts['slug']))) ? trim($atts['slug']) : 'user';

	if(isset($_GET['users-list-page']) && !empty($_GET['users-list-page'])){
		$offset = $_GET['users-list-page'];
	} else {
		$offset = 1;
	}

	$users = get_users([
		'fields' => ['ID'],
		'number' => $count_at_display <= $total_users ? $count_at_display : $total_users,
		'paged' => $offset,
		'orderby' => 'registered',
		'order' => 'DESC'
		]);

	if(count($users) > 0){
		$sc = '<div class="users-list-wrapper">';
		$sc .= '<ul class="users-list">';
			foreach($users as $user){
				$img = get_avatar_data($user->ID)['url'];
				$name = get_the_author_meta( 'first_name', $user->ID) . " " . get_the_author_meta( 'last_name', $user->ID);
				$nickname = get_the_author_meta( 'nicename', $user->ID);
				$description = get_the_author_meta( 'description', $user->ID);
				$url = "/$slug/?id=$user->ID";
				$sc .= sprintf("
				<li class='users-list-item'>
					<div class='users-list-item__photo'>
						<img src='%s'>
					</div>
					<div class='users-list-item__info'>
						<a href='%s'>
							<h2>%s</h2>
						</a>
						<p>
							%s
						</p>
					</div>
				</li>
				", $img, $url, trim($name) != "" ? $name : $nickname, $description);
				
			}
		$sc .= '</ul>';	
		$sc .= '<div class="users-list-pagination">';

		$c = $total_users / $count_at_display;
		if($c > 1){
			for($i = 1; $i <= ceil($c) ; $i++){
				$sc .= sprintf("<a href='%s' %s>%s</a>", add_query_arg( ['users-list-page' => $i] ), $offset == $i ? "class='active'" : "", $i);
			}
		}

		$sc .= "</div>";	
		$sc .= "</div>";
		return $sc;
	}

	return "";
	
}

function user_profile_shortcode(){
	$id = $_GET['id'];
	$total_users = count_users()['total_users'];

	if(!empty($id) && is_numeric($id) && (int)$id <= $total_users){
		$id = (int)$id;
		$name = get_the_author_meta( 'first_name', $id) . " " . get_the_author_meta( 'last_name', $id);
		$nickname = get_the_author_meta( 'nicename', $id);
		$sc = sprintf("
		<div class='user-profile'>
			<div class='user-profile__photo'>
				<img src='%s' alt=''>
			</div>
			<h2 class='user-profile__name'>%s</h2>
			<table class='user-profile__info-table'>
				<tr>
					<td><b>Адрес:</b></td>
					<td>%s</td>
				</tr>
				<tr>
					<td><b>Телефон:</b></td>
					<td>%s</td>
				</tr>
				<tr>
					<td><b>Пол:</b></td>
					<td>%s</td>
				</tr>
				<tr>
					<td><b>Семейный статус:</b></td>
					<td>%s</td>
				</tr>
			</table>
		</div>
		", 
			get_avatar_data($id)['url'],
			trim($name) != "" ? $name : $nickname,
			get_the_author_meta( 'personal_info_address', $id ),
			get_the_author_meta( 'personal_info_phone', $id ),
			get_the_author_meta( 'personal_info_gender', $id ),
			get_the_author_meta( 'personal_info_marriage', $id )
		);
	return $sc;
	} else {
		return "";
		// echo("<script>location.href = '".site_url()."'</script>");
	}
}

function user_personal_info_styles(){
	wp_register_style( 'user_personal_info_style', plugins_url('main.css',__FILE__ ));
	wp_enqueue_style( 'user_personal_info_style' );
}

// ACTIVATE PLUGIN
function activate_user_personal_info() {
	$private_key_path = plugin_dir_path( __FILE__ ) . "keys/private.key";
	$public_key_path = plugin_dir_path( __FILE__ ) . "keys/public.key";
	
	$keys = Enc::get_keys();
	file_put_contents($private_key_path, $keys['private']);
	file_put_contents($public_key_path, $keys['public']);
}


register_activation_hook( __FILE__, 'activate_user_personal_info' );


function run_user_personal_info() {
	add_filter('get_user_metadata', 'decrypt_user_meta', 10, 4);

	add_action( 'personal_options_update', 'save_user_profile');
	add_action( 'edit_user_profile_update', 'save_user_profile' );
	add_action( 'user_register', 'save_user_profile' );

	add_action( 'show_user_profile', 'insert_fields' );
	add_action( 'edit_user_profile', 'insert_fields' );
	add_action( 'user_new_form', 'insert_fields' );

	add_shortcode( 'users-list', 'users_list_shortcode' );
	add_shortcode( 'user-profile', 'user_profile_shortcode' );

	add_action( 'wp_enqueue_scripts', 'user_personal_info_styles' );
		

}
run_user_personal_info();
