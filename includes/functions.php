<?php

function hsr_create_tables() {    
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table for availability
    $availability_table = $wpdb->prefix . 'hsr_availability';
    $reservations_table = $wpdb->prefix . 'hsr_reservations';
    $staff_table = $wpdb->prefix . 'hsr_staff';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create availability table
    $sql1 = "CREATE TABLE $availability_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        staff_id mediumint(9) NOT NULL,
        date DATE NOT NULL,
        time TIME NOT NULL,
        reservation_id mediumint(9) DEFAULT NULL,
    PRIMARY KEY  (id),
    FOREIGN KEY (staff_id) REFERENCES $staff_table(id),
    FOREIGN KEY (reservation_id) REFERENCES $reservations_table(id)
    ) $charset_collate;";

    // Create reservations table
    $sql2 = "CREATE TABLE $reservations_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        user_id bigint(20) UNSIGNED DEFAULT NULL,
        memo TEXT,
        photo_url VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql3 = "CREATE TABLE $staff_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // dbDelta($sql3);
    // dbDelta($sql1);
    // dbDelta($sql2);

}

function hsr_admin_menu() {
    add_menu_page(
        'Hair Salon Reservation',
        'Reservations',
        'manage_options',
        'hsr_main',
        'hsr_main_page',
        'dashicons-calendar',
        6
    );

    add_submenu_page(
        'hsr_main',
        'Manage Availability',
        'Availability',
        'manage_options',
        'hsr_availability',
        'hsr_availability_page'
    );

    add_submenu_page(
        'hsr_main',
        'Manage Reservations',
        'Manage Reservations',
        'manage_options',
        'hsr_manage_reservations',
        'hsr_manage_reservations_page'
    );
    
    // 이메일 설정 페이지 추가
    add_submenu_page(
        'hsr_main',
        'Email Settings',
        'Email Settings',
        'manage_options',
        'hsr_email_settings',
        'hsr_email_settings_page'
    );
    // 스태프 관리 페이지 추가
    add_submenu_page(
        'hsr_main',
        'Manage Staff',
        'Staff',
        'manage_options',
        'hsr_manage_staff',
        'hsr_manage_staff_page'
    );
}
function hsr_ajax_test() {
    $response = [
        "code" => 1,
    ];
    die(json_encode($response));
    
}
function hsr_get_staff_name() {
    check_ajax_referer('hsr_ajax_nonce', 'nonce');

    $staff_id = intval($_POST['staff_id']);
    global $wpdb;
    $staff_table = $wpdb->prefix . 'hsr_staff';

    $staff_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM $staff_table WHERE id = %d", $staff_id));

    if ($staff_name) {
        wp_send_json_success($staff_name);
    } else {
        wp_send_json_error('Staff not found');
    }
}
function hsr_get_staff_availability() {
    //check_ajax_referer('hsr_ajax_nonce', 'nonce');
    //echo "<pre>";    print_r($_POST);    echo "<pre>";
    $date = sanitize_text_field($_POST['inputData']);
    global $wpdb;
    $availability_table = $wpdb->prefix . 'hsr_availability';
    $staff_table = $wpdb->prefix . 'hsr_staff';

    $availabilities = $wpdb->get_results($wpdb->prepare(
        "SELECT a.id, a.time, s.name AS staff_name 
        FROM $availability_table a 
        JOIN $staff_table s ON a.staff_id = s.id 
        WHERE a.date = %s 
        AND a.reservation_id is null
        ORDER BY a.time, s.name",
        $date
    ));

    if ($availabilities) {
        wp_send_json_success($availabilities);
    } else {
        wp_send_json_error('No availability found');
    }
    
    wp_die();
}
function hsr_get_available_staff() {
    //check_ajax_referer('hsr_ajax_nonce', 'nonce');
    $date = sanitize_text_field($_POST['date']);
    global $wpdb;
    $availability_table = $wpdb->prefix . 'hsr_availability';
    $staff_table = $wpdb->prefix . 'hsr_staff';

    $staff = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT s.id, s.name 
        FROM $staff_table s 
        JOIN $availability_table a ON s.id = a.staff_id 
        WHERE a.date = %s 
        AND a.reservation_id is null
        ORDER BY s.name",
        $date
    ));

    if ($staff) {
        wp_send_json_success($staff);
    } else {
        wp_send_json_error('No staff available');
    }
}

