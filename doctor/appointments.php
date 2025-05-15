<?php
include '../config/database.php';
include '../config/functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit;
}

$doctor_id = getDoctorId($conn, $_SESSION['userid']);
$name = getDoctorName($conn, $_SESSION['userid']);

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['action'])) {
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    $is_guest = isset($_POST['is_guest']) && $_POST['is_guest'] === 'true';
    $remark = isset($_POST['remark']) ? trim($_POST['remark']) : null;
    
    if ($appointment_id && in_array($action, ['approve', 'reject', 'complete', 'cancel'])) {
        if ($is_guest) {
            // Handle guest appointment actions
            if ($action === 'cancel') {
                // Update guest appointment status to cancelled
                $sql = "UPDATE guest_appointments SET status = 'cancelled', remark = ? WHERE guest_appointment_id = ? AND doctor_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sii", $remark, $appointment_id, $doctor_id);
                
                if ($stmt->execute()) {
                    // Get guest appointment details for email
                    $get_details_sql = "SELECT * FROM guest_appointments WHERE guest_appointment_id = ?";
                    $get_details_stmt = $conn->prepare($get_details_sql);
                    $get_details_stmt->bind_param("i", $appointment_id);
                    $get_details_stmt->execute();
                    $appointment = $get_details_stmt->get_result()->fetch_assoc();
                    
                    if ($appointment) {
                        // Send cancellation email
                        $subject = "Appointment Cancelled - VitalHealth";
                        $message = "Dear " . $appointment['full_name'] . ",\n\n";
                        $message .= "Your appointment scheduled for " . date('F j, Y', strtotime($appointment['appointment_date'])) . " at " . date('h:i A', strtotime($appointment['appointment_time'])) . " has been cancelled.\n\n";
                        if ($remark) {
                            $message .= "Reason: " . $remark . "\n\n";
                        }
                        $message .= "Please feel free to book another appointment at a different time.\n\n";
                        $message .= "Best regards,\nVitalHealth Team";
                        
                        // Try to send email
                        $emailSent = @sendEmail($appointment['email'], $subject, $message);
                        
                        header("Location: appointments.php?success=1&email=" . ($emailSent ? '1' : '0'));
                        exit;
                    }
                }
                header("Location: appointments.php?error=1");
                exit;
            } else {
                // Handle other guest actions (approve/reject)
                $redirect = "process_guest_approval.php?appointment_id=" . $appointment_id . "&action=" . $action;
                if ($remark) $redirect .= "&remark=" . urlencode($remark);
                header("Location: $redirect");
                exit;
            }
        } else {
            // Handle regular appointment status updates
            $status = '';
            switch ($action) {
                case 'approve':
                    $status = 'scheduled';
                    break;
                case 'reject':
                case 'cancel':
                    $status = 'cancelled';
                    break;
                case 'complete':
                    $status = 'completed';
                    break;
            }
            if (in_array($action, ['cancel', 'reject']) && $remark) {
                $sql = "UPDATE appointments SET status = ?, remark = ? WHERE appointment_id = ? AND doctor_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssii", $status, $remark, $appointment_id, $doctor_id);
            } else {
                $sql = "UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sii", $status, $appointment_id, $doctor_id);
            }
            if ($stmt->execute()) {
                header("Location: appointments.php?success=1");
                exit;
            } else {
                header("Location: appointments.php?error=1");
                exit;
            }
        }
    }
}

// Get filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build the query for regular appointments
$sql = "SELECT 
            'regular' as appointment_type,
            a.appointment_id,
            a.patient_id,
            p.full_name as patient_name,
            p.contact_number,
            u.email,
            a.appointment_date,
            a.appointment_time,
            a.status,
            'Regular' as consultation_type
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        JOIN users u ON p.user_id = u.user_id
        WHERE a.doctor_id = ?";

if ($status_filter !== 'all') {
    $sql .= " AND a.status = ?";
}

