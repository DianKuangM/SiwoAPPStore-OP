<?php
require 'config.php';
$id = intval($_GET['id']);
$st = $pdo->prepare("SELECT * FROM apps WHERE id=?");
$st->execute([$id]);
$app = $st->fetch();
if(!$app){die("应用不存在");}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=htmlspecialchars($app['app_name'])?> - 拾物Siwo</title>
<?php include 'style.html'; ?>
</head>
<body>
<header>
<div class="container head-row">
    <a href="index.php" class="logo">拾物 Siwo</a>
    <div class="nav">
        <?php if(is_login()): ?>
        <a href="upload.php">上传</a>
        <?php endif; ?>
        <a href="index.php">首页</a>
    </div>
</div>
</header>
<div class="container">
    <div class="card">
        <?php if($app['icon']): ?>
        <img src="<?=htmlspecialchars($app['icon'])?>" style="width:90px;height:90px;border-radius:12px;">
        <?php endif; ?>
        <h1 style="margin:12px 0;"><?=htmlspecialchars($app['app_name'])?></h1>
        <p>包名：<?=htmlspecialchars($app['package_name'])?></p>
        <p>版本：<?=htmlspecialchars($app['version'])?></p>
        <p>大小：<?=$app['size']?> MB</p>
        <p>总下载：<?=$app['downloads']?> 次</p>
        <div style="margin:15px 0;">
            <h3>应用简介</h3>
            <p><?=nl2br(htmlspecialchars($app['description']))?></p>
        </div>

        <?php
        // 优先级：网盘外链 > 本地APK
        if (!empty($app['download_url'])):
        ?>
            <a href="<?=htmlspecialchars($app['download_url'])?>" target="_blank" class="btn">前往网盘下载</a>
        <?php elseif (!empty($app['apk_file'])): ?>
            <a href="download.php?id=<?=$app['id']?>" class="btn">下载APK</a>
        <?php else: ?>
            <p style="color:#999;">暂无可用下载资源</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>