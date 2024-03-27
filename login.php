<?php

require_once 'db-connect.php'; // Bevat databaseconnectie

if (isset($_POST['email']) && isset($_POST['password'])) {
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Zoek de gebruiker in de database
  $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? AND password = ?");
  $stmt->execute([$email, $password]);
  $admin = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$admin) {
    // Geen admin gevonden, zoek in docenten tabel
    $stmt = $pdo->prepare("SELECT * FROM docents WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
      // Gebruiker niet gevonden
      
      header('Location: login_fail.php');
      exit;
    }

    // ... Voer hier de acties uit na een succesvolle docent login ...

    // Voorbeeld: stuur de docent naar het dashboard
    header('Location: dashboard_docent.php');
    exit;
  }

  // ... Voer hier de acties uit na een succesvolle admin login ...

  // Voorbeeld: stuur de admin naar het admin panel
  header('Location: dashboard_admin.php');
  exit;
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stage2024</title>
    <link rel="stylesheet" href="styles/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
<div class="container-fluid">

<h1><img src="assets/logo.png" alt="logo" class="logo"></h1>
</div>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="col-md m-2 text-center">
                <h2>WELKOM BIJ SYNTRA PXL STAGE MANAGEMENT</h2>
                <p>Gelieve eerst in te loggen.</p>
            </div>
            <div class="col-md">
                <form method="post" action="login.php">
                    <label class="form-label" for="email">Email:</label>
                    <input class="form-control" type="text" id="email" name="email">
                    <label class="form-label" for="password">Password:</label>
                    <input class="form-control" type="password" id="password" name="password">
                    <input class="form-control btn btn-primary mt-3" type="submit" value="Login">
                </form>
            
            </div>
        </div>
           
    </div>
</div>

<?php include('footer.php'); ?>