<?php
require_once(ABSPATH . 'wp-admin/includes/template.php');

function hsr_reservation_form() {
    ob_start();

    if (isset($_POST['hsr_submit_reservation'])) {
        hsr_handle_reservation_submission();
    }

    ?>
<script>
    window.ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>"
</script>
    <?php
    hsr_display_reservation_form();

    return ob_get_clean();
}
// http://localhost/home/booking-form/
function hsr_display_reservation_form() {
    global $wpdb;
    $availability_table = $wpdb->prefix . 'hsr_availability';
    $staff_table = $wpdb->prefix . 'hsr_staff';

    if (!is_user_logged_in()) {
        echo '<p>예약을 진행하려면 <a href="' . wp_login_url(get_permalink()) . '">로그인</a>하거나 <a href="' . wp_registration_url() . '">회원가입</a> 해주세요.</p>';
        return;
    }

    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name;
    $user_phone = get_user_meta($current_user->ID, 'phone', true);

    echo '<form method="post" id="reservation-form">';
    wp_nonce_field('hsr_reservation_nonce');

    echo '<p>이름: ' . esc_html($user_name) . '</p>';

    if (empty($user_phone)) {
        echo '<p>';
        echo '<label for="hsr_phone">전화번호:</label><br>';
        echo '<input type="tel" id="hsr_phone" name="hsr_phone" required>';
        echo '</p>';
    } else {
        echo '<p>전화번호: ' . esc_html($user_phone) . '</p>';
    }

    $dates = $wpdb->get_col("SELECT DISTINCT date FROM $availability_table WHERE date >= CURDATE() ORDER BY date");

    echo '<div class="reservation-grid">';
    echo '<div class="reservation-column">';
    echo '<label for="hsr_date">날짜:</label>';
    echo '<select id="hsr_date" name="hsr_date" required size="4">';
    foreach ($dates as $date) {
        echo '<option value="' . esc_attr($date) . '">' . esc_html($date) . '</option>';
    }
    echo '</select>';
    echo '</div>';

    echo '<div class="reservation-column" id="staff-container" >';
    echo '<label for="hsr_staff">직원:</label>';
    echo '<select id="hsr_staff" name="hsr_staff" required size="4"></select>';
    echo '</div>';

    echo '<div class="reservation-column" id="time-container">';
    echo '<label for="hsr_time">시간:</label>';
    echo '<select id="hsr_time" name="hsr_time" required size="10"></select>';
    echo '</div>';
    echo '</div>';

    submit_button('예약하기', 'primary', 'hsr_submit_reservation');
    echo '</form>';

    ?>

    <script>
    jQuery(document).ready(function($) {
        $('#hsr_date').change(function() {
            var selectedDate = $(this).val();
            
            var select = $('#hsr_staff');
            select.empty();
            var select1 = $('#hsr_time');
            select1.empty()
            if (selectedDate) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'hsr_get_available_staff',
                        date: selectedDate,
                        nonce: '<?php echo wp_create_nonce('hsr_ajax_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var staff = response.data;
                            //var select = $('#hsr_staff');
                            //select.empty();
                            $.each(staff, function(index, member) {
                                select.append($('<option></option>').attr('value', member.id).text(member.name));
                            });
                        } else {
                            alert('직원 정보를 불러오는데 실패했습니다.');
                        }
                    }
                });
            } else {
                //
            }
        });

        $('#hsr_staff').change(function() {
            var selectedDate = $('#hsr_date').val();
            var selectedStaff = $(this).val();
            if (selectedDate && selectedStaff) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'hsr_get_available_times',
                        date: selectedDate,
                        staff_id: selectedStaff,
                        nonce: '<?php echo wp_create_nonce('hsr_ajax_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var times = response.data;
                            var select = $('#hsr_time');
                            select.empty();
                            $.each(times, function(index, time) {
                                select.append($('<option></option>').attr('value', time.time).text(time.time));
                            });
                        } else {
                            alert('시간 정보를 불러오는데 실패했습니다.');
                        }
                    }
                });
            } else {
                //
            }
        });
    });
    </script>
    <?php
}