// Add guest appointments
$sql .= " UNION ALL 
        SELECT 
            'guest' as appointment_type,
            ga.guest_appointment_id as appointment_id,
            NULL as patient_id,
            ga.full_name as patient_name,
            ga.phone_number as contact_number,
            ga.email,
            ga.appointment_date,
            ga.appointment_time,
            ga.status,
            ga.consultation_type
        FROM guest_appointments ga
        WHERE ga.doctor_id = ?";

if ($status_filter !== 'all') {
    $sql .= " AND ga.status = ?";
}

// Apply type filter
if ($type_filter !== 'all') {
    $sql = "SELECT * FROM (" . $sql . ") as combined_appointments WHERE appointment_type = ?";
}

$sql .= " ORDER BY appointment_date DESC, appointment_time ASC";

$stmt = $conn->prepare($sql);

if ($status_filter !== 'all' && $type_filter !== 'all') {
    $stmt->bind_param("issss", $doctor_id, $status_filter, $doctor_id, $status_filter, $type_filter);
} elseif ($status_filter !== 'all') {
    $stmt->bind_param("isss", $doctor_id, $status_filter, $doctor_id, $status_filter);
} elseif ($type_filter !== 'all') {
    $stmt->bind_param("iis", $doctor_id, $doctor_id, $type_filter);
} else {
    $stmt->bind_param("ii", $doctor_id, $doctor_id);
}

$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);

