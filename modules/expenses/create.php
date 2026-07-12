<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

// Only Fleet Manager can add expenses
if (!isAdmin()) {
    header("Location: ../dashboard/index.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$error = '';

// Get vehicles
$vehicles = $db->query("SELECT * FROM vehicles WHERE status != 'Retired' ORDER BY registration_number");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = intval($_POST['vehicle_id']);
    $expense_type = sanitize($_POST['expense_type']);
    $description = sanitize($_POST['description']);
    $amount = floatval($_POST['amount']);
    $expense_date = sanitize($_POST['expense_date']);
    
    $stmt = $db->prepare("INSERT INTO expenses (vehicle_id, expense_type, description, amount, expense_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $vehicle_id, $expense_type, $description, $amount, $expense_date);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Expense added successfully!';
        header("Location: index.php");
        exit();
    } else {
        $error = 'Failed to add expense. Please try again.';
    }
}

$page_title = 'Add Expense';
$page_subtitle = 'Record a new expense';
?>
<?php include '../../includes/header.php'; ?>

<style>
    .form-container {
        max-width: 700px;
        margin: 0 auto;
    }
    .form-section {
        background: #F8FAFC;
        padding: 20px 24px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 1px solid #E2E8F0;
    }
    .form-section .section-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #0F172A;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 2px solid #4F46E5;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-section .section-title i {
        color: #4F46E5;
    }
    .form-label {
        font-weight: 600;
        color: #1E293B;
        font-size: 0.9rem;
        margin-bottom: 6px;
    }
    .form-label .required {
        color: #EF4444;
        margin-left: 4px;
    }
    .form-control, .form-select {
        border: 2px solid #E2E8F0;
        border-radius: 10px;
        padding: 0.6rem 1rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: #FFFFFF;
        color: #0F172A;
    }
    .form-control:focus, .form-select:focus {
        border-color: #4F46E5;
        box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
        outline: none;
    }
    .form-control::placeholder {
        color: #94A3B8;
        font-size: 0.9rem;
    }
    .form-text {
        color: #64748B;
        font-size: 0.8rem;
        margin-top: 4px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        border: none;
        padding: 0.7rem 2rem;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(79,70,229,0.4);
    }
    .btn-secondary {
        border-radius: 10px;
        padding: 0.7rem 2rem;
        font-weight: 600;
    }
    .alert-custom {
        border-radius: 10px;
        border: none;
        padding: 0.8rem 1.2rem;
    }
    .expense-types {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    .expense-types .type-option {
        padding: 8px 12px;
        border: 2px solid #E2E8F0;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        font-size: 0.85rem;
        font-weight: 500;
        color: #1E293B;
    }
    .expense-types .type-option:hover {
        border-color: #4F46E5;
        background: #F8FAFC;
    }
    .expense-types .type-option i {
        display: block;
        font-size: 1.2rem;
        margin-bottom: 4px;
        color: #4F46E5;
    }
    .expense-types .type-option.selected {
        border-color: #4F46E5;
        background: #EFF6FF;
    }
    @media (max-width: 576px) {
        .expense-types {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="form-container">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-plus-circle text-primary"></i> Add Expense
                    </h4>
                    <small class="text-muted">Record a new expense</small>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-custom alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-coins"></i> Expense Details
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="vehicle_id" class="form-label">
                                        Select Vehicle <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                        <option value="">-- Select Vehicle --</option>
                                        <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                            <option value="<?php echo $vehicle['vehicle_id']; ?>">
                                                <?php echo $vehicle['registration_number'] . ' - ' . $vehicle['model']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">
                                        Expense Type <span class="required">*</span>
                                    </label>
                                    <div class="expense-types" id="expenseTypes">
                                        <div class="type-option" data-value="Fuel">
                                            <i class="fas fa-gas-pump"></i>
                                            Fuel
                                        </div>
                                        <div class="type-option" data-value="Toll">
                                            <i class="fas fa-road"></i>
                                            Toll
                                        </div>
                                        <div class="type-option" data-value="Maintenance">
                                            <i class="fas fa-tools"></i>
                                            Maintenance
                                        </div>
                                        <div class="type-option" data-value="Repair">
                                            <i class="fas fa-wrench"></i>
                                            Repair
                                        </div>
                                        <div class="type-option" data-value="Insurance">
                                            <i class="fas fa-shield-alt"></i>
                                            Insurance
                                        </div>
                                        <div class="type-option" data-value="Other">
                                            <i class="fas fa-ellipsis-h"></i>
                                            Other
                                        </div>
                                    </div>
                                    <input type="hidden" name="expense_type" id="expense_type" value="">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">
                                        Description
                                    </label>
                                    <textarea class="form-control" id="description" 
                                              name="description" rows="2" 
                                              placeholder="Describe the expense"></textarea>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="amount" class="form-label">
                                        Amount ($) <span class="required">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="amount" name="amount" required 
                                               placeholder="Enter amount">
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="expense_date" class="form-label">
                                        Expense Date <span class="required">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="expense_date" 
                                           name="expense_date" required 
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Add Expense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Expense type selection
    $('.type-option').click(function() {
        $('.type-option').removeClass('selected');
        $(this).addClass('selected');
        $('#expense_type').val($(this).data('value'));
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        var expenseType = $('#expense_type').val();
        var amount = $('#amount').val();
        var date = $('#expense_date').val();
        var vehicle = $('#vehicle_id').val();
        
        if (!vehicle) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'No Vehicle Selected',
                text: 'Please select a vehicle.',
                confirmButtonColor: '#4F46E5'
            });
            return false;
        }
        
        if (!expenseType) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'No Type Selected',
                text: 'Please select an expense type.',
                confirmButtonColor: '#4F46E5'
            });
            return false;
        }
        
        if (!amount || amount <= 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Invalid Amount',
                text: 'Please enter a valid amount.',
                confirmButtonColor: '#4F46E5'
            });
            return false;
        }
        
        if (!date) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Invalid Date',
                text: 'Please select a valid date.',
                confirmButtonColor: '#4F46E5'
            });
            return false;
        }
        
        // Show loading state
        var $btn = $('#submitBtn');
        var originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Adding...');
        $btn.prop('disabled', true);
        
        setTimeout(function() {
            $btn.html(originalHtml);
            $btn.prop('disabled', false);
        }, 5000);
        
        return true;
    });
});
</script>

<?php include '../../includes/footer.php'; ?>