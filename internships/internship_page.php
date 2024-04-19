<?php
session_start();

// Include the Internship class and database configuration
require_once 'Internship.php';

// Create an instance of the Internship class
$internship = new Internship($pdo);

// Check if there is a POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log('Post Data: ' . print_r($_POST, true));
    // Switch between actions
    switch ($_POST['action']) {
        case 'delete':
            $internship_id = $_POST['id'];
            $success = $internship->delete($internship_id);
            echo json_encode(['success' => $success]);
            break;
        
        case 'update':
            $internshipId = $_POST['id'];
            $companyData = [
                'companyName' => $_POST['companyName'],
                'address' => $_POST['address']
            ];
            $contactData = [
                'contactFirstName' => $_POST['contactFirstName'],
                'contactLastName' => $_POST['contactLastName'],
                'contactEmail' => $_POST['contactEmail'],
                'contactPhone' => $_POST['contactPhone']
            ];

            $updateCompanySuccess = $internship->updateCompany($internshipId, $companyData);
            $updateContactSuccess = $internship->updateContactPerson($internshipId, $contactData);

            echo json_encode([
                'success' => $updateCompanySuccess && $updateContactSuccess
            ]);
            break;
        
        case 'create':
            $companyName = $_POST['companyName'];
            $address = $_POST['address'];
            $contactFirstName = $_POST['contactFirstName'];
            $contactLastName = $_POST['contactLastName'];
            $contactEmail = $_POST['contactEmail'];
            $contactPhone = $_POST['contactPhone'];

            $result = $internship->create([
                'companyName' => $companyName,
                'address' => $address,
                'contactFirstName' => $contactFirstName,
                'contactLastName' => $contactLastName,
                'contactEmail' => $contactEmail,
                'contactPhone' => $contactPhone
            ]);

            echo json_encode(['success' => $result]);
            break;
        
        default:
            // Action not recognized
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
    exit;
}

// Get the list of all internships for display
$internships = $internship->read();

