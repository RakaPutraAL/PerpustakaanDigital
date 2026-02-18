<?php
session_start();
include __DIR__ . "/../../config/database.php";

// Cek Akses Admin
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
  die("Akses ditolak");
}

// --- LOGIKA TAMBAH BUKU ---
if (isset($_POST['tambah'])) {
  // Ambil input & Sanitasi data
  $judul       = mysqli_real_escape_string($conn, substr($_POST['judul'], 0, 255));
  $penulis     = mysqli_real_escape_string($conn, substr($_POST['penulis'], 0, 100));
  $penerbit    = mysqli_real_escape_string($conn, substr($_POST['penerbit'], 0, 100));
  $kategori    = mysqli_real_escape_string($conn, substr($_POST['kategori'], 0, 50));
  $tahun       = $_POST['tahun'];
  $stok        = $_POST['stok'];
  $prefix_kode = mysqli_real_escape_string($conn, substr($_POST['prefix_kode'], 0, 20));
  $deskripsi   = mysqli_real_escape_string($conn, substr($_POST['deskripsi'], 0, 1000));

  // Inisialisasi nama file gambar (kosong)
  $nama_file = '';

  // --- LOGIKA UPLOAD GAMBAR MANUAL ---
  // Cek apakah user mengupload file
  if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
    $nama_asli  = $_FILES['gambar']['name'];
    $lokasi_tmp = $_FILES['gambar']['tmp_name'];
    $ukuran_file = $_FILES['gambar']['size'];
    $ekstensi   = pathinfo($nama_asli, PATHINFO_EXTENSION);
    
    // Ekstensi yang diperbolehkan
    $ekstensi_diperbolehkan = array('jpg', 'jpeg', 'png', 'webp', 'gif');
    
    // Cek Ekstensi
    if (in_array(strtolower($ekstensi), $ekstensi_diperbolehkan)) {
      // Cek Ukuran File (Maksimal 2MB)
      if ($ukuran_file <= 2048000) {
        // Buat nama file baru yang unik
        $nama_baru = "buku-" . time() . "-" . rand(1000, 9999) . "." . $ekstensi;
        
        // Tentukan folder tujuan
        $folder_upload = __DIR__ . "/../../uploads/";
        
        // Jika folder belum ada, buat folder otomatis
        if (!is_dir($folder_upload)) {
          mkdir($folder_upload, 0777, true);
        }

        // Pindahkan file dari temporary ke folder upload
        if (move_uploaded_file($lokasi_tmp, $folder_upload . $nama_baru)) {
          $nama_file = $nama_baru;
        } else {
          echo "<script>alert('Gagal mengupload gambar. Cek permission folder.'); window.history.back();</script>";
          exit;
        }
      } else {
        echo "<script>alert('Ukuran gambar terlalu besar (Maks 2MB).'); window.history.back();</script>";
        exit;
      }
    } else {
      echo "<script>alert('Format gambar tidak valid. Hanya JPG, JPEG, PNG, WEBP, GIF.'); window.history.back();</script>";
      exit;
    }
  }
  // Jika user TIDAK upload gambar, maka $nama_file tetap kosong ('')

  // 1. Insert ke tabel buku (Data Utama)
  $query_buku = "INSERT INTO buku (judul, penulis, penerbit, tahun, stok) 
                 VALUES ('$judul', '$penulis', '$penerbit', '$tahun', '$stok')";
  
  if (mysqli_query($conn, $query_buku)) {
    // 2. Ambil ID buku yang baru saja dibuat (Auto Increment)
    $id_buku_baru = mysqli_insert_id($conn);

    // 3. Insert ke detail_buku (Data Tambahan: Prefix, Gambar, Deskripsi, Kategori)
    // Kolom gambar akan bernilai kosong jika tidak ada upload
    $query_detail = "INSERT INTO detail_buku (id_buku, kode_buku, gambar, deskripsi, kategori) 
                    VALUES ('$id_buku_baru', '$prefix_kode', '$nama_file', '$deskripsi', '$kategori')";
    
    if (mysqli_query($conn, $query_detail)) {
      // Sukses penuh
      echo "<script>alert('✓ Data buku berhasil ditambahkan!'); window.location.href='kelola_buku.php';</script>";
    } else {
      // Gagal insert detail
      echo "Error menyimpan detail buku: " . mysqli_error($conn);
    }
  } else {
    // Gagal insert buku utama
    echo "Error menyimpan buku: " . mysqli_error($conn);
  }
}

// --- LOGIKA HAPUS BUKU ---
if (isset($_GET['hapus'])) {
  $id = mysqli_real_escape_string($conn, $_GET['hapus']);

  // Ambil nama file gambar terlebih dahulu untuk dihapus dari server
  $query_gambar = mysqli_query($conn, "SELECT gambar FROM detail_buku WHERE id_buku = '$id'");
  if ($query_gambar) {
    $data_gambar = mysqli_fetch_assoc($query_gambar);
    $nama_file = $data_gambar['gambar'];

    // Hapus file fisik jika ada
    if (!empty($nama_file)) {
      $file_path = __DIR__ . "/../../uploads/" . $nama_file;
      if (file_exists($file_path)) {
        unlink($file_path);
      }
    }
  }

  // Hapus dari detail_buku terlebih dahulu (karena foreign key)
  mysqli_query($conn, "DELETE FROM detail_buku WHERE id_buku = '$id'");

  // Kemudian hapus dari tabel buku
  $query_hapus = mysqli_query($conn, "DELETE FROM buku WHERE id = '$id'");

  if ($query_hapus) {
    echo "<script>alert('✓ Buku berhasil dihapus.'); window.location.href='kelola_buku.php';</script>";
  } else {
    echo "Gagal menghapus buku: " . mysqli_error($conn);
  }
}
?>