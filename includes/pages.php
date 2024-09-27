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

    // Handle form submissions for one
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
    
    // Handle form submissions for bulk job
    if (isset($_POST['hsr_bulk_add_availability'])) {
        hsr_bulk_add_availability();
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
    $availabilities = $wpdb->get_results($wpdb->prepare(
        "SELECT a.*, s.name AS staff_name 
         FROM $table a LEFT JOIN $staff_table s ON a.staff_id = s.id 
         WHERE reservation_id is null AND CONCAT(a.date, ' ', a.time) >= %s 
         ORDER BY a.date, a.time", $current_time));
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

    // bulk job
    echo '<h2>대량 Availability 추가</h2>';
    echo '<form method="post" action="">';
    wp_nonce_field('hsr_bulk_add_availability_nonce');
    echo '<table class="form-table">';
    echo '<tr><th scope="row"><label for="bulk_date">날짜</label></th>';
    echo '<td><input type="date" id="bulk_date" name="bulk_date" required></td></tr>';
    echo '<tr><th scope="row"><label for="start_time">시작 시간</label></th>';
    echo '<td><input type="time" id="start_time" name="start_time" required></td></tr>';
    echo '<tr><th scope="row"><label for="end_time">종료 시간</label></th>';
    echo '<td><input type="time" id="end_time" name="end_time" required></td></tr>';
    echo '<tr><th scope="row"><label for="interval">시간 간격 (분)</label></th>';
    echo '<td><input type="number" id="interval" name="interval" min="1" required></td></tr>';
    echo '</table>';
    submit_button('대량 Availability 추가', 'primary', 'hsr_bulk_add_availability');
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

// 일별
    // Date selection form
    echo '<h2>View Availability</h2>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="hsr_availability">';
    echo '<label for="view_date">Select Date: </label>';
    echo '<input type="date" id="view_date" name="view_date" value="' . (isset($_GET['view_date']) ? esc_attr($_GET['view_date']) : date('Y-m-d')) . '" required>';
    echo '<input type="submit" value="View">';
    echo '</form>';

    // Display availability table
    if (isset($_GET['view_date'])) {
        $view_date = sanitize_text_field($_GET['view_date']);
        $staff_members = $wpdb->get_results("SELECT * FROM $staff_table ORDER BY name");
        
        echo '<h3>Availability for ' . esc_html($view_date) . '</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Time</th>';
        foreach ($staff_members as $staff) {
            echo '<th>' . esc_html($staff->name) . '</th>';
        }
        echo '</tr></thead><tbody>';
    
        $start_time = new DateTime('09:00');
        $end_time = new DateTime('18:00');
        $interval = new DateInterval('PT20M');
    
        while ($start_time <= $end_time) {
            echo '<tr>';
            echo '<td>' . $start_time->format('H:i') . '</td>';
            
            foreach ($staff_members as $staff) {
                $availability = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table WHERE date = %s AND time = %s AND staff_id = %d",
                    $view_date,
                    $start_time->format('H:i:s'),
                    $staff->id
                ));
    
                echo '<td>';
                if ($availability) {
                    if ($availability->reservation_id) {
                        echo esc_html($availability->reservation_id);
                    } else {
                        echo '<a href="' . admin_url('admin.php?page=hsr_availability&action=delete&id=' . $availability->id) . '" onclick="return confirm(\'Are you sure you want to delete this availability?\')">X</a> ';
                        echo '<a href="#" onclick="openBookingModal(' . $availability->id . ', \'' . $staff->name . '\', \'' . $view_date . '\', \'' . $start_time->format('H:i') . '\'); return false;">B+</a>';
                    }
                } else {
                    echo 'N/A';
                }
                echo '</td>';
            }
            
            echo '</tr>';
            $start_time->add($interval);
        }
    
        echo '</tbody></table>';
    }
// 일별 end

    echo '</div>';
    echo '<script>
    function openBookingModal(availabilityId, staffName, date, time) {
        var modal = document.createElement("div");
        modal.style.position = "fixed";
        modal.style.left = "0";
        modal.style.top = "0";
        modal.style.width = "100%";
        modal.style.height = "100%";
        modal.style.backgroundColor = "rgba(0,0,0,0.5)";
        modal.style.display = "flex";
        modal.style.justifyContent = "center";
        modal.style.alignItems = "center";

        var content = document.createElement("div");
        content.style.backgroundColor = "#fff";
        content.style.padding = "20px";
        content.style.borderRadius = "5px";

        content.innerHTML = `
            <h2>예약하기</h2>
            <p>직원: ${staffName}</p>
            <p>날짜: ${date}</p>
            <p>시간: ${time}</p>
            <select id="user-select">
                <option value="">사용자 선택</option>
                ' . implode('', array_map(function($user) {
                    return '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                }, get_users())) . '
            </select><br><br>
            <textarea id="memo" placeholder="메모"></textarea><br><br>
            <button onclick="makeReservation(${availabilityId})">OK</button>
            <button onclick="closeModal()">Close</button>
        `;

        modal.appendChild(content);
        document.body.appendChild(modal);
    }

    function closeModal() {
        var modal = document.querySelector("div[style*=\'position: fixed\']");
        if (modal) {
            modal.remove();
        }
    }

    function makeReservation(availabilityId) {
        var userId = document.getElementById("user-select").value;
        var memo = document.getElementById("memo").value;

        if (!userId) {
            alert("사용자를 선택해주세요.");
            return;
        }

        var data = {
            action: "hsr_make_reservation",
            availability_id: availabilityId,
            user_id: userId,
            memo: memo
        };

        jQuery.post(ajaxurl, data, function(response) {
            if (response.success) {
                alert("예약이 완료되었습니다.");
                location.reload();
            } else {
                alert("예약에 실패했습니다: " + response.data);
            }
            closeModal();
        });
    }
    </script>';
}