// Get success/error messages
$success = isset($_GET['success']) ? $_GET['success'] : null;
$error = isset($_GET['error']) ? $_GET['error'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <?php include 'doctor_sidebar.php'; ?>

    <div class="flex-1 p-5 ml-64">
        <h1 class="font-semibold text-3xl mb-6 mt-3">Appointments</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php if (isset($_GET['email'])): ?>
                    <?php if ($_GET['email'] == '1'): ?>
                        <span class="block sm:inline">Appointment processed successfully and email notification sent.</span>
                    <?php else: ?>
                        <span class="block sm:inline">Appointment processed successfully but email notification failed.</span>
                        <?php if (isset($_GET['debug'])): ?>
                            <script>
                                console.log('Email Debug Information:', <?php echo json_encode(urldecode($_GET['debug'])); ?>);
                            </script>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="block sm:inline">Appointment processed successfully.</span>
                <?php endif; ?>
                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Failed to update appointment status. Please try again.</span>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="GET" class="flex gap-4 items-center">
                <div class="flex-1">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                    <select name="status" id="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Appointments</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Filter by Type</label>
                    <select name="type" id="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="regular" <?php echo $type_filter === 'regular' ? 'selected' : ''; ?>>Regular Patients</option>
                        <option value="guest" <?php echo $type_filter === 'guest' ? 'selected' : ''; ?>>Guest Patients</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mt-6">
                    Apply Filter
                </button>
            </form>
        </div>

        <!-- Appointments List -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <?php if (empty($appointments)): ?>
                <div class="p-6 text-center text-gray-500">
                    <p>No appointments found.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                            <?php if ($appointment['appointment_type'] === 'guest'): ?>
                                                <span class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Guest</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($appointment['email']): ?>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['email']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['contact_number']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo ucfirst($appointment['consultation_type']); ?> Consultation
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch ($appointment['status']) {
                                            case 'pending':
                                                $status_class = 'bg-yellow-100 text-yellow-800';
                                                $status_text = 'Pending';
                                                break;
                                            case 'scheduled':
                                                $status_class = 'bg-blue-100 text-blue-800';
                                                $status_text = 'Scheduled';
                                                break;
                                            case 'confirmed':
                                                $status_class = 'bg-green-100 text-green-800';
                                                $status_text = 'scheduled';
                                                break;
                                            case 'rejected':
                                                $status_class = 'bg-red-100 text-red-800';
                                                $status_text = 'Rejected';
                                                break;
                                            case 'completed':
                                                $status_class = 'bg-green-100 text-green-800';
                                                $status_text = 'Completed';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'bg-red-100 text-red-800';
                                                $status_text = 'Cancelled';
                                                break;
                                            default:
                                                $status_class = 'bg-gray-100 text-gray-800';
                                                $status_text = ucfirst($appointment['status']);
                                        }
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <?php if ($appointment['appointment_type'] === 'guest'): ?>
                                            <span class="ml-2 text-xs text-gray-500">(Guest)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($appointment['status'] === 'pending'): ?>
                                            <?php if ($appointment['appointment_type'] === 'guest'): ?>
                                                <form method="POST" action="process_guest_approval.php" class="inline">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="text-green-600 hover:text-green-900 mr-3">
                                                        <i class="fas fa-check-circle"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="process_guest_approval.php" class="inline">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-times-circle"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="text-green-600 hover:text-green-900 mr-3">
                                                        <i class="fas fa-check-circle"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-times-circle"></i> Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php elseif ($appointment['status'] === 'scheduled' || $appointment['status'] === 'confirmed'): ?>
                                            <?php if ($appointment['appointment_type'] === 'guest'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="is_guest" value="true">
                                                    <input type="hidden" name="remark" value="">
                                                    <button type="button" class="text-red-600 hover:text-red-900 open-remark-modal" data-action="cancel">
                                                        <i class="fas fa-ban"></i> Cancel
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="remark" value="">
                                                    <button type="button" class="text-red-600 hover:text-red-900 open-remark-modal" data-action="cancel">
                                                        <i class="fas fa-ban"></i> Cancel
                                                    </button>
                                                </form>
                                                <form action="finalassessment.php" method="GET" class="inline">
                                                    <input type="hidden" name="patientid" value="<?php echo $appointment['patient_id']; ?>">
                                                    <input type="hidden" name="appointmentid" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <button type="submit" class="text-purple-600 hover:text-purple-900 mr-3">
                                                        <i class="fas fa-file-medical"></i> Final Assessment
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Remark Modal -->
    <div id="remarkModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
            <h2 class="text-lg font-semibold mb-4" id="remarkModalTitle">Add Remark</h2>
            <textarea id="remarkInput" class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4" rows="4" placeholder="Enter your remark here..."></textarea>
            <div class="flex justify-end gap-2">
                <button id="cancelRemarkBtn" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
                <button id="submitRemarkBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit</button>
            </div>
            <button id="closeRemarkModal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">&times;</button>
        </div>
    </div>

    <script>
    let currentRemarkForm = null;
    let currentAction = '';

    // Open modal on button click
    Array.from(document.getElementsByClassName('open-remark-modal')).forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            currentRemarkForm = this.closest('form');
            currentAction = this.getAttribute('data-action');
            document.getElementById('remarkModalTitle').textContent = currentAction === 'cancel' ? 'Cancel Appointment Remark' : 'Reject Appointment Remark';
            document.getElementById('remarkInput').value = '';
            document.getElementById('remarkModal').classList.remove('hidden');
            document.getElementById('remarkInput').focus();
        });
    });

    // Close modal
    function closeRemarkModal() {
        document.getElementById('remarkModal').classList.add('hidden');
        currentRemarkForm = null;
    }
    document.getElementById('closeRemarkModal').onclick = closeRemarkModal;
    document.getElementById('cancelRemarkBtn').onclick = closeRemarkModal;

    // Submit modal
    function submitRemark() {
        if (!currentRemarkForm) {
            alert('No form found to submit the remark.');
            closeRemarkModal();
            return;
        }
        const remark = document.getElementById('remarkInput').value.trim();
        if (!remark) {
            alert('Please enter a remark.');
            return;
        }
        const remarkInput = currentRemarkForm.querySelector('input[name="remark"]');
        if (remarkInput) {
            remarkInput.value = remark;
            currentRemarkForm.submit();
        } else {
            alert('Error: Could not find remark input field.');
        }
        closeRemarkModal();
    }
    document.getElementById('submitRemarkBtn').onclick = submitRemark;

    // Allow Enter+Ctrl to submit
    const remarkInput = document.getElementById('remarkInput');
    if (remarkInput) {
        remarkInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                submitRemark();
            }
        });
    }
    </script>
</body>
</html> 