// processing the form http://localhost/home/booking-form/
function hsr_handle_reservation_submission() {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hsr_reservation_nonce')) {
        echo '<div class="error"><p>Nonce verification failed.</p></div>';
        return;
    }

    global $wpdb;
    $availability_table = $wpdb->prefix . 'hsr_availability';
    $reservations_table = $wpdb->prefix . 'hsr_reservations';

    $current_user = wp_get_current_user();
    $name = $current_user->display_name;
    $user_id = $current_user->ID;
    $phone = get_user_meta($user_id, 'phone', true);

    // 전화번호가 없으면 입력받은 전화번호를 저장
    if (empty($phone) && isset($_POST['hsr_phone'])) {
        $phone = sanitize_text_field($_POST['hsr_phone']);
        update_user_meta($user_id, 'phone', $phone);
    }

    //$staff_time = sanitize_text_field($_POST['hsr_staff_time']);
    $avail_date = sanitize_text_field($_POST['hsr_date']);
    $avail_staff = sanitize_text_field($_POST['hsr_staff']);
    $avail_time = sanitize_text_field($_POST['hsr_time']);

    // Validate inputs
    if (empty($name) || empty($phone) || empty($avail_staff) || empty($avail_time)) {
        echo '<div class="error"><p>모든 필드를 채워주세요.</p></div>';
        return;
    }

    // 선택된 availability 정보 가져오기
    $slot = $wpdb->get_row($wpdb->prepare("SELECT * FROM $availability_table WHERE date = %s and time = %s and staff_id = %d", $avail_date, $avail_time, $avail_staff));
    //$slot = $wpdb->get_row($wpdb->prepare("SELECT * FROM $availability_table WHERE id = %d", $staff_time));

    if (!$slot) {
        echo '<div class="error"><p>선택한 시간대를 찾을 수 없습니다. '.$avail_date . $avail_time. $avail_staff.'</p></div>';
        return;
    }

    // 예약 중복 확인
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $reservations_table WHERE date = %s AND time = %s AND staff_id = %d", $slot->date, $slot->time, $slot->staff_id));

    if ($existing) {
        echo '<div class="error"><p>선택한 시간대는 이미 예약되었습니다.</p></div>';
        return;
    }

    // 예약 삽입
    $wpdb->insert($reservations_table, [
        'name' => $name,
        'phone' => $phone,
        'user_id' => $user_id,
        'date' => $slot->date,
        'time' => $slot->time,
        'staff_id' => $slot->staff_id
    ]);

    // 예약된 availability 삭제
    $wpdb->delete($availability_table, ['id' => $slot->id]);

    echo '<div class="updated"><p>예약이 완료되었습니다. 감사합니다!</p></div>';

    // 이메일 발송
    hsr_send_reservation_emails($name, $phone, $slot->date, $slot->time, $slot->staff_id);
}