// manage reservations page
function hsr_manage_reservations_page() {
    global $wpdb;
    $reservations_table = $wpdb->prefix . 'hsr_reservations';
    $availability_table = $wpdb->prefix . 'hsr_availability';
    $staff_table = $wpdb->prefix . 'hsr_staff';
    $user_table = $wpdb->prefix . 'users';
    // Handle deletion
    if (isset($_GET['action']) && $_GET['action'] == 'delete') {
        //check_admin_referer('hsr_delete_reservation_nonce');
        $id = intval($_GET['id']);
        $wpdb->delete($table, ['id' => $id]);
        echo '<div class="updated"><p>Reservation deleted.</p></div>';
    }

    // Fetch all reservations
    //$reservations = $wpdb->get_results("SELECT * FROM $table ORDER BY date DESC, time DESC");

    // 사용자 예약 내역 불러오기       
    $reservations = $wpdb->get_results($wpdb->prepare(
        "SELECT r.*, a.date, a.time, s.name, u.user_login FROM $availability_table a 
        join $reservations_table r ON a.reservation_id = r.id 
        join $staff_table s ON a.staff_id = s.id 
        join $user_table u ON r.user_id = u.id 
        WHERE a.reservation_id is not null 
        ORDER BY date DESC, time DESC"));
        // AND r.user_id = %d 
        // ORDER BY date DESC, time DESC"
        // , $current_user->ID));

    // Display the page
    echo '<div class="wrap"><h1>Manage Reservations</h1>';

    // List of Reservations
    if ($reservations) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Staff</th><th>Customer</th><th>Phone</th><th>Date</th><th>Time</th><th>Created At</th><th>Actions</th></tr></thead><tbody>';
        foreach ($reservations as $reservation) {
            echo '<tr>';
            echo '<td>' . esc_html($reservation->id) . '</td>';
            echo '<td>' . esc_html($reservation->name) . '</td>';
            echo '<td>' . esc_html($reservation->user_login) . '</td>';
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
