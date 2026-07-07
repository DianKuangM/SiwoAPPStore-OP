<?php require 'config.php';
$msg = '';
if($_POST){
    $u = trim($_POST['user']);
    $p = $_POST['pwd'];
    $e = $_POST['email'];
    if(strlen($u)<3){$msg="用户名太短";}
    else{
        $hash = password_hash($p,PASSWORD_DEFAULT);
        try{
            $ins = $pdo->prepare("INSERT INTO users(username,password,email)VALUES(?,?,?)");
            $ins->execute([$u,$hash,$e]);
            header("Location:/login");exit;
        }catch(PDOException $err){
            $msg="用户名已存在";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>注册 - 拾物Siwo</title>
<?php include 'style.html'; ?>
</head>
<body>
<header>
<div class="container head-row">
    <a href="index.php" class="logo">拾物 Siwo</a>
</div>
</header>
<div class="container" style="max-width:420px;margin:50px auto;">
    <div class="card">
        <h2>新用户注册</h2>
        <?php if($msg): ?><p style="color:red"><?=$msg?></p><?php endif; ?>
        <form method="post">
            <input placeholder="用户名" name="user" required>
            <input placeholder="邮箱(选填)" name="email">
            <input placeholder="密码" type="password" name="pwd" required>
            <button class="btn" style="width:100%">注册</button>
        </form>
        <p style="margin-top:12px;">已有账号？<a href="/login" style="color:var(--main)">登录</a></p>
    </div>
</div>
</body>
</html>
