<?php
/**
 * Збирає сторінку «до» в Elementor: так виглядає типовий сайт місцевого бізнесу, зроблений конструктором.
 * Запуск (з папки WordPress): php build-elementor-page.php <шлях до photos/original>
 */

$_SERVER['HTTP_HOST'] = 'localhost:8081';
require getcwd() . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$photos_dir = rtrim( $argv[1] ?? '', '/\\' );
if ( ! is_dir( $photos_dir ) ) {
	exit( "Вкажіть папку з фото\n" );
}

wp_set_current_user( 1 );

/** Завантажує фото в медіатеку так само, як це робить людина через адмінку. */
function sto_upload( $file ) {
	$tmp = wp_tempnam( basename( $file ) );
	copy( $file, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $file ), 'tmp_name' => $tmp ), 0 );
	if ( is_wp_error( $id ) ) {
		exit( $id->get_error_message() . "\n" );
	}
	return array( 'id' => $id, 'url' => wp_get_attachment_url( $id ) );
}

$img = array();
foreach ( array( 'hero', 'diagnostics', 'tires', 'oil', 'gallery-1', 'gallery-2', 'gallery-3' ) as $name ) {
	$img[ $name ] = sto_upload( "$photos_dir/$name.jpg" );
	echo "uploaded $name\n";
}

$n = 0;
function sto_id() {
	global $n;
	return substr( md5( 'sto' . ( ++$n ) ), 0, 7 );
}