// reservation history
function hsr_reservation_history() {
    ob_start();

    if (isset($_POST['hsr_submit_history'])) {
        hsr_handle_history_submission();
    }

    hsr_display_history_form();

    return ob_get_clean();
}
function hsr_display_history_form() {
    echo '<h2>View Your Reservation History</h2>';
    echo '<form method="post">';
    wp_nonce_field('hsr_history_nonce');

    echo '<p>';
    echo '<label for="hsr_history_phone">Enter Your Phone Number:</label><br>';
    echo '<input type="text" id="hsr_history_phone" name="hsr_history_phone" required>';
    echo '</p>';

    submit_button('View History', 'primary', 'hsr_submit_history');

    echo '</form>';
}
function hsr_handle_history_submission() {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hsr_history_nonce')) {
        echo '<div class="error"><p>Nonce verification failed.</p></div>';
        return;
    }

    global $wpdb;
    $reservations_table = $wpdb->prefix . 'hsr_reservations';

    $phone = sanitize_text_field($_POST['hsr_history_phone']);

    if (empty($phone)) {
        echo '<div class="error"><p>Please enter your phone number.</p></div>';
        return;
    }

    // Fetch reservations
    $reservations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $reservations_table WHERE phone = %s ORDER BY date DESC, time DESC", $phone));

    if ($reservations) {
        echo '<h3>Your Reservations:</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Date</th><th>Time</th><th>Created At</th></tr></thead><tbody>';
        foreach ($reservations as $reservation) {
            echo '<tr>';
            echo '<td>' . esc_html($reservation->id) . '</td>';
            echo '<td>' . esc_html($reservation->name) . '</td>';
            echo '<td>' . esc_html($reservation->date) . '</td>';
            echo '<td>' . esc_html($reservation->time) . '</td>';
            echo '<td>' . esc_html($reservation->created_at) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No reservations found for this phone number.</p>';
    }
}

// User Reservation
// 예약 내역 단축코드 등록
function hsr_user_reservations() {    
    ob_start();

    if (!is_user_logged_in()) {
        echo '<p>예약 내역을 보려면 <a href="' . wp_login_url(get_permalink()) . '">로그인</a> 해주세요.</p>';
        return;
    }

    $current_user = wp_get_current_user();
    global $wpdb;
    $reservations_table = $wpdb->prefix . 'hsr_reservations';
    $staff_table = $wpdb->prefix . 'hsr_staff';

    // 사용자 예약 내역 불러오기
    $reservations = $wpdb->get_results($wpdb->prepare("SELECT r.*, s.name FROM $reservations_table r join $staff_table s ON r.staff_id = s.id WHERE user_id = %d ORDER BY date DESC, time DESC", $current_user->ID));
    //SELECT r.*, s.name FROM $reservations_table r join $staff_table s ON r.staff_id = s.id WHERE user_id = %d ORDER BY date DESC, time DESC;
    if ($reservations) {
        //echo '<h3>나의 예약 내역</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Phone</th><th>Date</th><th>Time</th><th>Booted At</th><th>Actions</th></tr></thead><tbody>';
        foreach ($reservations as $reservation) {
            echo '<tr>';
            echo '<td>' . esc_html($reservation->name) . '</td>';
            echo '<td>' . esc_html($reservation->phone) . '</td>';
            echo '<td>' . esc_html($reservation->date) . '</td>';
            echo '<td>' . esc_html($reservation->time) . '</td>';
            echo '<td>' . esc_html($reservation->created_at) . '</td>';
            echo '<td><a href="' . wp_nonce_url(admin_url('admin-post.php?action=hsr_delete_user_reservation&id=' . $reservation->id), 'hsr_delete_user_reservation_nonce') . '" onclick="return confirm(\'예약을 삭제하시겠습니까?\')">Delete</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>예약 내역이 없습니다.</p>';
    }    

    return ob_get_clean();
}

function hsr_delete_user_reservation() {
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }

    if (!isset($_GET['id']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'hsr_delete_user_reservation_nonce')) {
        wp_die('권한이 없습니다.');
    }

    $reservation_id = intval($_GET['id']);
    global $wpdb;
    $reservations_table = $wpdb->prefix . 'hsr_reservations';

    // 현재 사용자 ID 확인
    $current_user_id = get_current_user_id();
    $reservation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $reservations_table WHERE id = %d AND user_id = %d", $reservation_id, $current_user_id));

    if ($reservation) {
        $wpdb->delete($reservations_table, ['id' => $reservation_id]);
        wp_redirect(add_query_arg('deleted', 'true', wp_get_referer()));
        exit;
    } else {
        wp_die('예약을 찾을 수 없습니다.');
    }
}

?>