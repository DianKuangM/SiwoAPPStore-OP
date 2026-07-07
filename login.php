<?php
require dirname(__DIR__) . '/config.php';
$msg = '';
if($_POST){
    $u = $_POST['user'];
    $p = $_POST['pwd'];
    $st = $pdo->prepare("SELECT id,password FROM users WHERE username=?");
    $st->execute([$u]);
    $row = $st->fetch();
    if($row && password_verify($p,$row['password'])){
        $_SESSION['userid'] = $row['id'];
        header("Location: ../index.php");
        exit;
    }else{
        $msg = "账号密码错误";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>登录 - 拾物Siwo</title>
<?php include dirname(__DIR__).'/style.html'; ?>
</head>
<body>
<header>
<div class="container head-row">
    <a href="../index.php" class="logo">拾物 Siwo</a>
</div>
</header>
<div class="container" style="max-width:420px;margin:60px auto;">
    <div class="card">
        <h2>账号登录</h2>
        <?php if($msg): ?>
        <p style="color:red"><?=$msg?></p>
        <?php endif; ?>
        <form method="post">
            <input placeholder="用户名" name="user" required>
            <input type="password" placeholder="密码" name="pwd" required>
            <button class="btn" style="width:100%;margin-top:10px;">登录</button>
        </form>
        <p style="margin-top:15px;">没有账号？<a href="../register.php">前往注册</a></p>
    </div>
</div>
</body>
</html>