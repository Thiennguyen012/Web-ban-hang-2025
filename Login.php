<?php
session_start();
require 'components/header.php';
require 'db/connect.php';

if (isset($_COOKIE['username']) && isset($_COOKIE['password'])) {
  $cookie_id = $_COOKIE['username'];
  $password = $_COOKIE['password'];
} else {
  $cookie_id = '';
  $password = '';
}
// Khởi tạo biến để lưu thông báo
$alert_message = '';
$alert_type = '';

if (isset($_POST['remember'])) {
  setcookie('username', $_POST['username'], time() + 60 * 60 * 24);
  setcookie('password', $_POST['password'], time() + 60 * 60 * 24);
} else {
  setcookie('username', '', time() - 3600);
  setcookie('password', '', time() - 3600);
}
?>

<!-- Xử lý form đăng nhập -->
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST["username"];
  $password = $_POST["password"];

  // Truy vấn database để kiểm tra thông tin đăng nhập và lấy role với prepared statement
  $sql = "SELECT * FROM site_user WHERE username = ? AND password = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $username, $password);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $role = $row['role'];

    // Tạo session với đầy đủ thông tin
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['name'] = $row['name'];

    // Kiểm tra role để chuyển hướng
    if ($role == 0) {
      // Admin
      header("Location: admin/home-page/admin.php");
      exit();
    } else if ($role == 1) {
      // Người dùng
      header("Location: index.php");
      exit();
    } else {
      // Role không hợp lệ, chuyển về trang đăng nhập với lỗi
      $error_message = "Role không hợp lệ.";
    }
  } else {
    $alert_message = 'Wrong Username or Password!';
    $alert_type = 'warning';
  }
}
?>

<body>
  <!-- Main container -->
  <div class="container d-flex justify-content-center align-items-center min-vh-100 border-5">
    <!-- login-container -->
    <div class="row border border rounded-5 p-3 bg-white" style="width: 930px; margin: auto">
      <!-- Alert Box - Hiển thị thông báo -->
      <?php if (!empty($alert_message)): ?>
        <div class="col-12 mb-3">
          <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert" id="alertBox">
            <i
              class="bi bi-<?php echo $alert_type == 'success' ? 'check-circle' : ($alert_type == 'danger' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
            <?php echo $alert_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        </div>
      <?php endif; ?>
      <!-- left box -->
      <div class="col-md-6 rounded-4 justify-content-center align-content-center d-flex flex-column image-container">
        <div>
          <img id="login-image" src="./img/login-side-img.jpeg" class="img-fluid rounded-5" alt="Login Side" />
        </div>
        <script>
          function updateLoginImage() {
            const img = document.getElementById("login-image");
            const aspectRatio = window.innerHeight / window.innerWidth;

            if (aspectRatio > 1.05) {
              img.src = "./img/side-img-crop.png";
            } else {
              img.src = "./img/login-side-img.jpeg";
            }
          }

          // Gọi ngay khi load
          window.addEventListener("load", updateLoginImage);
          // Gọi lại khi thay đổi kích thước cửa sổ
          window.addEventListener("resize", updateLoginImage);
        </script>
      </div>
      <!-- right box -->
      <!-- tạo cột phải -->

      <div class="col-md-6">
        <!-- Form đăng nhập -->
        <form action="login.php" method="post">
          <!-- tạo 1 row ở cột phải -->
          <div class="row align-items-center">
            <div class="d-flex justify-content-between align-items-center">
              <p class="text-dark mt-4 mb-4" id="login-text">Sign in</p>
              <a href="#" onclick="location.href='index.php';return false;" class="btn btn-transperent"><i class="bi bi-arrow-left fs-5"></i></a>
            </div>
            <!-- input username, password -->
            <div class="input-group mb-3">
              <input type="text" id="username" name="username" class="form-control form-control-lg bg-light fs-6"
                placeholder="Username" value="<?php echo $cookie_id ?>" />
            </div>

            <div class="input-group mb-4">
              <input type="password" id="password" name="password" class="form-control form-control-lg bg-light fs-6"
                placeholder="Password" value="<?php echo $password ?>" />
            </div>

            <!-- tạo checkbox và forgot password trên cùng 1 hàng, dùng d-flex và justify-content-beetween
              2 đối tượng này phải cùng cấp  -->
            <div class="input-group d-flex justify-content-between">
              <div>
                <input type="checkbox" class="form-check-input" id="remember" name="remember" />
                <label for="remember"><small class="text-dark">Remember me?</small></label>
              </div>

              <div>
                <small><a href="#" class="text-success float-end"
                    onclick="window.location.href='module/forgot-password/send-email.php'; return false;">Forgot
                    password?</a></small>
              </div>
            </div>
            <!-- Button login -->
            <div class="input-group mb-lg-3 mb-md-2 mb-sm-2">
              <button type="submit" class="btn btn-dark btn-lg mt-4 rounded-5 w-100" id="login-btn">
                Sign in
              </button>
            </div>
            <!-- line -->
            <div>
              <hr style="border: 1px solid #9da1a6" />
            </div>
            <div class="mb-3">
              <small class="text-dark">Or login with</small>
            </div>
            <!-- tạo 1 row với 4 cols để thêm 4 button trong cùng 1 hàng -->
            <a href="module/login-google/Login-google.php">
              <button class="btn bg-light w-100 fs-6 rounded-5" type="button">
                <img src="./img/google.png" style="width: 30px" />
                <small class="mx-3">Continue with <span class="fw-semibold">Google</span></small>
              </button>
            </a>
            <!-- tạo need an account và sign up cùng trên 1 hàng, lại dùng d-flex justify-content -->
            <div class="justify-content d-flex mt-4">
              <div>
                <small class="text" style="margin-right: 5px">Need an account?</small>
              </div>

              <div>
                <small><a href="./Registration.php" class="text-success">Sign up</a></small>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="asset/main-script1.js"></script>
  <!-- bootstrap js -->
  <script src="./asset/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>