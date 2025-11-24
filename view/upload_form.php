<?php
// view/upload_form.php
$title = 'Upload File';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <title><?php echo htmlspecialchars($title); ?></title>
</head>
<body class="p-4">
  <div class="container">
    <h3>Upload File</h3>
    <form id="uploadForm" action="/actions/upload.php" method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Choose file</label>
        <input type="file" name="file" class="form-control" required>
      </div>
      <button class="btn btn-primary" type="submit">Upload</button>
    </form>

    <div id="result" class="mt-3"></div>
  </div>

  <script>
  document.getElementById('uploadForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.currentTarget;
    const data = new FormData(form);
    const res = await fetch(form.action, { method:'POST', body: data });
    const json = await res.json();
    const out = document.getElementById('result');
    if (json.success) {
      out.innerHTML = '<div class="alert alert-success">Uploaded: <a href="/' + json.path + '" target="_blank">' + json.path + '</a></div>';
    } else {
      out.innerHTML = '<div class="alert alert-danger">' + (json.error || 'Unknown error') + '</div>';
    }
  });
  </script>
</body>
</html>
