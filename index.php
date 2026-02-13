<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/bootstrap.css">
  <script src="js/jquery.js"></script>
  <title>Login</title>
</head>

<body>
  <header>
    <nav class="navbar bg-body-tertiary">
      <div class="container-fluid">
        <a class="navbar-brand" href="#">
          Login
        </a>
      </div>
    </nav>
  </header>
  <main>
    <div class="container">
      <div class="d-flex justify-content-center flex-column bg-secondary p-5 m-3">
        <div class="p-2 h3 text-light">Credit</div>
        <div>
          <div class="input-group m-3">
            <div class="form-floating">
              <input type="text" class="form-control" id="admin_username" placeholder="Username">
              <label for="floatingInputGroup1">Username</label>
            </div>
          </div>
          <div class="input-group m-3">
            <div class="form-floating">
              <input type="text" class="form-control" id="admin_password" placeholder="Password">
              <label for="floatingInputGroup1">Password</label>
            </div>
          </div>
        </div>
        <div class='m-3'>
          <button class="btn btn-dark" id="btn_sign_in">Sign in</button>
        </div>
      </div>
      <div class='m-3 row' id="messageBox">
        <div class="col bg-danger bg-gradient">
          <div class=" d-flex justify-content-between ">
            <div class=" h3">Message:</div>
            <a class="h3 text-light btn" style="text-decoration: none;" id='close_messageBox'>X</a>
          </div>
          <div id="messageBody" class="col h3 text-light p-2" style="background-color:#f08080;">
          </div>
        </div>
      </div>
    </div>
  </main>
  <footer>

  </footer>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/login.js"></script>
</body>

</html>