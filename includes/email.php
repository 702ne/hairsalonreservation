<?php
function hsr_email_settings_page() {
    // 저장된 옵션 불러오기
    $admin_email = get_option('hsr_admin_email');
    $from_email = get_option('hsr_from_email');

    // 폼 제출 처리
    if (isset($_POST['hsr_save_email_settings'])) {
        check_admin_referer('hsr_save_email_settings_nonce');
        $admin_email = sanitize_email($_POST['hsr_admin_email']);
        $from_email = sanitize_email($_POST['hsr_from_email']);
        update_option('hsr_admin_email', $admin_email);
        update_option('hsr_from_email', $from_email);
        echo '<div class="updated"><p>Email settings saved.</p></div>';
    }

    // 페이지 출력
    echo '<div class="wrap"><h1>Email Settings</h1>';
    echo '<form method="post">';
    wp_nonce_field('hsr_save_email_settings_nonce');
    echo '<table class="form-table">';
    echo '<tr><th scope="row"><label for="hsr_admin_email">Admin Email</label></th>';
    echo '<td><input type="email" id="hsr_admin_email" name="hsr_admin_email" value="' . esc_attr($admin_email) . '" required></td></tr>';
    echo '<tr><th scope="row"><label for="hsr_from_email">From Email</label></th>';
    echo '<td><input type="email" id="hsr_from_email" name="hsr_from_email" value="' . esc_attr($from_email) . '" required></td></tr>';
    echo '</table>';
    submit_button('Save Settings', 'primary', 'hsr_save_email_settings');
    echo '</form></div>';
}

function hsr_send_reservation_emails($name, $phone, $date, $time, $staff_id) {
    $admin_email = get_option('hsr_admin_email');
    $from_email = get_option('hsr_from_email');

    global $wpdb;
    $staff_table = $wpdb->prefix . 'hsr_staff';
    $staff_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM $staff_table WHERE id = %d", $staff_id));
    
    $subject_admin = 'New Reservation Made';
    $message_admin = "A new reservation has been made.\\n\\nName: $name\\nPhone: $phone\\nDate: $date\\nTime: $time\\nStaff: $staff_name";
    $headers_admin = 'From: ' . $from_email . "\\r\\n";

    wp_mail($admin_email, $subject_admin, $message_admin, $headers_admin);
/*
    $subject_customer = 'Reservation Confirmation';
    $message_customer = "Dear $name,\\n\\nThank you for your reservation.\\n\\nDetails:\\nDate: $date\\nTime: $time\\n\\nWe look forward to serving you.";
    $headers_customer = 'From: ' . $from_email . "\\r\\n";

    wp_mail($phone . '@smsprovider.com', $subject_customer, $message_customer, $headers_customer);
    */

    echo '<div class="updated"><p>email sent to '. $admin_email . '</p></div>';
}

?>