function sto_widget( $type, $settings ) {
	return array( 'id' => sto_id(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => array() );
}

function sto_column( $size, $widgets, $settings = array() ) {
	return array( 'id' => sto_id(), 'elType' => 'column', 'settings' => array_merge( array( '_column_size' => $size ), $settings ), 'elements' => $widgets );
}

function sto_section( $columns, $settings = array() ) {
	return array( 'id' => sto_id(), 'elType' => 'section', 'settings' => $settings, 'elements' => $columns );
}

function sto_px( $size ) {
	return array( 'unit' => 'px', 'size' => $size, 'sizes' => array() );
}

function sto_pad( $v, $h ) {
	return array( 'unit' => 'px', 'top' => $v, 'right' => $h, 'bottom' => $v, 'left' => $h, 'isLinked' => false );
}

function sto_heading( $text, $tag = 'h2', $size = 38, $color = '#111827', $align = 'center' ) {
	return sto_widget(
		'heading',
		array(
			'title'                     => $text,
			'header_size'               => $tag,
			'align'                     => $align,
			'title_color'               => $color,
			'typography_typography'     => 'custom',
			'typography_font_family'    => 'Montserrat',
			'typography_font_weight'    => '800',
			'typography_font_size'      => sto_px( $size ),
			'_animation'                => 'fadeInUp',
		)
	);
}

function sto_text( $html, $color = '#4b5563', $align = 'center' ) {
	return sto_widget(
		'text-editor',
		array(
			'editor'                 => $html,
			'align'                  => $align,
			'text_color'             => $color,
			'typography_typography'  => 'custom',
			'typography_font_family' => 'Open Sans',
			'typography_font_size'   => sto_px( 17 ),
			'_animation'             => 'fadeIn',
		)
	);
}

function sto_button( $text, $url, $bg = '#ea580c' ) {
	return sto_widget(
		'button',
		array(
			'text'             => $text,
			'link'             => array( 'url' => $url ),
			'size'             => 'lg',
			'align'            => 'center',
			'background_color' => $bg,
			'border_radius'    => array( 'unit' => 'px', 'top' => 40, 'right' => 40, 'bottom' => 40, 'left' => 40, 'isLinked' => true ),
			'selected_icon'    => array( 'value' => 'fas fa-phone-alt', 'library' => 'fa-solid' ),
			'_animation'       => 'zoomIn',
		)
	);
}

$phone = '+380 00 000 00 00';
$data  = array();

// Верхня смужка з контактами (іконки Font Awesome).
$data[] = sto_section(
	array(
		sto_column(
			100,
			array(
				sto_widget(
					'icon-list',
					array(
						'view'       => 'inline',
						'icon_align' => 'center',
						'text_color' => '#e5e7eb',
						'icon_color' => '#f97316',
						'icon_list'  => array(
							array( '_id' => sto_id(), 'text' => $phone, 'selected_icon' => array( 'value' => 'fas fa-phone-alt', 'library' => 'fa-solid' ) ),
							array( '_id' => sto_id(), 'text' => 'вул. Прикладна, 5, Луцьк', 'selected_icon' => array( 'value' => 'fas fa-map-marker-alt', 'library' => 'fa-solid' ) ),
							array( '_id' => sto_id(), 'text' => 'Пн–Сб 8:00–19:00', 'selected_icon' => array( 'value' => 'far fa-clock', 'library' => 'fa-regular' ) ),
						),
					)
				),
			)
		),
	),
	array( 'background_background' => 'classic', 'background_color' => '#111827', 'padding' => sto_pad( 8, 0 ) )
);

// Перший екран з фоновим фото.
$data[] = sto_section(
	array(
		sto_column(
			100,
			array(
				sto_heading( 'СТО «Мотор» — ремонт авто в Луцьку', 'h1', 56, '#ffffff' ),
				sto_text( '<p>Діагностика, ремонт ходової, заміна оливи та шиномонтаж. Працюємо з усіма марками, даємо гарантію на роботи.</p>', '#f3f4f6' ),
				sto_button( 'Записатися на ремонт', 'tel:+380000000000' ),
			)
		),
	),
	array(
		'layout'                              => 'full_width',
		'height'                              => 'min-height',
		'custom_height'                       => array( 'unit' => 'vh', 'size' => 90, 'sizes' => array() ),
		'background_background'               => 'classic',
		'background_image'                    => $img['hero'],
		'background_position'                 => 'center center',
		'background_size'                     => 'cover',
		'background_attachment'               => 'fixed',
		'background_overlay_background'       => 'classic',
		'background_overlay_color'            => '#000000',
		'background_overlay_opacity'          => sto_px( 0.55 ),
		'content_position'                    => 'middle',
		'animation'                           => 'fadeIn',
	)
);

// Лічильники.
$counters = array();
foreach ( array( array( 15, '', 'років досвіду' ), array( 12000, '+', 'задоволених клієнтів' ), array( 8, '', 'робочих боксів' ), array( 12, ' міс.', 'гарантія на роботи' ) ) as $c ) {
	$counters[] = sto_column(
		25,
		array(
			sto_widget(
				'counter',
				array(
					'starting_number'                => 0,
					'ending_number'                  => $c[0],
					'suffix'                         => $c[1],
					'title'                          => $c[2],
					'number_color'                   => '#ea580c',
					'typography_number_typography'   => 'custom',
					'typography_number_font_family'  => 'Montserrat',
					'_animation'                     => 'fadeInUp',
				)
			),
		)
	);
}
$data[] = sto_section( $counters, array( 'padding' => sto_pad( 60, 0 ) ) );

// Послуги.
$services = array(
	array( 'diagnostics', 'Комп’ютерна діагностика', 'Знаходимо несправності за 30 хвилин. Від 400 грн.' ),
	array( 'tires', 'Шиномонтаж', 'Сезонна заміна шин, балансування, зберігання. Від 600 грн.' ),
	array( 'oil', 'Заміна оливи', 'Олива та фільтри з гарантією оригінальності. Від 300 грн.' ),
);
$cols = array();
foreach ( $services as $s ) {
	$cols[] = sto_column(
		33,
		array(
			sto_widget(
				'image-box',
				array(
					'image'            => $img[ $s[0] ],
					'title_text'       => $s[1],
					'description_text' => $s[2],
					'title_size'       => 'h3',
					'position'         => 'top',
					'image_size'       => array( 'unit' => '%', 'size' => 100, 'sizes' => array() ),
					'_animation'       => 'fadeInUp',
				)
			),
		)
	);
}
$data[] = sto_section( array( sto_column( 100, array( sto_heading( 'Наші послуги' ) ) ) ), array( 'padding' => sto_pad( 40, 0 ) ) );
$data[] = sto_section( $cols, array( 'padding' => sto_pad( 20, 0 ), 'gap' => 'wider' ) );

// Чому ми.
$why = array();
foreach ( array( array( 'fas fa-shield-alt', 'Гарантія 12 місяців', 'На всі роботи та встановлені запчастини.' ), array( 'fas fa-tools', 'Сучасне обладнання', 'Підйомники, стенд розвалу-сходження, сканери.' ), array( 'fas fa-ruble-sign', 'Чесні ціни', 'Погоджуємо вартість до початку ремонту.' ) ) as $w ) {
	$why[] = sto_column(
		33,
		array(
			sto_widget(
				'icon-box',
				array(
					'selected_icon'    => array( 'value' => $w[0], 'library' => 'fa-solid' ),
					'title_text'       => $w[1],
					'description_text' => $w[2],
					'primary_color'    => '#ea580c',
					'_animation'       => 'bounceIn',
				)
			),
		)
	);
}
$data[] = sto_section( array( sto_column( 100, array( sto_heading( 'Чому обирають нас' ) ) ) ), array( 'padding' => sto_pad( 50, 0 ), 'background_background' => 'classic', 'background_color' => '#f3f4f6' ) );
$data[] = sto_section( $why, array( 'padding' => sto_pad( 10, 0 ), 'background_background' => 'classic', 'background_color' => '#f3f4f6' ) );

// Галерея-карусель.
$data[] = sto_section(
	array(
		sto_column(
			100,
			array(
				sto_heading( 'Наш сервіс' ),
				sto_widget(
					'image-carousel',
					array(
						'carousel'       => array( $img['gallery-1'], $img['gallery-2'], $img['gallery-3'], $img['hero'] ),
						'thumbnail_size' => 'full',
						'slides_to_show' => '3',
						'navigation'     => 'both',
						'autoplay'       => 'yes',
						'image_stretch'  => 'yes',
					)
				),
			)
		),
	),
	array( 'padding' => sto_pad( 60, 0 ) )
);

// Питання.
$data[] = sto_section(
	array(
		sto_column(
			100,
			array(
				sto_heading( 'Часті питання' ),
				sto_widget(
					'accordion',
					array(
						'tabs' => array(
							array( '_id' => sto_id(), 'tab_title' => 'Чи можна приїхати без запису?', 'tab_content' => '<p>Так, але з записом ви не чекатимете в черзі.</p>' ),
							array( '_id' => sto_id(), 'tab_title' => 'Чи можна принести свої запчастини?', 'tab_content' => '<p>Так, але гарантія тоді діє лише на роботу.</p>' ),
							array( '_id' => sto_id(), 'tab_title' => 'Скільки триває діагностика?', 'tab_content' => '<p>Комп’ютерна діагностика — близько 30 хвилин.</p>' ),
						),
					)
				),
			)
		),
	),
	array( 'padding' => sto_pad( 60, 0 ) )
);

// Карта Google.
$data[] = sto_section(
	array(
		sto_column(
			100,
			array(
				sto_heading( 'Як нас знайти' ),
				sto_widget( 'google_maps', array( 'address' => 'Луцьк, Україна', 'zoom' => sto_px( 13 ), 'height' => sto_px( 400 ) ) ),
				sto_button( 'Подзвонити: ' . $phone, 'tel:+380000000000' ),
			)
		),
	),
	array( 'padding' => sto_pad( 60, 0 ) )
);

// Підвал.
$data[] = sto_section(
	array( sto_column( 100, array( sto_text( '<p>© СТО «Мотор», Луцьк. Демо-сторінка «до» для кейсу з оптимізації швидкості.</p>', '#9ca3af' ) ) ) ),
	array( 'background_background' => 'classic', 'background_color' => '#111827', 'padding' => sto_pad( 30, 0 ) )
);

$page_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'СТО «Мотор» — ремонт авто в Луцьку',
		'post_name'    => 'golovna',
		'post_content' => '',
	)
);

update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
update_post_meta( $page_id, '_wp_page_template', 'elementor_canvas' );
update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_id );
update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();

echo "page $page_id\n";
