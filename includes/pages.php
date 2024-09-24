<?php

// main page
function hsr_main_page() {
    echo '<div class="wrap"><h1>Hair Salon Reservation System</h1><p>Welcome to the reservation system. Use the submenus to manage availability and reservations.</p></div>';
}

// manage staff page
function hsr_manage_staff_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'hsr_staff';
    
        // 직원 추가
        if (isset($_POST['add_staff'])) {
            check_admin_referer('hsr_add_staff_nonce');
            $name = sanitize_text_field($_POST['staff_name']);
            $wpdb->insert($table, ['name' => $name]);
            echo '<div class="updated"><p>직원이 추가되었습니다.</p></div>';
        }
    
        // 직원 수정
        if (isset($_POST['edit_staff'])) {
            check_admin_referer('hsr_edit_staff_nonce');
            $id = intval($_POST['staff_id']);
            $name = sanitize_text_field($_POST['staff_name']);
            $wpdb->update($table, ['name' => $name], ['id' => $id]);
            echo '<div class="updated"><p>직원 정보가 수정되었습니다.</p></div>';
        }
    
        // 직원 삭제
        if (isset($_GET['action']) && $_GET['action'] == 'delete') {
            $id = intval($_GET['id']);
            $wpdb->delete($table, ['id' => $id]);
            echo '<div class="updated"><p>직원이 삭제되었습니다.</p></div>';
        }
    
        // 직원 목록 가져오기
        $staff = $wpdb->get_results("SELECT * FROM $table ORDER BY name");
    
        echo '<div class="wrap">';
        echo '<h1>직원 관리</h1>';
    
        // 직원 추가 폼
        echo '<h2>새 직원 추가</h2>';
        echo '<form method="post">';
        wp_nonce_field('hsr_add_staff_nonce');
        echo '<input type="text" name="staff_name" required placeholder="직원 이름">';
        submit_button('직원 추가', 'primary', 'add_staff');
        echo '</form>';
    
        // 직원 목록 및 수정/삭제 폼
        echo '<h2>직원 목록</h2>';
        if ($staff) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>이름</th><th>작업</th></tr></thead>';
            echo '<tbody>';
            foreach ($staff as $member) {
                echo '<tr>';
                echo '<td>' . esc_html($member->id) . '</td>';
                echo '<td>';
                echo '<form method="post" style="display:inline;">';
                wp_nonce_field('hsr_edit_staff_nonce');
                echo '<input type="hidden" name="staff_id" value="' . esc_attr($member->id) . '">';
                echo '<input type="text" name="staff_name" value="' . esc_attr($member->name) . '" required>';
                submit_button('수정', 'small', 'edit_staff', false);
                echo '</form>';
                echo '</td>';
                echo '<td><a href="' . add_query_arg(['action' => 'delete', 'id' => $member->id]) . '" onclick="return confirm(\'이 직원을 삭제하시겠습니까?\')">삭제</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>등록된 직원이 없습니다.</p>';
        }
    
        echo '</div>';

}

