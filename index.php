<?php
include 'config/database.php';
?>

<?php
if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {

        $logged_in = mysqli_fetch_assoc($result);

        if ($logged_in['role'] == 'patient') {
            session_start();
            $_SESSION['userid'] = $logged_in['user_id'];
            header('Location: /Healthcare/patientdashboard.php');
        } else if ($logged_in['role'] == 'doctor') {
            session_start();
            $_SESSION['userid'] = $logged_in['user_id'];
            header('Location: /Healthcare/doctordashboard.php');
        } else {
            session_start();
            $email = filter_input(
                INPUT_POST,
                'email',
                FILTER_SANITIZE_SPECIAL_CHARS
            );
            $_SESSION['email'] = $email;
            header('Location: /Healthcare/dashboard.php');
        }
    } else {
        echo 'invalid login';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <title>Document</title>
</head>

<body>
    <div class="flex justify-center items-center h-screen bg-sky-100">
        <div class="w-130 p-6 shadow-lg bg-white rounded-md">
            <i class="fas fa-heartbeat text-green-500 text-4xl"></i>
            <h1 class="text-3xl text-center font-semibold mb-3">Log in to your account</h1>
            <p class="text-center text-neutral-600 mb-4">Welcome! Please enter your credentials</p>
            <form action="index.php" method="post">
                <input type="text" name="email" class="mt-5 border border-neutral-300 w-full p-2 rounded-md" placeholder="Username">
                <input type="password" name="password" class="mt-5 border border-neutral-300 w-full p-2 rounded-md" placeholder="Password">
                <button type="submit" name="submit" class="text-center text-white border indigo-600 bg-green-600 w-full mt-10 p-2 rounded-md">Sign In</button>
            </form>
            <p class="text-center mt-3">Don't have an account? <a href="google.com" class="font-bold">Sign Up</a></p>

        </div>
    </div>
</body>

</html>