function hsr_get_available_times() {
    //check_ajax_referer('hsr_ajax_nonce', 'nonce');
    $date = sanitize_text_field($_POST['date']);
    $staff_id = intval($_POST['staff_id']);
    global $wpdb;
    $availability_table = $wpdb->prefix . 'hsr_availability';

    $times = $wpdb->get_results($wpdb->prepare(
        "SELECT id, time 
        FROM $availability_table 
        WHERE date = %s AND staff_id = %d         
        AND reservation_id is null
        ORDER BY time",
        $date,
        $staff_id
    ));

    if ($times) {
        wp_send_json_success($times);
    } else {
        wp_send_json_error('No times available');
    }
}

// Availability bulk job
function hsr_bulk_add_availability() {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hsr_bulk_add_availability_nonce')) {
        wp_die('보안 검증에 실패했습니다.');
    }

    $date = sanitize_text_field($_POST['bulk_date']);
    $start_time = sanitize_text_field($_POST['start_time']);
    $end_time = sanitize_text_field($_POST['end_time']);
    $interval = intval($_POST['interval']);

    global $wpdb;
    $staff_table = $wpdb->prefix . 'hsr_staff';
    $availability_table = $wpdb->prefix . 'hsr_availability';

    $staff_members = $wpdb->get_results("SELECT id FROM $staff_table");

    $start = new DateTime($date . ' ' . $start_time);
    $end = new DateTime($date . ' ' . $end_time);
    $interval = new DateInterval('PT' . $interval . 'M');

    $inserted = 0;
    $period = new DatePeriod($start, $interval, $end);

    foreach ($period as $dt) {
        foreach ($staff_members as $staff) {
            $wpdb->insert(
                $availability_table,
                array(
                    'date' => $date,
                    'time' => $dt->format('H:i:s'),
                    'staff_id' => $staff->id
                )
            );
            $inserted++;
        }
    }

    echo '<div class="updated"><p>' . $inserted . '개의 availability가 추가되었습니다.</p></div>';
}
add_action('wp_ajax_hsr_make_reservation', 'hsr_make_reservation');

function hsr_make_reservation() {
    global $wpdb;
    $availability_table = $wpdb->prefix . 'hsr_availability';
    $reservations_table = $wpdb->prefix . 'hsr_reservations';

    $availability_id = intval($_POST['availability_id']);
    $user_id = intval($_POST['user_id']);
    $memo = sanitize_textarea_field($_POST['memo']);

    $availability = $wpdb->get_row($wpdb->prepare("SELECT * FROM $availability_table WHERE id = %d", $availability_id));

    if (!$availability) {
        wp_send_json_error('Availability not found');
        return;
    }

    if ($availability->reservation_id) {
        wp_send_json_error('This time slot is already booked');
        return;
    }

    $wpdb->insert($reservations_table, [
        'user_id' => $user_id,
        'memo' => $memo,
        'created_at' => current_time('mysql')
    ]);

    $reservation_id = $wpdb->insert_id;

    $wpdb->update($availability_table, ['reservation_id' => $reservation_id], ['id' => $availability_id]);

    wp_send_json_success();
}

// 사용자 프로필에 전화번호 필드 추가
function hsr_add_phone_field($user) {
    ?>
    <h3>전화번호</h3>
    <table class="form-table">
        <tr>
            <th><label for="phone">전화번호</label></th>
            <td>
                <input type="tel" name="phone" id="phone" value="<?php echo esc_attr(get_user_meta($user->ID, 'phone', true)); ?>" class="regular-text" />
            </td>
        </tr>
    </table>
    <?php
}

// 사용자 프로필 업데이트 시 전화번호 저장
function hsr_save_phone_field($user_id) {
    if (current_user_can('edit_user', $user_id)) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    }
}

// 등록 폼에 전화번호 필드 추가
function hsr_add_phone_to_registration($errors, $sanitized_user_login, $user_email) {
    if (empty($_POST['phone'])) {
        $errors->add('phone_error', __('<strong>ERROR</strong>: Please enter your phone number.', 'hairsalonreservation'));
    }
    return $errors;
}
add_filter('registration_errors', 'hsr_add_phone_to_registration', 10, 3);

// 등록 시 전화번호 저장
function hsr_save_phone_on_registration($user_id) {
    if (isset($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    }
}

// 등록 폼에 전화번호 필드 출력
function hsr_add_phone_field_to_registration() {
    ?>
    <p>
        <label for="phone"><?php _e('Phone Number', 'hairsalonreservation'); ?><br />
        <input type="tel" name="phone" id="phone" class="input" value="<?php echo (isset($_POST['phone'])) ? esc_attr($_POST['phone']) : ''; ?>" size="25" /></label>
    </p>
    <?php
}
?>