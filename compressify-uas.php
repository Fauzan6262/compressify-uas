<?php
function compressImage($source, $destination, $quality = 75) {
  $info = getimagesize($source);
  if ($info['mime'] == 'image/jpeg') {
    $image = imagecreatefromjpeg($source);
    imagejpeg($image, $destination, $quality);
  } elseif ($info['mime'] == 'image/png') {
    $image = imagecreatefrompng($source);
    imagepng($image, $destination, 9);
  }
  return $destination;
}

function compressAudio($source, $destination) {
  $cmd = "ffmpeg -y -i \"$source\" -vn -ar 44100 -ac 2 -b:a 128k \"$destination\"";
  shell_exec($cmd);
  return $destination;
}

function compressVideo($source, $destination) {
  $cmd = "ffmpeg -y -i \"$source\" -vcodec libx264 -crf 28 \"$destination\"";
  shell_exec($cmd);
  return $destination;
}

$result = '';
$preview = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $type = $_POST['type'];
  $uploaded = $_FILES['file'];
  $filename = $uploaded['name'];
  $ext = pathinfo($filename, PATHINFO_EXTENSION);
  $originalPath = "original_" . time() . "_$filename";
  $destination = "compressed_" . time() . ".$ext";

  move_uploaded_file($uploaded['tmp_name'], $originalPath);

  switch ($type) {
    case 'image': compressImage($originalPath, $destination, 50); break;
    case 'audio': compressAudio($originalPath, $destination); break;
    case 'video': compressVideo($originalPath, $destination); break;
  }

  if (!file_exists($destination)) {
    $result = "<div class='text-red-600 text-center mt-4'>❌ Kompresi gagal. File tidak ditemukan.</div>";
  } else {
    $result = "<div class='text-green-600 font-semibold text-center mt-4'>✅ File berhasil dikompresi!</div>";
    $result .= "<div class='text-center mt-2'><a href='$destination' download class='bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700'>⬇️ Download Hasil</a></div>";

    if ($type == 'image') {
      $result .= "<div class='mt-4 text-center'><img src='$destination' class='rounded shadow max-w-sm mx-auto' alt='Preview Gambar'></div>";
    } elseif ($type == 'audio') {
      $originalSize = filesize($originalPath);
      $compressedSize = filesize($destination);
      $originalSizeKb = round($originalSize / 1024, 2);
      $compressedSizeKb = round($compressedSize / 1024, 2);

      $result .= "
      <div class='mt-6 text-center'>
        <h3 class='font-semibold mb-2'>🎧 Audio Sebelum Kompresi:</h3>
        <audio controls class='mx-auto mb-4'><source src='$originalPath' type='audio/mpeg'></audio>

        <h3 class='font-semibold mb-2'>🎧 Audio Setelah Kompresi:</h3>
        <audio controls class='mx-auto'><source src='$destination' type='audio/mpeg'></audio>

        <div class='text-sm mt-4 text-gray-700 leading-relaxed'>
          <p>📦 Ukuran sebelum: <strong>$originalSizeKb KB</strong></p>
          <p>📉 Ukuran sesudah: <strong>$compressedSizeKb KB</strong></p>
          <p>🎯 Kompresi dilakukan dengan menurunkan bitrate ke <strong>128 kbps</strong> menggunakan FFmpeg.</p>
          <p>🔊 Kualitas masih stereo (2 channel), sample rate tetap 44.1 kHz.</p>
        </div>
      </div>";
    } elseif ($type == 'video') {
  $originalSize = filesize($originalPath);
  $compressedSize = filesize($destination);
  $originalSizeKb = round($originalSize / 1024, 2);
  $compressedSizeKb = round($compressedSize / 1024, 2);

  $result .= "
  <div class='mt-6 text-center'>
    <h3 class='font-semibold mb-2'>🎥 Video Sebelum Kompresi:</h3>
    <video controls class='mx-auto mb-4 w-full max-w-lg'>
      <source src='$originalPath' type='video/mp4'>
    </video>

    <h3 class='font-semibold mb-2'>🎬 Video Setelah Kompresi:</h3>
    <video controls class='mx-auto w-full max-w-lg'>
      <source src='$destination' type='video/mp4'>
    </video>

    <div class='text-sm mt-4 text-gray-700 leading-relaxed'>
      <p>📦 Ukuran sebelum: <strong>$originalSizeKb KB</strong></p>
      <p>📉 Ukuran sesudah: <strong>$compressedSizeKb KB</strong></p>
      <p>🎯 Kompresi dilakukan menggunakan <strong>codec H.264 (libx264)</strong> dengan <strong>CRF = 28</strong>.</p>
      <p>📽️ CRF (Constant Rate Factor) menentukan kualitas akhir — semakin tinggi angkanya, semakin kecil ukuran, dan semakin rendah kualitas.</p>
    </div>
  </div>";
}

  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Compressify - Kompres File</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="bg-white shadow-xl rounded-lg p-8 max-w-xl w-full">
    <h1 class="text-3xl font-bold text-center text-blue-700 mb-6">📦 Compressify</h1>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Pilih Tipe File</label>
        <select name="type" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-300">
          <option value="image">🖼️ Image</option>
          <option value="audio">🎵 Audio</option>
          <option value="video">🎥 Video</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Pilih File</label>
        <input type="file" name="file" accept="image/*,audio/*,video/*" required class="w-full px-3 py-2 border rounded file:mr-4 file:py-2 file:px-4 file:border-0 file:text-sm file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
      </div>
      <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">🚀 Kompres Sekarang</button>
    </form>

    <?= $result ?>
  </div>
</body>
</html>
