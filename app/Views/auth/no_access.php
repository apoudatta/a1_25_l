<?php
// app/Views/no_access.php
http_response_code(403);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>No Access — 403</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5.0.2 -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
    crossorigin="anonymous"
  >
  <style>
    body { background: #f8f9fa; }
    .access-card {
      max-width: 560px;
      border: 0;
      border-radius: 1rem;
      box-shadow: 0 10px 30px rgba(0,0,0,.06);
    }
    .icon-wrap {
      width: 84px;
      height: 84px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #fff;
      box-shadow: 0 6px 18px rgba(0,0,0,.05);
      margin-top: -42px;
    }
    .code-chip {
      font-weight: 600;
      letter-spacing: .08em;
      background: #fff3cd;
      color: #664d03;
      border: 1px solid #ffe69c;
      border-radius: .5rem;
      padding: .25rem .5rem;
      display: inline-block;
    }
  </style>
</head>
<body>
  <div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card access-card text-center bg-white">
      <div class="card-body p-4 p-md-5">
        <div class="icon-wrap mx-auto">
          <!-- Lock icon (SVG) -->
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-lock" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M8 1a3 3 0 0 0-3 3v3h6V4a3 3 0 0 0-3-3m4 6V4a4 4 0 1 0-8 0v3a2 2 0 0 0-2 2v4a2
                     2 0 0 0 2 2h8a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2m-4 3.5a1.5 1.5 0 1 0 0 3
                     1.5 1.5 0 0 0 0-3"/>
          </svg>
        </div>

        <h1 class="h3 mt-4 mb-2">
          Access Denied
        </h1>
        <p class="text-muted mb-3">
          You don’t have permission to view this page or perform this action.
        </p>

        <!-- <div class="mb-4">
          <span class="code-chip">HTTP 403 — Forbidden</span>
        </div> -->

        <div class="d-grid d-sm-flex gap-2 justify-content-center">
          <a
            href="<?= esc(env('PORTAL_URL')) ?>"
            class="btn btn-primary btn-lg px-4"
          >
          Go Back
          </a>
        </div>

        <p class="text-muted small mt-4 mb-0">
          If you believe this is an error, please contact your administrator.
        </p>
      </div>
    </div>
  </div>

  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
    crossorigin="anonymous"
  ></script>
</body>
</html>