// Include the navbar based on the user type
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admins') {
        include('../includes/navbar_admin.php');
    } elseif ($_SESSION['user_type'] === 'teachers') {
        include('../includes/navbar_docent.php');
    }
} else {
    // If user is not logged in, redirect to login page
    header('Location: ./index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stageplaats</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap5.min.css">

    <link rel="stylesheet" href="../styles/internship.css">
</head>

<body>
    <div class="container mt-5">
        <h2>Stageplaatsen</h2>
        <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createInternshipModal">
            <i class="bi bi-plus-square m-2"></i>Stageplaats Toevoegen
        </button>
        <table id="internshipsTable" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>Bedrijf</th>
                    <th>Adres</th>
                    <th>Contactpersoon</th>
                    <th>Contacttelefoon</th>
                    <th>Contact E-mail</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($internships as $i) : ?>
                    <tr>
                        <td><?= htmlspecialchars($i['company_name']) ?></td>
                        <td><?= htmlspecialchars($i['company_address']) ?></td>
                        <td><?= htmlspecialchars($i['contact_name']) ?></td>
                        <td><?= htmlspecialchars($i['contact_phone']) ?></td>
                        <td><?= htmlspecialchars($i['contact_email']) ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm edit-button" data-id="<?= htmlspecialchars($i['internship_id']) ?>"><i class="bi bi-pencil-square"></i></button>
                            <button class="btn btn-danger btn-sm delete-button" data-id="<?= htmlspecialchars($i['internship_id']) ?>"><i class="bi bi-trash3"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Modal for Editing Internship -->
    <div class="modal fade" id="editInternshipModal" tabindex="-1" aria-labelledby="editInternshipModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editInternshipModalLabel">Stageplaats Bewerken</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Sluiten"></button>
                </div>
                <div class="modal-body">
                    <form id="editInternshipForm">
                        <input type="hidden" id="editInternshipId" name="id">
                        <!-- Добавляем скрытое поле для action -->
                        <input type="hidden" name="action" value="update">
                        <div class="mb-3">
                            <label for="editCompanyName" class="form-label">Bedrijfsnaam:</label>
                            <input type="text" class="form-control" id="editCompanyName" name="companyName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editAddress" class="form-label">Adres:</label>
                            <input type="text" class="form-control" id="editAddress" name="address" required>
                        </div>
                        <div class="mb-3">
                            <label for="editContactFirstName" class="form-label">Voornaam Contactpersoon:</label>
                            <input type="text" class="form-control" id="editContactFirstName" name="contactFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editContactLastName" class="form-label">Achternaam Contactpersoon:</label>
                            <input type="text" class="form-control" id="editContactLastName" name="contactLastName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editContactEmail" class="form-label">Contact Email:</label>
                            <input type="email" class="form-control" id="editContactEmail" name="contactEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="editContactPhone" class="form-label">Contact Telefoon:</label>
                            <input type="tel" class="form-control" id="editContactPhone" name="contactPhone" required>
                        </div>
                        <button type="submit" class="btn btn-success">Opslaan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for Creating Internship -->
    <div class="modal fade" id="createInternshipModal" tabindex="-1" aria-labelledby="createInternshipModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createInternshipModalLabel">Nieuwe Stageplaats Toevoegen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Sluiten"></button>
                </div>
                <div class="modal-body">
                    <form id="createInternshipForm">
                        <input type="hidden" name="action" value="create">  <!-- Добавлено скрытое поле для action -->
                        <div class="mb-3">
                            <label for="companyName" class="form-label">Bedrijfsnaam:</label>
                            <input type="text" class="form-control" id="companyName" name="companyName" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Adres:</label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactFirstName" class="form-label">Voornaam Contactpersoon:</label>
                            <input type="text" class="form-control" id="contactFirstName" name="contactFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactLastName" class="form-label">Achternaam Contactpersoon:</label>
                            <input type="text" class="form-control" id="contactLastName" name="contactLastName" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactEmail" class="form-label">Contact Email:</label>
                            <input type="email" class="form-control" id="contactEmail" name="contactEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactPhone" class="form-label">Contact Telefoon:</label>
                            <input type="tel" class="form-control" id="contactPhone" name="contactPhone" required>
                        </div>
                        <button type="submit" class="btn btn-success">Toevoegen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <!-- DataTables Bootstrap 5 JS -->
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#internshipsTable').DataTable();

            // Click handler for the "Delete" button
            $('#internshipsTable tbody').on('click', 'button.delete-button', function() {
                var button = $(this);
                var internshipId = button.data('id');

                if (confirm('Are you sure you want to delete this record?')) {
                    $.ajax({
                        url: 'http://localhost/Stage2024/internships/internship_page.php',
                        type: 'POST',
                        dataType: 'json', // Expect JSON response
                        data: {
                            'id': internshipId,
                            'action': 'delete'
                        },
                        success: function(result) {
                            if (result.success) {
                                table.row(button.parents('tr')).remove().draw();
                                alert('Record deleted.');
                            } else {
                                alert('Failed to delete the record.');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                            alert('Error occurred while processing the request.');
                        }
                    });
                }
            });

            // Click handler for the "Edit" button
            $('#internshipsTable tbody').on('click', 'button.edit-button', function() {
                var button = $(this);
                var internshipId = button.data('id');
                var row = table.row(button.parents('tr')).data(); // Get data from the row

                // Splitting the contact person's name into first name and last name
                var contactNameParts = row[2].split(' '); // Assuming the name is in the format "First Last"
                var contactFirstName = contactNameParts[0];
                var contactLastName = contactNameParts.slice(1).join(' '); // Joining the last name parts if any

                // Fill the edit form with data from the selected row
                $('#editInternshipId').val(internshipId);
                $('#editCompanyName').val(row[0]);
                $('#editAddress').val(row[1]);
                $('#editContactFirstName').val(contactFirstName);
                $('#editContactLastName').val(contactLastName); // Now filling last name separately
                $('#editContactPhone').val(row[3]);
                $('#editContactEmail').val(row[4]);

                // Show the edit modal
                $('#editInternshipModal').modal('show');
            });

            /// Form submission handler for editing internships
            $('#editInternshipForm').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: 'http://localhost/Stage2024/internships/internship_page.php',  // Make sure this is the correct URL
                    type: 'POST',
                    data: formData,
                    dataType: 'json',  // Make sure 'json' is specified as the data type
                    success: function(data) {  // 'data' will already be an object, no need to parse JSON
                        if (data.success) {
                            alert('Internship updated successfully!');
                            $('#editInternshipModal').modal('hide');
                            location.reload();  // Reload the page to see updated data
                        } else {
                            alert('Error updating internship. Please try again.');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred: ' + error);
                    }
                });
            });



            // Form submission handler for creating internships
            $('#createInternshipForm').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: 'http://localhost/Stage2024/internships/internship_page.php',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            alert('Internship created successfully!');
                            $('#createInternshipModal').modal('hide');
                            location.reload();  // Перезагрузите страницу, чтобы увидеть новые данные
                        } else {
                            alert('Error creating internship. Please try again.');
                        }
                    },
                    error: function() {
                        alert('Error creating internship. Please check the server and network.');
                    }
                });
            });
        });
    </script>


    <?php include('../includes/footer.php'); ?>