// availability page
function hsr_availability_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'hsr_availability';
    $staff_table = $wpdb->prefix . 'hsr_staff';

    // Handle form submissions
    if (isset($_POST['hsr_add_availability'])) {
        check_admin_referer('hsr_add_availability_nonce');
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $staff_id = intval($_POST['staff_id']);

                // Check for duplicate entries
                $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE date = %s AND time = %s AND staff_id = %d", $date, $time, $staff_id));
                if ($existing) {
                    echo '<div class="error"><p>This time slot is already taken for the selected staff.</p></div>';
                } else {
                    $wpdb->insert($table, ['date' => $date, 'time' => $time, 'staff_id' => $staff_id]);
                    echo '<div class="updated"><p>Availability added.</p></div>';
                }

        //$wpdb->insert($table, ['date' => $date, 'time' => $time, 'staff_id' => $staff_id]);
        echo '<div class="updated"><p>Availability added.</p></div>';
    }

    // Handle deletion
    if (isset($_GET['action']) && $_GET['action'] == 'delete') {
        //check_admin_referer('hsr_delete_availability_nonce');
        $id = intval($_GET['id']);
        $wpdb->delete($table, ['id' => $id]);
        echo '<div class="updated"><p>Availability deleted.</p></div>';
    }

    // Fetch all availability, excluding past times
    $current_time = current_time('Y-m-d H:i:s');
    $availabilities = $wpdb->get_results($wpdb->prepare("SELECT a.*, s.name AS staff_name FROM $table a LEFT JOIN $staff_table s ON a.staff_id = s.id WHERE CONCAT(a.date, ' ', a.time) >= %s ORDER BY a.date, a.time", $current_time));
    //$availabilities = $wpdb->get_results("SELECT a.*, s.name AS staff_name FROM $table a LEFT JOIN $staff_table s ON a.staff_id = s.id ORDER BY a.date, a.time");
    $staff_members = $wpdb->get_results("SELECT * FROM $staff_table ORDER BY name");
 
    // Display the page
    echo '<div class="wrap"><h1>Manage Availability</h1>';

    // Add Availability Form
    echo '<h2>Add New Availability</h2>';
    echo '<form method="post">';
    wp_nonce_field('hsr_add_availability_nonce');
    echo '<table class="form-table">';
    echo '<tr><th scope="row"><label for="date">Date</label></th>';
    echo '<td><input type="date" id="date" name="date" required></td></tr>';
    echo '<tr><th scope="row"><label for="time">Time</label></th>';
    echo '<td><input type="time" id="time" name="time" required></td></tr>';
    echo '<tr><th scope="row"><label for="staff_id">Staff</label></th>';
    echo '<td><select id="staff_id" name="staff_id" required>';
    echo '<option value="">Select Staff</option>';
    foreach ($staff_members as $staff) {
        echo '<option value="' . esc_attr($staff->id) . '">' . esc_html($staff->name) . '</option>';
    }
    echo '</select></td></tr>';
    echo '</table>';
    submit_button('Add Availability', 'primary', 'hsr_add_availability');
    echo '</form>';

    // List of Current Availability
    echo '<h2>Current Availability</h2>';
    if ($availabilities) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Date</th><th>Time</th><th>Actions</th></tr></thead><tbody>';
        foreach ($availabilities as $availability) {
            echo '<tr>';
            echo '<td>' . esc_html($availability->id) . '</td>';
            echo '<td>' . esc_html($availability->date) . '</td>';
            echo '<td>' . esc_html($availability->time) . '</td>';
            echo '<td>' . esc_html($availability->staff_name) . '</td>';
            echo '<td><a href="' . admin_url('admin.php?page=hsr_availability&action=delete&id=' . $availability->id) . '" onclick="return confirm(\'Are you sure you want to delete this availability?\')">Delete</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No availability set.</p>';
    }

    echo '</div>';
}

// manage reservations page
function hsr_manage_reservations_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'hsr_reservations';

    // Handle deletion
    if (isset($_GET['action']) && $_GET['action'] == 'delete') {
        //check_admin_referer('hsr_delete_reservation_nonce');
        $id = intval($_GET['id']);
        $wpdb->delete($table, ['id' => $id]);
        echo '<div class="updated"><p>Reservation deleted.</p></div>';
    }

    // Fetch all reservations
    $reservations = $wpdb->get_results("SELECT * FROM $table ORDER BY date DESC, time DESC");

    // Display the page
    echo '<div class="wrap"><h1>Manage Reservations</h1>';

    // List of Reservations
    if ($reservations) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Date</th><th>Time</th><th>Created At</th><th>Actions</th></tr></thead><tbody>';
        foreach ($reservations as $reservation) {
            echo '<tr>';
            echo '<td>' . esc_html($reservation->id) . '</td>';
            echo '<td>' . esc_html($reservation->name) . '</td>';
            echo '<td>' . esc_html($reservation->phone) . '</td>';
            echo '<td>' . esc_html($reservation->date) . '</td>';
            echo '<td>' . esc_html($reservation->time) . '</td>';
            echo '<td>' . esc_html($reservation->created_at) . '</td>';
            //echo '<td><a href="' . admin_url('admin.php?page=hsr_manage_reservations&action=delete&id=' . $reservation->id) .'" onclick="return confirm(\\'Are you sure you want to delete this reservation?\\')">Delete</a></td>';
            echo "<td><a href='" . admin_url("admin.php?page=hsr_manage_reservations&action=delete&id={$reservation->id}") ."' onclick=\"return confirm('Are you sure you want to delete this reservation?')\">Delete</a></td>";
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No reservations found.</p>';
    }

    echo '</div>';
}


?>
