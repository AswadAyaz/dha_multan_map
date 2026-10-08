<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

if (!isLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}

$message = '';
$error = '';
$uploadedFiles = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = __DIR__ . '/../uploads/properties/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (isset($_FILES['images'])) {
        $files = $_FILES['images'];
        $count = count($files['name']);
        
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = time() . '_' . basename($files['name'][$i]);
                $filePath = $uploadDir . $fileName;
                
                // Check file size (max 5MB)
                if ($files['size'][$i] > 5 * 1024 * 1024) {
                    $error .= "File " . ($i + 1) . " is too large (max 5MB). ";
                    continue;
                }
                
                // Check file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($files['type'][$i], $allowedTypes)) {
                    $error .= "File " . ($i + 1) . " is not an allowed image type. ";
                    continue;
                }
                
                if (move_uploaded_file($files['tmp_name'][$i], $filePath)) {
                    $uploadedFiles[] = $fileName;
                } else {
                    $error .= "Failed to upload file " . ($i + 1) . ". ";
                }
            }
        }
        
        if (!empty($uploadedFiles)) {
            $message = count($uploadedFiles) . " image(s) uploaded successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Images - DHA Multan Admin</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; padding: 2rem; }
    .container { max-width: 800px; margin: 0 auto; }
    .card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
    h1 { color: #1e293b; margin-bottom: 1.5rem; }
    .upload-area { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 3rem; text-align: center; cursor: pointer; transition: all 0.3s; }
    .upload-area:hover { border-color: #3b82f6; background: #f8fafc; }
    .upload-area.dragover { border-color: #3b82f6; background: #eff6ff; }
    .upload-btn { background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; margin-top: 1rem; }
    .upload-btn:hover { background: #2563eb; }
    .success { background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #bbf7d0; }
    .error { background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #fecaca; }
    .file-list { margin-top: 1rem; }
    .file-item { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: #f8fafc; border-radius: 6px; margin-bottom: 0.5rem; }
    .preview { max-width: 100px; max-height: 100px; border-radius: 6px; }
    .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.875rem; margin-right: 0.5rem; }
    .btn-secondary { background: #64748b; color: white; }
    .btn-secondary:hover { background: #475569; }
    .back-link { display: inline-block; margin-bottom: 1rem; color: #3b82f6; text-decoration: none; }
    .back-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="container">
    <a href="/admin/dashboard.php" class="back-link">← Back to Dashboard</a>
    <div class="card">
      <h1>📷 Upload Property Images</h1>
      <p style="color: #64748b; margin-bottom: 1.5rem;">Upload images for your property listings. Max 5MB per file. Allowed: JPG, PNG, GIF, WebP.</p>
      
      <?php if ($message): ?>
        <div class="success"><?php echo $message; ?></div>
      <?php endif; ?>
      
      <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
      <?php endif; ?>
      
      <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <div class="upload-area" id="dropZone">
          <p style="font-size: 1.25rem; color: #64748b;">📁 Click or drag images here</p>
          <p style="color: #94a3b8; margin-top: 0.5rem;">Supports: JPG, PNG, GIF, WebP (Max 5MB each)</p>
          <input type="file" name="images[]" id="fileInput" multiple accept="image/*" style="display: none;">
          <button type="button" class="upload-btn" onclick="document.getElementById('fileInput').click()">Select Images</button>
        </div>
        
        <div class="file-list" id="fileList"></div>
        
        <div id="previewSection" style="margin-top: 1.5rem;"></div>
        
        <button type="submit" class="upload-btn" id="uploadBtn" style="display: none; margin-top: 1rem;">Upload Images</button>
      </form>
    </div>
    
    <?php if (!empty($uploadedFiles)): ?>
    <div class="card">
      <h2>✅ Uploaded Files</h2>
      <div class="file-list">
        <?php foreach ($uploadedFiles as $file): ?>
          <div class="file-item">
            <span><?php echo $file; ?></span>
            <span style="color: #16a34a;">✓ Uploaded</span>
          </div>
        <?php endforeach; ?>
      </div>
      <p style="margin-top: 1rem; color: #64748b; font-size: 0.875rem;">
        Use these filenames in your property listings: <code>/uploads/properties/<?php echo $uploadedFiles[0]; ?></code>
      </p>
    </div>
    <?php endif; ?>
    
    <div class="card">
      <h2>💡 Tips for InfinityFree</h2>
      <ul style="color: #64748b; line-height: 1.8;">
        <li>Images must be under <strong>5MB</strong> each</li>
        <li>Compress large images at <a href="https://tinypng.com/" target="_blank">TinyPNG</a></li>
        <li>Use FTP (FileZilla) for uploading many files at once</li>
        <li>After upload, copy the filename to use in property listings</li>
      </ul>
    </div>
  </div>

  <script>
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    const previewSection = document.getElementById('previewSection');
    const uploadBtn = document.getElementById('uploadBtn');
    let selectedFiles = [];

    dropZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
      dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      dropZone.classList.remove('dragover');
      handleFiles(e.dataTransfer.files);
    });

    dropZone.addEventListener('click', () => {
      fileInput.click();
    });

    fileInput.addEventListener('change', (e) => {
      handleFiles(e.target.files);
    });

    function handleFiles(files) {
      selectedFiles = Array.from(files);
      updateFileList();
      updatePreviews();
      uploadBtn.style.display = selectedFiles.length > 0 ? 'block' : 'none';
    }

    function updateFileList() {
      fileList.innerHTML = selectedFiles.map((file, i) => `
        <div class="file-item">
          <span>${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
          <button type="button" onclick="removeFile(${i})" style="background: #ef4444; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer;">Remove</button>
        </div>
      `).join('');
    }

    function updatePreviews() {
      previewSection.innerHTML = '<h3>Preview:</h3><div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.5rem;">';
      selectedFiles.forEach(file => {
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = (e) => {
            previewSection.innerHTML += `<img src="${e.target.result}" class="preview">`;
          };
          reader.readAsDataURL(file);
        }
      });
      previewSection.innerHTML += '</div>';
    }

    function removeFile(index) {
      selectedFiles.splice(index, 1);
      updateFileList();
      updatePreviews();
      uploadBtn.style.display = selectedFiles.length > 0 ? 'block' : 'none';
    }
  </script>
</body>
</html>
