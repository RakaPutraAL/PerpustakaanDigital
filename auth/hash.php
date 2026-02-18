<?php
// PATH YANG BENAR (naik satu folder dari auth)
include __DIR__ . "/../config/database.php";

// Ambil semua user
$query = mysqli_query($conn, "SELECT id, username, password FROM users");

if (!$query) {
    die("Query gagal: " . mysqli_error($conn));
}

while ($user = mysqli_fetch_assoc($query)) {

    $password_lama = $user['password'];

    // Jika BELUM hash (bcrypt diawali $2y$)
    if (substr($password_lama, 0, 4) !== '$2y$') {

        $password_hash = password_hash($password_lama, PASSWORD_DEFAULT);

        $update = mysqli_query($conn, "
            UPDATE users 
            SET password = '$password_hash'
            WHERE id = {$user['id']}
        ");

        if ($update) {
            echo "✔ {$user['username']} berhasil di-hash<br>";
        } else {
            echo "❌ GAGAL hash {$user['username']} : " . mysqli_error($conn) . "<br>";
        }

    } else {
        echo "⏭ {$user['username']} sudah hash<br>";
    }
}

echo "<hr><b>SELESAI</b>";
